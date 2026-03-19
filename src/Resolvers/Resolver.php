<?php
    /**
     * Project Name:    Wingman Nexus - Resolver
     * Created by:      Angel Politis
     * Creation Date:   Dec 02 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Resolvers namespace.
    namespace Wingman\Nexus\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\SchemaValidationException;
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Bridge\Verix\Validator;
    use Wingman\Nexus\Caching\CacheManager;
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Interfaces\Resolver as ResolverInterface;
    use Wingman\Nexus\Objects\ArgumentList;
    use Wingman\Nexus\Objects\RouteRegistry;
    use Wingman\Nexus\Objects\RoutingPath;
    use Wingman\Nexus\Objects\RoutingResult;
    use Wingman\Nexus\Parsers\PatternParser;
    use Wingman\Nexus\RouteCompiler;
    use Wingman\Nexus\RouteMatcher;
    use Wingman\Nexus\TypeRegistry;

    /**
     * Represents a resolver.
     * @package Wingman\Nexus\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class Resolver implements ResolverInterface {
        /**
         * The configuration of a resolver.
         * @var Configuration|null
         */
        protected ?Configuration $config;

        /**
         * The single-wildcard token forwarded to `UrlGenerator` (configurable via Cortex).
         * @var string
         */
        #[Configurable("nexus.symbols.wildcard1")]
        protected string $wildcard1 = RouteCompiler::DEFAULT_WILDCARD_1;

        /**
         * The multi-wildcard token forwarded to `UrlGenerator` (configurable via Cortex).
         * @var string
         */
        #[Configurable("nexus.symbols.wildcardN")]
        protected string $wildcardN = RouteCompiler::DEFAULT_WILDCARD_N;

        /**
         * The regex used to detect variable placeholders (forwarded to `PatternParser`).
         * @var string
         */
        #[Configurable("nexus.regex.variable")]
        protected string $variableRegex = PatternParser::DEFAULT_VARIABLE_REGEX;

        /**
         * The type name used when a variable has no explicit type annotation (forwarded to `PatternParser`).
         * @var string
         */
        #[Configurable("nexus.variableDefaultType")]
        protected string $variableDefaultType = PatternParser::DEFAULT_VARIABLE_DEFAULT_TYPE;

        /**
         * The maximum number of redirect hops before aborting with an error.
         * @var int
         */
        #[Configurable("nexus.maxRedirectDepth")]
        protected int $maxRedirectDepth = 10;

        /**
         * The maximum number of rewrite hops before aborting with an error.
         * @var int
         */
        #[Configurable("nexus.maxRewriteDepth")]
        protected int $maxRewriteDepth = 10;

        /**
         * The pattern parser of a resolver.
         * @var PatternParser
         */
        protected PatternParser $parser;

        /**
         * The rules of a resolver.
         * @var array
         */
        protected array $rules = [];

        /**
         * The fallback rules of a resolver, evaluated in order after all normal rules have been exhausted.
         * @var array
         */
        protected array $fallbackRules = [];

        /**
         * The cacher of a router.
         * @var CacheManager
         */
        protected CacheManager $cacheManager;

        /**
         * The rewrite registry of a resolver.
         * @var RouteRegistry
         */
        protected RouteRegistry $registry;

        /**
         * The matcher of a resolver.
         * @var RouteMatcher
         */
        protected RouteMatcher $matcher;

        /**
         * Whether the registry of a resolver has been refreshed.
         * @var bool
         */
        protected bool $registryRefreshed = false;

        /**
         * The routing path of a resolver.
         * @var RoutingPath
         */
        protected RoutingPath $path;

        /**
         * The type registry of a resolver.
         * @var TypeRegistry
         */
        protected TypeRegistry $types;

        /**
         * Creates a new resolver.
         * @param CacheManager $cacheManager The cache manager.
         * @param RoutingPath $path The routing path.
         * @param array|Configuration $config The configuration.
         * @param TypeRegistry $types The type registry.
         */
        public function __construct (CacheManager $cacheManager, RoutingPath $path, array|Configuration $config, TypeRegistry $types) {
            Configuration::hydrate($this, $config);
            $this->matcher = new RouteMatcher();
            $this->cacheManager = $cacheManager;
            $this->path = $path;
            $this->config = $config instanceof Configuration ? $config : null;
            $this->types = $types;
            $this->parser = new PatternParser($this->variableRegex, $this->variableDefaultType);
        }

        /**
         * Gets the associated rule type for a resolver.
         * @return RuleType The rule type.
         */
        abstract protected static function getRuleType () : RuleType;

        /**
         * Ensures that the registry of a resolver is ready.
         * @return static The resolver.
         */
        protected function prepareRegistry () : static {
            if (!$this->registryRefreshed) $this->refreshRegistry();
            return $this;
        }

        /**
         * Refresh the registry of a resolver from the cache.
         * @return static The resolver.
         */
        protected function refreshRegistry () : static {
            $rules = $this->cacheManager->load(static::getRuleType(), array_merge($this->rules, $this->fallbackRules));
            $this->registry = new RouteRegistry($rules["compiled"], $rules["definitions"], $rules["targets"]);
            $this->registryRefreshed = true;
            Emitter::create()->with(ruleType: static::getRuleType(), resolver: $this)->emit(Signal::REGISTRY_REFRESHED);
            return $this;
        }

        /**
         * Validates captured parameters from a matched route against their Verix schema types.
         * Returns false if any Verix-typed parameter fails validation, causing the caller to
         * skip the current rule and continue searching for the next match.
         * @param string $name The rule name.
         * @param ArgumentList|null $matches The argument list populated by the matcher.
         * @return bool Whether all Verix-typed parameters satisfy their schema constraints.
         */
        protected function validateSchemaParameters (string $name, ?ArgumentList $matches) : bool {
            if ($matches === null) return true;

            $definition = $this->registry->getDefinition($name);

            if ($definition === null) return true;

            $namedMatches = $matches->getAllNamed();

            foreach ($definition->getParameters() as $param) {
                if (!Validator::isSchemaExpression($param->getType())) continue;

                $value = $namedMatches[$param->getName()] ?? null;

                if ($value === null) continue;

                try {
                    Validator::validate($value, $param->getType(), $param->getName());
                }
                catch (SchemaValidationException) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Adds rules to the resolver's fallback list.
         * Fallback rules are evaluated in registration order only after every normal rule
         * has been tried and found no match, providing a guaranteed last-resort ordering
         * that is independent of how rules were registered.
         * @param array $rules The rules to add.
         * @return static The resolver.
         */
        public function addFallbackRules (array $rules) : static {
            array_push($this->fallbackRules, ...$rules);
            $this->registryRefreshed = false;
            Emitter::create()->with(rules: $rules, ruleType: static::getRuleType(), resolver: $this)->emit(Signal::RULES_ADDED);
            return $this;
        }

        /**
         * Adds rules to a resolver.
         * @param array $rules The rules to add.
         * @return static The resolver.
         */
        public function addRules (array $rules) : static {
            array_push($this->rules, ...$rules);
            $this->registryRefreshed = false;
            Emitter::create()->with(rules: $rules, ruleType: static::getRuleType(), resolver: $this)->emit(Signal::RULES_ADDED);
            return $this;
        }

        /**
         * Searches for an applicable target for a URL and method.
         * @param string $url The URL.
         * @param string $method The HTTP method.
         * @param array $steps The steps.
         * @return RoutingResult The result of the process that will contain a target or error.
         */
        public function findRule (string $url, string $method, array $steps = []) : RoutingResult {
            $this->prepareRegistry();

            $requestUri = $this->parser->parsePattern($url)->getUri();

            foreach ($this->rules as $rule) {
                $name = $rule->getName();
                $compiledRoute = $this->registry->getCompiledRoute($name);
                $target = $this->registry->getTargetMap($name)?->getTarget($method);

                # Skip the iteration as this method has no target.
                if (!isset($target)) continue;
        
                # Skip the iteration as there's no match.
                if (!$this->matcher->match($compiledRoute, $url, $matches, $requestUri)) continue;

                # Skip the iteration if Verix-typed parameters fail their schema constraints.
                if (!$this->validateSchemaParameters($name, $matches)) continue;

                Emitter::create()->with(rule: $rule, url: $url, method: $method, resolver: $this)->emit(Signal::MATCH_FOUND);

                return new RoutingResult($target, $matches, $steps);
            }

            foreach ($this->fallbackRules as $rule) {
                $name = $rule->getName();
                $compiledRoute = $this->registry->getCompiledRoute($name);
                $target = $this->registry->getTargetMap($name)?->getTarget($method);

                if (!isset($target)) continue;

                if (!$this->matcher->match($compiledRoute, $url, $matches, $requestUri)) continue;

                if (!$this->validateSchemaParameters($name, $matches)) continue;

                Emitter::create()->with(rule: $rule, url: $url, method: $method, resolver: $this)->emit(Signal::FALLBACK_MATCHED);

                return new RoutingResult($target, $matches, $steps);
            }

            Emitter::create()->with(url: $url, method: $method, ruleType: static::getRuleType(), resolver: $this)->emit(Signal::MATCH_NOT_FOUND);

            return RoutingResult::withError(RoutingError::NOT_FOUND, $steps);
        }

        /**
         * Gets the path of a resolver.
         * @return RoutingPath The path of the resolver.
         */
        public function getPath () : RoutingPath {
            return $this->path;
        }

        /**
         * Clears all registered and fallback rules and invalidates the registry.
         *
         * Use this method in long-lived process environments (Swoole, RoadRunner, ReactPHP)
         * when the same resolver instance must be reconfigured between requests. After calling
         * reset() the resolver is in an empty, ready-to-configure state equivalent to a
         * freshly constructed instance.
         * @return static The resolver.
         */
        public function reset () : static {
            $this->rules = [];
            $this->fallbackRules = [];
            $this->registryRefreshed = false;
            return $this;
        }

        /**
         * Resolves the rule found for a URL and method until no more operrations are possible.
         * @param string $url A URL.
         * @param string $method An HTTP method.
         * @param array $steps The steps.
         * @return RoutingResult The result of the process that will contain a rewrite target or error.
         */
        public function resolve (string $url, string $method, array $steps = []) : RoutingResult {
            return $this->prepareRegistry()->findRule($url, $method, $steps);
        }
    }
?>