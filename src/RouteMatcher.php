<?php
    /**
     * Project Name:    Wingman Nexus - Route Matcher
     * Created by:      Angel Politis
     * Creation Date:   Nov 10 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Enums\RouteQueryRequirement;
    use Wingman\Nexus\Objects\ArgumentList;
    use Wingman\Nexus\Objects\ArgumentSet;
    use Wingman\Nexus\Objects\CompiledRoute;
    use Wingman\Nexus\Objects\URI;
    use Wingman\Nexus\Parsers\PatternParser;

    /**
     * Represents a route matcher.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteMatcher {
        /**
         * The regex used to detect variable placeholders and wildcards.
         * @var string
         */
        #[Configurable("nexus.regex.variable")]
        protected string $variableRegex = PatternParser::DEFAULT_VARIABLE_REGEX;

        /**
         * The type name used when a variable has no explicit type annotation.
         * @var string
         */
        #[Configurable("nexus.variableDefaultType")]
        protected string $variableDefaultType = PatternParser::DEFAULT_VARIABLE_DEFAULT_TYPE;

        /**
         * Creates a new route matcher.
         * @param array|Configuration $config A flat dot-notation array or Cortex `Configuration`
         *   instance used to override default matching behaviour.
         */
        public function __construct (array|Configuration $config = []) {
            Configuration::hydrate($this, $config);
        }

        /**
         * Matches a specific URI component against a pattern.
         * @param string $pattern A regex pattern.
         * @param string|null $value The value to match.
         * @param ArgumentSet|null $matches The matches — populated by the method.
         * @return bool Whether the component matches the pattern.
         */
        protected function matchComponent (string $pattern, ?string $value, ?ArgumentSet &$matches) : bool {
            if ($value === null) $value = "";
            
            if (preg_match($pattern, $value, $m)) {
                $matches = $this->transformPregMatches($m);
                return true;
            }
            
            return false;
        }

        /**
         * Transforms an array of matches as it comes out of preg_match so that named and unnamed parameters
         * are placed in different subarrays and there's also a subarray for all parameters in the order they
         * were encountered.
         * @param array $matches An array of preg_match matches.
         * @return ArgumentSet The matches divided accordingly.
         */
        protected function transformPregMatches (array $matches) : ArgumentSet {
            $named = [];
            $unnamed = [];
            $indexed = [];
        
            # Filter out the full match (index 0).
            $filteredMatches = array_filter($matches, fn ($k) => $k !== 0, ARRAY_FILTER_USE_KEY);
        
            foreach ($filteredMatches as $key => $value) {
                /**
                 * The Fix: Only split by slash if it's a Wildcard N (indexed/unnamed)
                 * or specifically named 'path'. 
                 * Named types like {target:string+} must remain a single string.
                 */
                if (!is_string($key) || $key === "path") {
                    $parts = preg_split('/(?<!\\\\)\//', $value);
                    $parts = array_map([Helper::class, "coerce"], $parts);
                    $finalValue = sizeof($parts) > 1 ? $parts : $parts[0];
                }
                else $finalValue = Helper::coerce($value);
        
                if (is_string($key)) {
                    $named[$key] = $finalValue;
                    $indexed[] = $finalValue;
                }
                else {
                    $hasNamedEquivalent = false;
                    foreach ($matches as $k => $v) {
                        if (is_string($k) && $v === $value) {
                            $hasNamedEquivalent = true;
                            break;
                        }
                    }
        
                    if (!$hasNamedEquivalent) {
                        $unnamed[] = $finalValue;
                        $indexed[] = $finalValue;
                    }
                }
            }
        
            return new ArgumentSet($named, $unnamed, $indexed);
        }

        /**
         * Matches a full URI (Scheme, Host, Port, Path, Query) against a route.
         * @param CompiledRoute $route The compiled route definition.
         * @param string $uri The full incoming URI (e.g., from $_SERVER).
         * @param array &$matches The resulting ArgumentSets — populated by the method.
         * @return bool Whether the URL matches a route.
         */
        public function match (CompiledRoute $route, string $uri, ?ArgumentList &$matches = null, ?URI $requestUri = null) : bool {
            $results = [];
        
            # Stage 0: Parse incoming URI (only if not already provided by the caller).
            if ($requestUri === null) {
                $parser = new PatternParser($this->variableRegex, $this->variableDefaultType);
                $requestUri = $parser->parsePattern($uri)->getUri();
            }
        
            # Stage 1: Scheme
            if (($schemePattern = $route->getSchemePattern()) && !$this->matchComponent($schemePattern, $requestUri->getScheme(), $results["scheme"])) {
                return false;
            }
        
            # Stage 2: Authority
            if (($usernamePattern = $route->getUsernamePattern()) && !$this->matchComponent($usernamePattern, $requestUri->getUsername(), $results["username"])) {
                return false;
            }
            if (($passwordPattern = $route->getPasswordPattern()) && !$this->matchComponent($passwordPattern, $requestUri->getPassword(), $results["password"])) {
                return false;
            }
            if (($hostPattern = $route->getHostPattern()) && !$this->matchComponent($hostPattern, $requestUri->getHost(), $results["host"])) {
                return false;
            }
            if (($portPattern = $route->getPortPattern()) && !$this->matchComponent($portPattern, (string) $requestUri->getPort(), $results["port"])) {
                return false;
            }
        
            # Stage 3: Path
            if (!$this->matchPath($route, $requestUri->getPath() ?? "", $results["path"])) {
                return false;
            }
        
            # Stage 4: Query
            if (!$this->matchQuery($route, "?" . ($requestUri->getQuery() ?? ""), $results["query"])) {
                return false;
            }
        
            # Stage 5: Fragment
            $requestFragment = $requestUri->getFragment();
            $fragmentSet = null;

            # 5a. If the route has a specific fragment pattern, match it.
            if (isset($route->fragmentPattern) && $route->fragmentPattern) {
                $this->matchComponent($route->fragmentPattern, $requestFragment, $fragmentSet);
            }

            # 5b. If we have a request fragment but no matches yet (or it's static),
            # capture it as an 'extra' so the Generator can see it.
            if ($requestFragment !== null) {
                if (!$fragmentSet) {
                    $fragmentSet = new ArgumentSet([], [], [], ["#" => $requestFragment]);
                }
                else $fragmentSet->extra["#"] = $requestFragment;
            }

            $results["fragment"] = $fragmentSet;
        
            $matches = new ArgumentList($results);
            return true;
        }

        /**
         * Matches a URL against the path of a route and extracts the values of any parameters.
         * @param string $url A URL.
         * @param array &$matches The matches — populated by the method.
         * @return bool Whether the URL matches the path of a route.
         */
        public function matchPath (CompiledRoute $route, string $url, ?ArgumentSet &$matches = null) : bool {
            $url = Helper::normaliseUrl($url);
            list($path) = preg_split('/(?<!\\\\)\\?/', $url, 2);            
            $result = preg_match($route->getPathPattern(), $path, $m);
            if ($result) {
                $matches = $this->transformPregMatches($m);
            }
            return $result;
        }

        /**
         * Matches a URL against the query of a route and extracts the values of any parameters.
         * @param string $url A URL.
         * @param array &$matches The matches — populated by the method.
         * @return bool Whether the URL matches the query of a route.
         */
        public function matchQuery (CompiledRoute $route, string $url, ?ArgumentSet &$matches = null): bool {
            $query = preg_split('/(?<!\\\\)\\?/', $url, 2)[1] ?? "";
            $req = $route->getQueryRequirement();
        
            if ($req == RouteQueryRequirement::FORBIDDEN && !empty($query)) return false;
            elseif ($req == RouteQueryRequirement::REQUIRED && empty($query)) return false;
        
            parse_str($query, $parsedResult);
        
            $queryMatches = ["named" => [], "unnamed" => [], "indexed" => []];
            $extraParams = [];
            $queryPatterns = $route->getQueryPatterns();
        
            foreach ($parsedResult as $key => $value) {
                $matched = false;
                $currentPair = "$key=$value";
        
                foreach ($queryPatterns as $pattern) {
                    if (preg_match("/^$pattern$/u", $currentPair, $m)) {
                        $transformed = $this->transformPregMatches($m);
                        
                        $queryMatches["named"] = array_merge($queryMatches["named"], $transformed->named);
                        $queryMatches["unnamed"] = array_merge($queryMatches["unnamed"], $transformed->unnamed);
                        $queryMatches["indexed"] = array_merge($queryMatches["indexed"], $transformed->indexed);
                        
                        $matched = true;
                        break;
                    }
                }
        
                if (!$matched) {
                    $extraParams[$key] = $value;
                }
            }
        
            $matches = new ArgumentSet(
                $queryMatches["named"], 
                $queryMatches["unnamed"], 
                $queryMatches["indexed"], 
                $extraParams
            );
        
            foreach ($route->getQuerySegments() as $segment) {
                if ($segment->isStatic()) {
                    parse_str($segment->getRaw(), $staticCheck);
                    $sKey = key($staticCheck);
                    if (!isset($parsedResult[$sKey]) || $parsedResult[$sKey] !== $staticCheck[$sKey]) {
                        return false;
                    }
                    continue;
                }
        
                foreach ($segment->getParameters() as $p) {
                    if (!$p->isOptional() && !isset($matches->named[$p->getName()])) {
                        return false;
                    }
                }
            }
        
            return true;
        }
    }
?>