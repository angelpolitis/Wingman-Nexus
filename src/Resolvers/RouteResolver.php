<?php
    /**
     * Project Name:    Wingman Nexus - Route Resolver
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
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Enums\HttpMethod;
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Exceptions\RouteNotFoundException;
    use Wingman\Nexus\Objects\ArgumentList;
    use Wingman\Nexus\Objects\ArgumentSet;
    use Wingman\Nexus\Objects\GroupedCallable;
    use Wingman\Nexus\Objects\RoutingResult;
    use Wingman\Nexus\Objects\RouteSnapshot;
    use Wingman\Nexus\Objects\TargetMap;
    use Wingman\Nexus\Objects\URI;
    use Wingman\Nexus\Targets\AnonymousTarget;
    use Wingman\Nexus\Targets\OptionsTarget;
    use Wingman\Nexus\Targets\RouteTarget;
    use Wingman\Nexus\UrlGenerator;

    /**
     * Represents a route resolver.
     * @package Wingman\Nexus\Resolvers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteResolver extends Resolver {
        /**
         * Collects the expanded allowed methods and response headers from a target map.
         *
         * If the map contains a wildcard `'*'` method entry, it is substituted with all
         * routable HTTP methods. Headers are collected from every {@see RouteTarget} in the map.
         * @param TargetMap $map The target map.
         * @return array{0: string[], 1: array} The allowed methods and aggregated headers.
         */
        private function collectFromTargetMap (TargetMap $map) : array {
            $methods = $map->getMethods();
            $expanded = in_array('*', $methods, true)
                ? HttpMethod::getRoutable()
                : $methods;

            $headers = [];

            foreach ($expanded as $m) {
                $target = $map->getTarget($m);

                if ($target instanceof RouteTarget) {
                    $headers = array_merge($headers, $target->getHeaders());
                }
            }

            return [$expanded, $headers];
        }

        /**
         * Derives the list of allowed HTTP methods from a raw route target (non-TargetMap format).
         *
         * Array targets use their keys as method names; a `'*'` wildcard key expands to all routable
         * methods. Callable targets imply every routable method. Any other value yields an empty list.
         * @param mixed $rawTarget The raw route target.
         * @return string[] The allowed methods.
         */
        private function expandRawTargetMethods (mixed $rawTarget) : array {
            if (is_array($rawTarget)) {
                $keys = array_map("strtoupper", array_keys($rawTarget));
                return in_array('*', $keys, true) ? HttpMethod::getRoutable() : $keys;
            }

            if (is_callable($rawTarget)) {
                return HttpMethod::getRoutable();
            }

            return [];
        }

        /**
         * Iterates a collection of rules looking for the first pattern match for the given URL and method.
         * Returns `null` when no rule's pattern matches, allowing the caller to try the next collection.
         * Returns a {@see RoutingResult} (success or method-not-allowed) as soon as a pattern match is found,
         * emitting the supplied signal on success.
         *
         * When `$contentType` is non-empty and a matched {@see RouteTarget} declares a non-empty
         * `contentTypes` list, the route is skipped unless the supplied content type appears in that list.
         * Routes with an empty `contentTypes` list always accept any content type.
         * @param array $rules The rule collection to search.
         * @param string $url The URL.
         * @param string $method The HTTP method.
         * @param URI $requestUri The pre-parsed request URI used by the matcher.
         * @param Signal $matchSignal The signal to emit when a target is resolved successfully.
         * @param string $contentType The request content type used to filter routes that declare content-type constraints.
         * @return RoutingResult|null The result, or null if no rule matched the URL pattern.
         */
        private function findRuleInCollection (
            array $rules,
            string $url,
            string $method,
            URI $requestUri,
            Signal $matchSignal,
            string $contentType = ""
        ) : RoutingResult|null {
            $methodNotAllowed = false;

            /** @var Route $route */
            foreach ($rules as $route) {
                $name = $route->getName();
                $compiledRoute = $this->registry->getCompiledRoute($name);
                $target = $this->registry->getTargetMap($name)?->getTarget($method);

                if (!$this->matcher->match($compiledRoute, $url, $matches, $requestUri)) continue;

                if (!$this->validateSchemaParameters($name, $matches)) continue;

                if (!$target) {
                    $rawTarget = $route->getTarget();
                    $middleware = [];
                    $tags = [];
                    $headers = [];

                    if ($rawTarget instanceof GroupedCallable) {
                        $middleware = $rawTarget->getMiddleware();
                        $tags = $rawTarget->getTags();
                        $headers = $rawTarget->getHeaders();
                        $rawTarget = $rawTarget->getCallable();
                    }

                    if (is_callable($rawTarget)) $rawTarget = [$method => $rawTarget];

                    $action = $rawTarget[$method] ?? $rawTarget['*'] ?? null;

                    if (!$action) {
                        $methodNotAllowed = true;
                        continue;
                    }

                    Emitter::create()->with(rule: $route, url: $url, method: $method, resolver: $this)->emit($matchSignal);

                    return new RoutingResult(new AnonymousTarget($action, $method, $middleware, $tags, $headers), $matches);
                }

                if ($contentType !== "" && $target instanceof RouteTarget && !empty($target->getContentTypes()) && !in_array($contentType, $target->getContentTypes(), true)) {
                    continue;
                }

                Emitter::create()->with(rule: $route, url: $url, method: $method, resolver: $this)->emit($matchSignal);

                return new RoutingResult($target, $matches);
            }

            if ($methodNotAllowed) {
                Emitter::create()->with(url: $url, method: $method, resolver: $this)->emit(Signal::METHOD_NOT_ALLOWED);
                return RoutingResult::withError(RoutingError::METHOD_NOT_ALLOWED);
            }

            return null;
        }

        /**
         * Gets the associated rule type for a resolver.
         * @return RuleType The rule type.
         */
        protected static function getRuleType () : RuleType {
            return RuleType::ROUTE;
        }

        /**
         * Resolves an HTTP OPTIONS request by collecting all available methods at the given URL.
         *
         * Scans every registered route whose compiled pattern matches the URL (ignoring HTTP method),
         * aggregates the allowed methods from each matching target map, and merges any response headers
         * defined on individual route targets. Always includes `OPTIONS` in the returned method list.
         * Emits {@see Signal::OPTIONS_RESOLVED} on success, or returns a {@see RoutingError::NOT_FOUND}
         * result when the URL does not match any registered pattern.
         * @param string $url The URL.
         * @param array $steps The preceding routing steps.
         * @return RoutingResult The result containing an {@see OptionsTarget} or a not-found error.
         */
        protected function resolveOptions (string $url, array $steps = []) : RoutingResult {
            $requestUri = $this->parser->parsePattern($url)->getUri();
            $allowedMethods = [];
            $aggregatedHeaders = [];

            /** @var Route */
            foreach ($this->rules as $route) {
                $name = $route->getName();
                $compiledRoute = $this->registry->getCompiledRoute($name);

                if (!$this->matcher->match($compiledRoute, $url, $unusedMatches, $requestUri)) continue;

                $targetMap = $this->registry->getTargetMap($name);

                [$methods, $headers] = $targetMap !== null
                    ? $this->collectFromTargetMap($targetMap)
                    : [$this->expandRawTargetMethods($route->getTarget()), []];

                $allowedMethods = array_merge($allowedMethods, $methods);
                $aggregatedHeaders = array_merge($aggregatedHeaders, $headers);
            }

            if (empty($allowedMethods)) {
                Emitter::create()->with(
                    url: $url,
                    method: HttpMethod::OPTIONS,
                    ruleType: static::getRuleType(),
                    resolver: $this
                )->emit(Signal::MATCH_NOT_FOUND);
                return RoutingResult::withError(RoutingError::NOT_FOUND, $steps);
            }

            $allowedMethods = array_values(array_unique(array_merge($allowedMethods, [HttpMethod::OPTIONS->value])));
            $target = new OptionsTarget($allowedMethods, $aggregatedHeaders);

            Emitter::create()->with(
                url: $url,
                methods: $allowedMethods,
                target: $target,
                resolver: $this
            )->emit(Signal::OPTIONS_RESOLVED);

            return new RoutingResult($target, new ArgumentList(), $steps);
        }

        /**
         * Searches for an applicable route for a URL and method.
         *
         * When `$contentType` is non-empty, only routes whose declared content-type list contains
         * the supplied value are considered a match. Routes with no declared content types are
         * never filtered and will match any content type.
         * @param string $url The URL.
         * @param string $method The HTTP method.
         * @param array $steps The steps.
         * @param string $contentType The request content type used to filter routes that declare content-type constraints.
         * @return RoutingResult The result of the process that will contain a target or error.
         */
        public function findRule (string $url, string $method, array $steps = [], string $contentType = "") : RoutingResult {
            if (HttpMethod::resolve($method) === HttpMethod::OPTIONS) {
                return $this->resolveOptions($url, $steps);
            }

            $requestUri = $this->parser->parsePattern($url)->getUri();
            $isHead = strtoupper($method) === HttpMethod::HEAD->value;

            $primary = $this->findRuleInCollection($this->rules, $url, $method, $requestUri, Signal::MATCH_FOUND, $contentType);

            if ($primary !== null && !$primary->hasError()) return $primary;

            $fallback = $this->findRuleInCollection($this->fallbackRules, $url, $method, $requestUri, Signal::FALLBACK_MATCHED, $contentType);

            if ($fallback !== null && !$fallback->hasError()) return $fallback;

            # HEAD auto-derivation: when no explicit HEAD handler is registered, resolve via GET.
            if ($isHead) {
                $headPrimary = $this->findRuleInCollection($this->rules, $url, HttpMethod::GET->value, $requestUri, Signal::MATCH_FOUND, $contentType);

                if ($headPrimary !== null && !$headPrimary->hasError()) return $headPrimary;

                $headFallback = $this->findRuleInCollection($this->fallbackRules, $url, HttpMethod::GET->value, $requestUri, Signal::FALLBACK_MATCHED, $contentType);

                if ($headFallback !== null && !$headFallback->hasError()) return $headFallback;
            }

            $result = $primary ?? $fallback;

            if ($result !== null) return $result;

            Emitter::create()->with(url: $url, method: $method, ruleType: static::getRuleType(), resolver: $this)->emit(Signal::MATCH_NOT_FOUND);

            return RoutingResult::withError(RoutingError::NOT_FOUND);
        }

        /**
         * Resolves a route for a URL and method, optionally filtering by content type.
         *
         * Extends the base {@see Resolver::resolve()} to thread `$contentType` through to
         * {@see findRule()}, allowing callers to perform content-type-aware routing without
         * bypassing the registry preparation step.
         * @param string $url The URL.
         * @param string $method The HTTP method.
         * @param array $steps The preceding routing steps.
         * @param string $contentType The request content type used to filter routes that declare content-type constraints.
         * @return RoutingResult The result of the process that will contain a target or error.
         */
        public function resolve (string $url, string $method, array $steps = [], string $contentType = "") : RoutingResult {
            return $this->prepareRegistry()->findRule($url, $method, $steps, $contentType);
        }

        /**
         * Separates supplied parameters into those that fill named route tokens (substituted
         * into the URL) and any remainder, which is appended to the query string when
         * $preserveQueryExtras is true.
         * @param string $name The name of the route.
         * @param array $params A flat associative array of parameter values.
         * @param bool $preserveQueryExtras Whether to append unrecognised parameters to the query string.
         * @return string The generated URL.
         * @throws RouteNotFoundException If no route with the given name is registered.
         */
        public function generateUrl (string $name, array $params = [], bool $preserveQueryExtras = true) : string {
            $this->prepareRegistry();

            $definition = $this->registry->getDefinition($name);

            if ($definition === null) {
                throw new RouteNotFoundException("No route named '{$name}' is registered.");
            }

            # Separate known route parameter values from extras that become appended query args.
            $routeParamNames = array_map(fn ($p) => $p->getName(), $definition->getParameters());
            $routeNamed = array_intersect_key($params, array_flip($routeParamNames));
            $extra = array_diff_key($params, array_flip($routeParamNames));

            $shared = new ArgumentSet($routeNamed);
            $querySet = new ArgumentSet($routeNamed, [], [], $extra);

            $arguments = new ArgumentList([
                "scheme"   => $shared,
                "username" => $shared,
                "password" => $shared,
                "host"     => $shared,
                "port"     => $shared,
                "path"     => $shared,
                "query"    => $querySet,
                "fragment" => $shared,
            ]);

            $generator = new UrlGenerator($definition, $this->wildcard1, $this->wildcardN, $this->types);

            return $generator->generate($arguments, $preserveQueryExtras);
        }

        /**
         * Returns a read-only snapshot of every registered route, including fallback routes.
         *
         * The result is suitable for debugging (`var_dump`), console tooling, and
         * documentation generators. Compile-time information — typed parameters,
         * allowed methods, and per-method target details — is fully resolved before
         * the snapshots are returned.
         * @return RouteSnapshot[] The snapshots, in registration order: normal routes first, then fallbacks.
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
                    $targetMap = $this->registry->getTargetMap($name);

                    $parameters = array_map(fn ($p) => [
                        'name' => $p->getName(),
                        'type' => $p->getType(),
                        'location' => $p->getLocation(),
                        'optional' => $p->isOptional(),
                    ], $definition?->getParameters() ?? []);

                    $methods = [];
                    $targets = [];

                    if ($targetMap !== null) {
                        $rawMethods = $targetMap->getMethods();
                        $methods = in_array('*', $rawMethods, true)
                            ? HttpMethod::getRoutable()
                            : $rawMethods;

                        foreach ($methods as $m) {
                            $target = $targetMap->getTarget($m);

                            if ($target instanceof RouteTarget) {
                                $targets[$m] = [
                                    'class' => $target->class,
                                    'action' => $target->action,
                                    'middleware' => $target->middleware,
                                    'tags' => $target->tags,
                                    'headers' => $target->headers,
                                    'contentTypes' => $target->contentTypes,
                                ];
                            }
                        }
                    }
                    else {
                        $rawTarget = $rule->getTarget();

                        if (is_array($rawTarget)) {
                            $keys = array_map('strtoupper', array_keys($rawTarget));
                            $methods = in_array('*', $keys, true) ? HttpMethod::getRoutable() : $keys;
                        }
                        elseif (is_callable($rawTarget)) {
                            $methods = HttpMethod::getRoutable();
                        }
                    }

                    $snapshots[] = new RouteSnapshot($name, $rule->getPattern(), $isFallback, $parameters, $methods, $targets);
                }
            }

            return $snapshots;
        }

        /**
         * Attempts to resolve a rewrite target and method.
         * @param RoutingResult $rewriteResult The routing result following a successful rewriting operation.
         * @param string $method An HTTP method.
         * @param string $contentType The request content type used to filter routes that declare content-type constraints.
         * @return RoutingResult The result of the routing operation.
         */
        public function resolveRewriteResult (RoutingResult $rewriteResult, string $method, string $contentType = "") : RoutingResult {
            $this->prepareRegistry();

            $intermediateResults = [...$rewriteResult->getSteps(), $rewriteResult];

            /** @var RewriteTarget */
            $target = $rewriteResult->getTarget();

            $targetPath = $target->getPath();

            $definition = $this->registry->getDefinitionByPattern($targetPath);

            if (!$definition) return RoutingResult::withError(RoutingError::NOT_FOUND, $intermediateResults);

            $generator = new UrlGenerator($definition, $this->wildcard1, $this->wildcardN, $this->types);
            $url = $generator->generate($rewriteResult->getArgs(), $target->preservesQuery());

            return $this->resolve($url, $method, $intermediateResults, $contentType);
        }
    }
?>