<?php
    /**
     * Project Name:    Wingman Nexus - Route Compiler
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
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
    use Wingman\Nexus\Objects\CompiledRoute;
    use Wingman\Nexus\Objects\RouteDefinition;
    use Wingman\Nexus\Objects\URI;

    /**
     * Represents a route compiler.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteCompiler {
        /**
         * The symbol that matches exactly one path segment (does not cross `/`).
         * @var string
         */
        public const DEFAULT_WILDCARD_1 = '*';

        /**
         * The symbol that matches one or more path segments (crosses `/`).
         * @var string
         */
        public const DEFAULT_WILDCARD_N = '**';

        /**
         * The single-segment wildcard symbol used in compiled regex patterns.
         * @var string
         */
        #[Configurable("nexus.symbols.wildcard1")]
        protected string $wildcard1 = self::DEFAULT_WILDCARD_1;

        /**
         * The multi-segment wildcard symbol used in compiled regex patterns.
         * @var string
         */
        #[Configurable("nexus.symbols.wildcardN")]
        protected string $wildcardN = self::DEFAULT_WILDCARD_N;

        /**
         * The variable data-type definitions used to build the internal `TypeRegistry`
         * when no pre-built registry is provided.
         * @var array
         */
        #[Configurable("nexus.variableDataTypes")]
        protected array $variableDataTypes = TypeRegistry::DEFAULT_TYPES;

        /**
         * The type registry of a route compiler.
         * @var TypeRegistry
         */
        protected TypeRegistry $types;

        /**
         * Creates a new route compiler.
         * @param array|Configuration $config A flat dot-notation array or Cortex `Configuration`
         *   instance used to override default compilation behaviour.
         * @param TypeRegistry|null $types An optional pre-built type registry; when omitted a new
         *   registry is constructed from the (possibly hydrated) `$variableDataTypes` property.
         */
        public function __construct (array|Configuration $config = [], ?TypeRegistry $types = null) {
            Configuration::hydrate($this, $config);
            $this->types = $types ?? new TypeRegistry($this->variableDataTypes);
        }

        /**
         * Compiles a string template with parameters into a regex pattern.
         * @param string|null $template The template string.
         * @param array $params The parameters.
         * @param string $delimiter The delimiter for single-segment wildcards.
         */
        protected function compileString (?string $template, array $params, string $delimiter = '/') : string {
            if ($template === null || $template === "") return "";

            $lastOffset = 0;
            $patternParts = [];
            $wildcard_1 = $this->wildcard1;
            $wildcard_n = $this->wildcardN;

            foreach ($params as $param) {
                $rawTag = $param->getRaw();
                $pos = strpos($template, $rawTag, $lastOffset);

                # 1. Static text before the tag.
                $staticText = substr($template, $lastOffset, $pos - $lastOffset);
                $patternParts[] = preg_quote($staticText, '/');

                # 2. Resolve regex.
                $regex = match ($name = $param->getName()) {
                    $wildcard_1 => '([^' . preg_quote($delimiter, '/') . '\n?]+)',
                    $wildcard_n => '([^\n?]+?)',
                    default => sprintf("(?P<%s>%s)", $name, $this->types->resolve($param->getType()))
                };

                # 3. Optionality.
                $patternParts[] = $param->isOptional() ? "(?:$regex)?" : $regex;

                $lastOffset = $pos + strlen($rawTag);
            }

            $patternParts[] = preg_quote(substr($template, $lastOffset), '/');
            return implode("", $patternParts);
        }

        /**
         * Builds individual regular expressions for authority sub-components.
         * @param URI $uri A URI object.
         * @param array $buckets Pre-filtered parameter buckets.
         * @return array{username: ?string, password: ?string, host: ?string, port: ?string}
         */
        public function compileAuthority (URI $uri, array $buckets) : array {
            $patterns = [
                "username" => null,
                "password" => null,
                "host" => null,
                "port" => null
            ];

            if ($user = $uri->getUsername()) {
                $patterns["username"] = '/^' . $this->compileString($user, $buckets["username"]) . '$/u';
            }

            if ($pass = $uri->getPassword()) {
                $patterns["password"] = '/^' . $this->compileString($pass, $buckets["password"]) . '$/u';
            }

            if ($host = $uri->getHost()) {
                $patterns["host"] = '/^' . $this->compileString($host, $buckets["host"], '.') . '$/u';
            }

            if ($port = $uri->getPort()) {
                $patterns["port"] = '/^' . $this->compileString((string) $port, $buckets["port"]) . '$/u';
            }

            return $patterns;
        }

        /**
         * Builds a regular expression that matches paths that adhere to the path of a route.
         * @param string $path A path.
         * @param array $params The parameters for the path.
         * @return string The regular expression.
         */
        public function compilePath (string $path, array $params) : string {
            $wildcard_1 = $this->wildcard1;
            $wildcard_n = $this->wildcardN;

            $interSegmentRegex = '([^\\n?]+?)';
            $intraSegmentRegex = '([^/\\n?]+)';
        
            # Sort parameters by their position in the string to process them linearly
            # We can use the 'raw' tag to find positions since we know they are unique in the path
            $lastOffset = 0;
            $patternParts = [];
        
            foreach ($params as $param) {
                $rawTag = $param->getRaw();
                $pos = strpos($path, $rawTag, $lastOffset);
        
                # 1. Capture and quote the static text before the parameter.
                $staticText = substr($path, $lastOffset, $pos - $lastOffset);
                
                $hasLeadingSlash = str_ends_with($staticText, '/');
                if ($param->isOptional() && $hasLeadingSlash) {
                    $staticText = substr($staticText, 0, -1);
                }
                
                $patternParts[] = preg_quote($staticText, '/');
        
                # 2. Resolve the parameter regex.
                $regex = match ($name = $param->getName()) {
                    $wildcard_1 => $intraSegmentRegex,
                    $wildcard_n => $interSegmentRegex,
                    default => sprintf("(?P<%s>%s)", $name, $this->types->resolve($param->getType()))
                };
        
                # 3. Wrap in optional groups if necessary.
                if ($param->isOptional()) {
                    $patternParts[] = $hasLeadingSlash ? "(?:\/$regex)?" : "(?:$regex)?";
                }
                else $patternParts[] = $regex;
        
                $lastOffset = $pos + strlen($rawTag);
            }
        
            # 4. Capture and quote any remaining static text after the last parameter.
            $remaining = substr($path, $lastOffset);
            if ($remaining !== false) {
                $patternParts[] = preg_quote($remaining, '/');
            }
        
            return '/^' . implode("", $patternParts) . '$/u';
        }

        /**
         * Builds a set of regular expressions, one per segment, that match query segments that adhere to the query of a route.
         * @param QuerySegments[] $segments The segments for the query.
         * @return string[] The regular expressions.
         */
        public function compileQuery (array $segments) : array {
            $compiledSegments = [];

            foreach ($segments as $segment) {
                if ($segment->isStatic()) {
                    $compiledSegments[] = preg_quote($segment->getRaw(), '/');
                    continue;
                }

                $template = $segment->getRaw();
                $segmentIsOptional = false;

                foreach ($segment->getParameters() as $param) {
                    $partRegex = sprintf("(?P<%s>%s)", $param->getName(), $this->types->resolve($param->getType()));
                    
                    $replacement = ($param->isOptional() && $param->isPartial()) ? "(?:$partRegex)?" : $partRegex;

                    $template = str_replace($param->getRaw(), $replacement, $template);

                    if ($param->isOptional() && !$param->isPartial()) {
                        $segmentIsOptional = true;
                    }
                }

                $compiledSegments[] = $segmentIsOptional ? "(?:$template)?" : $template;
            }

            return $compiledSegments;
        }

        /**
         * Compiles a route from a definition.
         * @param RouteDefinition $routeDef The route definition.
         * @return CompiledRoute The compiled route.
         */
        public function compile (RouteDefinition $routeDef) : CompiledRoute {
            $uri = $routeDef->getUri();
            $allParams = $routeDef->getParameters();
            
            $buckets = [
                "scheme" => [], "username" => [], "password" => [], 
                "host" => [], "port" => [], "path" => [], "fragment" => []
            ];
            
            foreach ($allParams as $param) {
                $loc = $param->getLocation();
                if (isset($buckets[$loc])) {
                    $buckets[$loc][] = $param;
                }
            }
        
            # 1. Compile Scheme
            $schemePattern = $uri->getScheme() 
                ? '/^' . $this->compileString($uri->getScheme(), $buckets['scheme']) . '$/u' 
                : null;
        
            # 2. Compile Authority
            $authPatterns = $this->compileAuthority($uri, $buckets);
        
            # 3. Compile Path
            $pathPattern = $this->compilePath($uri->getPath() ?? "", $buckets['path']);
        
            # 4. Compile Query
            $queryPatterns = $this->compileQuery($routeDef->getQuerySegments());
        
            # 5. Compile Fragment
            $fragmentPattern = $uri->getFragment() 
                ? '/^' . $this->compileString($uri->getFragment(), $buckets['fragment']) . '$/u' 
                : null;
        
            return new CompiledRoute(
                $schemePattern,
                $authPatterns['username'],
                $authPatterns['password'],
                $authPatterns['host'],
                $authPatterns['port'],
                $pathPattern,
                $queryPatterns,
                $fragmentPattern,
                $allParams,
                $routeDef->getQuerySegments(),
                $routeDef->getQueryRequirement()
            );
        }
    }
?>