<?php
    /**
     * Project Name:    Wingman Nexus - Cache Manager
     * Created by:      Angel Politis
     * Creation Date:   Nov 11 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Caching namespace.
    namespace Wingman\Nexus\Caching;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Interfaces\NexusException;
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Exceptions\DuplicateRouteException;
    use Wingman\Nexus\Objects\GroupedCallable;
    use Wingman\Nexus\Parsers\PatternParser;
    use Wingman\Nexus\Parsers\RedirectTargetParser;
    use Wingman\Nexus\Parsers\RewriteTargetParser;
    use Wingman\Nexus\Parsers\RouteTargetParser;
    use Wingman\Nexus\Objects\TargetMap;
    use Wingman\Nexus\RouteCompiler;
    use Wingman\Nexus\TypeRegistry;

    /**
     * Responsible for managing the caching of compiled routes, route definitions and target maps.
     * @package Wingman\Nexus\Caching
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CacheManager {
        /**
         * Maps short-form keys to their full dotted-notation equivalents for use in the `$map` parameter
         * of `Configuration::hydrate()`.
         * @var array<string, string>
         */
        public const array KEY_MAP = [
            # Canonical cache short keys.
            "cachingEnabled" => "nexus.cache.enabled",
            "cacheDir" => "nexus.locations.cache",
            "fileExtension" => "nexus.cache.fileExtension",
            "compiledFile" => "nexus.cache.compiledFile",
            "definitionsFile" => "nexus.cache.definitionsFile",
            "targetsFile" => "nexus.cache.targetsFile",

            # Parser-related options consumed by cache compilation.
            "variableRegex" => "nexus.variableRegex",
            "variableDefaultType" => "nexus.variableDefaultType"
        ];

        /**
         * The filenames of cache files.
         * @var array{compiled: string, definitions: string, targets: string}
         */
        protected array $cacheFiles = [];

        /**
         * The cacher used by a cache manager.
         * @var Cacher
         */
        protected Cacher $cacher;

        /**
         * The route compiler used by a cache manager.
         * @var RouteCompiler
         */
        protected RouteCompiler $compiler;

        /**
         * The original configuration passed to the constructor, forwarded to target parsers for self-hydration.
         * @var array|Configuration
         */
        protected array|Configuration $config = [];

        /**
         * The file extension appended to every cache filename.
         * @var string
         */
        #[Configurable("nexus.cache.fileExtension")]
        protected string $cacheFileExtension = 'cache';

        /**
         * The base filename (without extension) for the compiled-routes cache file.
         * @var string
         */
        #[Configurable("nexus.cache.compiledFile")]
        protected string $compiledBasename = 'compiled';

        /**
         * The base filename (without extension) for the route-definitions cache file.
         * @var string
         */
        #[Configurable("nexus.cache.definitionsFile")]
        protected string $definitionsBasename = 'definitions';

        /**
         * The base filename (without extension) for the target-maps cache file.
         * @var string
         */
        #[Configurable("nexus.cache.targetsFile")]
        protected string $targetsBasename = 'targets';

        /**
         * The filesystem path of the directory where cache files are stored.
         * @var string
         */
        #[Configurable("nexus.locations.cache")]
        protected string $cacheDir = '';

        /**
          * Whether writing compiled data to the cache is enabled.
         * @var bool
         */
        #[Configurable("nexus.cache.enabled")]
        protected bool $cacheEnabled = false;

        /**
         * The regex used to detect variable placeholders and wildcards (forwarded to `PatternParser`).
         * @var string
         */
        #[Configurable("nexus.variableRegex")]
        protected string $variableRegex = PatternParser::DEFAULT_VARIABLE_REGEX;

        /**
         * The type name used when a variable has no explicit type annotation (forwarded to `PatternParser`).
         * @var string
         */
        #[Configurable("nexus.variableDefaultType")]
        protected string $variableDefaultType = PatternParser::DEFAULT_VARIABLE_DEFAULT_TYPE;

        /**
         * Creates a new cache manager.
         * @param array|Configuration $config A flat dot-notation array or Cortex `Configuration`
         *   instance used to configure the cache manager and the underlying compiler.
         * @param TypeRegistry|null $types An optional pre-built type registry forwarded to the compiler.
         */
        public function __construct (array|Configuration $config = [], ?TypeRegistry $types = null) {
            $this->cacheDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . "cache";
            $this->config = $config;
            Configuration::hydrate($this, $config, static::KEY_MAP);

            $suffix = !empty($this->cacheFileExtension) ? '.' . $this->cacheFileExtension : '';

            $this->cacheFiles = [
                "compiled" => $this->compiledBasename . $suffix,
                "definitions" => $this->definitionsBasename . $suffix,
                "targets" => $this->targetsBasename . $suffix,
            ];

            $this->cacher   = new Cacher($this->cacheDir);
            $this->compiler = new RouteCompiler($config, $types);
        }

        /**
         * Gets the location of a cache file that corresponds to the specified rule and file type.
         * @param RuleType $ruleType The rule type.
         * @param "compiled"|"definitions"|"targets" $fileType The file type.
         * @return string The relative path of the file with respect to the root of the cacher.
         */
        protected function getFile (RuleType $ruleType, string $fileType) : string {
            $ruleTypes = ["redirects", "rewrites", "routes"];
            return $ruleTypes[$ruleType->value]. DIRECTORY_SEPARATOR . $this->cacheFiles[$fileType];
        }

        /**
         * Creates a cache with specified data for a file.
         * @param string $file The file the created cache will be matched with.
         * @param array $data The data of the cache file.
         */
        public function cache (string $file, array $data) : void {
            $fileId = md5($file);

            $cacheFile = "files/$fileId.cache";
            
            $fingerprint = $this->cacher->generateFingerprint([$file]);

            $this->cacher->write($cacheFile, $data, compact("fingerprint"));
            Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_WRITTEN);
        }

        /**
         * Fetches the data of a file from the cache if there's one, otherwise `null`.
         * @param string $file The file whose cache file to retrieve.
         * @return array|null The data of the cache or `null` if there's no cache or it's stale.
         */
        public function fetchFromCache (string $file) : ?array {
            $fileId = md5($file);

            $cacheFile = "files/$fileId.cache";
            
            $fingerprint = $this->cacher->generateFingerprint([$file]);

            try {
                $cache = $this->cacher->read($cacheFile);
            }
            catch (NexusException $e) {
                Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_MISS);
                return null;
            }

            $sourceFingerprint = $cache->getMetadata()["fingerprint"] ?? null;

            if ($sourceFingerprint === $fingerprint) {
                Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_HIT);
                return $cache->getContent();
            }

            Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_MISS);
            return null;
        }

        /**
         * Checks whether the cache for a specified type of rules is valid.
         * @param RuleType $ruleType The type of rules.
         * @return bool Whether all cache files exist.
         */
        public function isCacheValid (RuleType $ruleType) : bool {
            return $this->cacher->exists($this->getFile($ruleType, "compiled"))
                && $this->cacher->exists($this->getFile($ruleType, "definitions"))
                && $this->cacher->exists($this->getFile($ruleType, "targets"));
        }

        /**
         * Loads the processed versions of the specified rules, whether by fetching them from the cache or rebuilding them.
         * @param RuleType $ruleType The type of rules to load.
         * @param array $rules The rules.
         * @return array{
         *      compiled: array{byName : array<string, CompiledRoute>, byPattern : array<string, CompiledRoute>},
         *      definitions: array{byName : array<string, RouteDefinition>, byPattern : array<string, RouteDefinition>},
         *      targets: array{byName : array<string, TargetMap>, byPattern : array<string, TargetMap>}
         * } The `compiled`, `definitions` and `targets` of the rules.
         */
        public function load (RuleType $ruleType, array $rules) : array {
            if ($this->isCacheValid($ruleType)) {
                try {
                    $definitions = $this->cacher->read($this->getFile($ruleType, "definitions"));
                    $compiled = $this->cacher->read($this->getFile($ruleType, "compiled"));
                    $targets = $this->cacher->read($this->getFile($ruleType, "targets"));

                    Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_HIT);

                    return [
                        "compiled" => $compiled->getContent(),
                        "definitions" => $definitions->getContent(),
                        "targets" => $targets->getContent()
                    ];
                }
                catch (NexusException) {
                    Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_MISS);
                    return $this->buildCache($ruleType, $rules);
                }
            }

            Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_MISS);

            return $this->buildCache($ruleType, $rules);
        }

        /**
         * Builds the cache for the rules of a specified type.
         * @param RuleType $ruleType The type of rules whose cache to build.
         * @param array $rules The rules.
         * @return array{
         *      compiled: array{byName : array<string, CompiledRoute>, byPattern : array<string, CompiledRoute>},
         *      definitions: array{byName : array<string, RouteDefinition>, byPattern : array<string, RouteDefinition>},
         *      targets: array{byName : array<string, TargetMap>, byPattern : array<string, TargetMap>}
         * } The `compiled`, `definitions` and `targets` of the rules.
         */
        public function buildCache (RuleType $ruleType, array $rules) : array {
            $patternParser = new PatternParser($this->variableRegex, $this->variableDefaultType);
            $targetParser = match ($ruleType) {
                RuleType::REDIRECT => new RedirectTargetParser($this->config),
                RuleType::REWRITE  => new RewriteTargetParser($this->config),
                RuleType::ROUTE    => new RouteTargetParser($this->config)
            };
            
            $definitions = [
                "byName" => [],
                "byPattern" => []
            ];
            $compiled = [
                "byName" => [],
                "byPattern" => []
            ];
            $targets = [
                "byName" => [],
                "byPattern" => []
            ];

            foreach ($rules as $rule) {
                $name = $rule->getName();
                $pattern = $rule->getPattern();
                $target = $rule->getTarget();

                $definition = $patternParser->parsePattern($pattern);

                if (!is_null($target)) {
                    if (!is_callable($target) && !($target instanceof GroupedCallable)) {
                        $map = is_string($target)
                            ? $targetParser->parseExpression($target)
                            : $targetParser->parseMap($target);
                    }
                    else $map = null;
                }
                else $map = new TargetMap();

                $compiledRoute = $this->compiler->compile($definition);

                if (array_key_exists($name, $compiled["byName"])) {
                    throw new DuplicateRouteException("A route named '{$name}' has already been registered.");
                }

                Emitter::create()->with(name: $name, pattern: $pattern, ruleType: $ruleType, manager: $this)->emit(Signal::ROUTE_COMPILED);

                $compiled["byName"][$name] = $compiledRoute;
                $definitions["byName"][$name] = $definition;
                $targets["byName"][$name] = $map;
                $compiled["byPattern"][$pattern] = $compiledRoute;
                $definitions["byPattern"][$pattern] = $definition;
                $targets["byPattern"][$pattern] = $map;
            }

            if ($this->cacheEnabled) {
                $this->cacher->write($this->getFile($ruleType, "compiled"), $compiled);
                $this->cacher->write($this->getFile($ruleType, "definitions"), $definitions);
                $this->cacher->write($this->getFile($ruleType, "targets"), $targets);

                Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_WRITTEN);
            }

            return compact("compiled", "definitions", "targets");
        }
    }
?>