<?php
    /**
     * Project Name:    Wingman Nexus - Rewrite Resolver
     * Created by:      Angel Politis
     * Creation Date:   Dec 02 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Resolvers namespace.
    namespace Wingman\Nexus\Resolvers;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Caching\CacheManager;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Objects\RoutingPath;
    use Wingman\Nexus\Objects\RoutingResult;
    use Wingman\Nexus\Objects\RewriteSnapshot;
    use Wingman\Nexus\Resolvers\Resolver;
    use Wingman\Nexus\Targets\RewriteTarget;
    use Wingman\Nexus\TypeRegistry;
    use Wingman\Nexus\UrlGenerator;

    /**
     * Represents a rewrite resolver.
     * @package Wingman\Nexus\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RewriteResolver extends Resolver {
        /**
         * Creates a new resolver.
         * @param CacheManager $cacheManager The cache manager.
         * @param RoutingPath $path The routing path.
         * @param array|Configuration $config The configuration.
         * @param TypeRegistry $types The type registry.
         */
        public function __construct (CacheManager $cacheManager, RoutingPath $path, array|Configuration $config, TypeRegistry $types) {
            parent::__construct($cacheManager, $path, $config, $types);
        }

        /**
         * Gets the associated rule type for a resolver.
         * @return RuleType The rule type.
         */
        protected static function getRuleType () : RuleType {
            return RuleType::REWRITE;
        }

        /**
         * Returns a read-only snapshot of every registered rewrite, including fallback rewrites.
         * The result is suitable for debugging, console tooling, and documentation generators.
         * @return RewriteSnapshot[] The snapshots, in registration order: normal rules first, then fallbacks.
         */
        public function getSnapshots () : array {
            $this->prepareRegistry();

            $snapshots = [];
            $collections = [
                [false, $this->rules],
                [true, $this->fallbackRules],
            ];

            foreach ($collections as [$isFallback, $collection]) {
                foreach ($collection as $rule) {
                    $name = $rule->getName();
                    $definition = $this->registry->getDefinition($name);

                    $parameters = array_map(fn ($p) => [
                        'name' => $p->getName(),
                        'type' => $p->getType(),
                        'location' => $p->getLocation(),
                        'optional' => $p->isOptional(),
                    ], $definition?->getParameters() ?? []);

                    /** @var RewriteTarget|null */
                    $target = $this->registry->getTargetMap($name)?->getTarget('*');

                    $snapshots[] = new RewriteSnapshot(
                        $name,
                        $rule->getPattern(),
                        $isFallback,
                        $target?->path ?? '',
                        $target?->preservesQuery ?? false,
                        $parameters
                    );
                }
            }

            return $snapshots;
        }

        /**
         * Resolves the rewrite chain for a URL and method until no more rewrites are possible.
         * @param string $url A URL.
         * @param string $method An HTTP method.
         * @param array $steps The steps.
         * @return RoutingResult The result of the process that will contain a rewrite target or error.
         */
        public function resolve (string $url, string $method, array $steps = []) : RoutingResult {
            $this->prepareRegistry();

            $results = [...$steps];
            $currentDepth = 0;
            $maxDepth = $this->maxRewriteDepth;
            $currentUrl = $url;

            $this->path->add($currentUrl, null);

            while (true) {
                # Terminate the process with an error if the maximum rewrite depth has been exceeded.
                if (!is_null($maxDepth) && $currentDepth > $maxDepth) {
                    Emitter::create()->with(depth: $currentDepth, url: $currentUrl, resolver: $this)->emit(Signal::MAX_REWRITE_DEPTH_EXCEEDED);
                    return RoutingResult::withError(RoutingError::MAX_REWRITE_DEPTH_EXCEEDED, $results);
                }

                $result = $this->findRule($currentUrl, $method, $results);

                $currentDepth++;

                if ($result->hasError()) {
                    # If the last rewrite rule produces an Not Found error and there are steps, return the last step.
                    if ($result->getError() === RoutingError::NOT_FOUND && sizeof($results) > 0) {
                        Emitter::create()->with(steps: sizeof($results), url: $currentUrl, resolver: $this)->emit(Signal::REWRITE_COMPLETED);
                        return end($results);
                    }

                    return RoutingResult::withError($result->getError(), $results);
                }

                $results[] = $result;

                /** @var RewriteTarget */
                $target = $result->getTarget();

                $targetPath = $target->getPath();

                $definition = $this->registry->getDefinitionByPattern($targetPath)
                    ?? $this->parser->parsePattern($targetPath);

                $generator = new UrlGenerator($definition, $this->wildcard1, $this->wildcardN, $this->types);
                
                $nextUrl = $generator->generate($result->getArgs(), $target->preservesQuery());

                # If the next url has been identified before, it means were are in cycle.
                if ($this->path->has($nextUrl)) {
                    Emitter::create()->with(url: $nextUrl, resolver: $this)->emit(Signal::REWRITE_CYCLE_DETECTED);
                    return RoutingResult::withError(RoutingError::REWRITE_CYCLE_IDENTIFIED, $results);
                }

                Emitter::create()->with(step: $currentDepth, fromUrl: $currentUrl, toUrl: $nextUrl, resolver: $this)->emit(Signal::REWRITE_STEP_RESOLVED);

                $this->path->add($nextUrl, $result);

                $currentUrl = $nextUrl;
            }
        }
    }
?>