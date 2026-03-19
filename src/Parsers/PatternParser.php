<?php
    /**
     * Project Name:    Wingman Nexus - Pattern Parser
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Parsers namespace.
    namespace Wingman\Nexus\Parsers;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Enums\RouteQueryRequirement;
    use Wingman\Nexus\Objects\Parameter;
    use Wingman\Nexus\Objects\QuerySegment;
    use Wingman\Nexus\Objects\RouteDefinition;
    use Wingman\Nexus\Objects\URI;

    /**
     * Represents a pattern parser.
     * @package Wingman\Nexus\Parsers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class PatternParser {
        /**
         * The regex used to detect variable placeholders, wildcards, and optional segments
         * in route patterns. Used by `extractParameters()`, `extractPathParameters()`, and
         * `extractQuerySegments()` to tokenise pattern strings.
         * @var string
         */
        public const DEFAULT_VARIABLE_REGEX = '/(?<wild_n>(?<!\\\\)\*\*(?!\\\\))|(?<wild_1>(?<!\\\\)\*)|(?<wild_n_opt>(?<!\\\\)\[\*\*\](?!\\\\))|(?<wild_1_opt>(?<!\\\\)\[\*\])|(?<req>\$?\{(?<req_v>[^{}:]+)(?:\:(?<req_t>[^{}:]+))?\})|(?<opt>\$?\[(?<opt_v>[^\[\]:]+)(?:\:(?<opt_t>[^\[\]:]+))?\])/';

        /**
         * The type name assumed for variables that have no explicit type annotation.
         * @var string
         */
        public const DEFAULT_VARIABLE_DEFAULT_TYPE = "string";

        /**
         * The regex used to detect variable placeholders and wildcards.
         * @var string
         */
        protected string $variableRegex;

        /**
         * The type name used when a variable has no explicit type annotation.
         * @var string
         */
        protected string $variableDefaultType;

        /**
         * Creates a new pattern parser.
         * @param string $variableRegex The regex that matches variable placeholders and wildcards.
         * @param string $variableDefaultType The type name used when no type annotation is present.
         */
        public function __construct (string $variableRegex = self::DEFAULT_VARIABLE_REGEX, string $variableDefaultType = self::DEFAULT_VARIABLE_DEFAULT_TYPE) {
            $this->variableRegex = $variableRegex;
            $this->variableDefaultType = $variableDefaultType;
        }

        /**
         * Finds the first unbalanced slash in a pattern.
         * @param string $pattern A pattern.
         * @return int|false The position of the first unbalanced slash or `false` if none exists.
         */
        protected function findFirstUnbalancedSlash (string $pattern) : int|bool {
            $len = strlen($pattern);
            $braceLevel = 0;
            for ($i = 0; $i < $len; $i++) {
                $ch = $pattern[$i];
                if ($ch === '{') $braceLevel++;
                if ($ch === '}') $braceLevel--;
                if ($ch === '/' && $braceLevel === 0) return $i;
            }
            return false;
        }

        /**
         * Extracts parameters from a generic string component.
         * @param string $input The string to scan (host, port, etc).
         * @param string $location The URI component name (context).
         * @return Parameter[] The extracted parameters.
         */
        protected function extractParameters (string $input, string $location) : array {
            if (empty($input)) return [];

            $regex = $this->variableRegex;
            $defaultType = $this->variableDefaultType;

            if (!preg_match_all($regex, $input, $matches, PREG_SET_ORDER)) return [];

            $params = [];
            foreach ($matches as $m) {
                $rawMatch = $m[0];
                $optional = !empty($m["opt"]) || !empty($m["wild_1_opt"]) || !empty($m["wild_n_opt"]);

                $params[] = new Parameter(
                    $this->getNameFromMatch($m),
                    $this->getTypeFromMatch($m) ?: $defaultType,
                    $location,
                    $rawMatch,
                    $optional,
                    $rawMatch !== $input,
                    $location
                );
            }
            return $params;
        }

        /**
         * Extracts the name from a regex match.
         * @param array $m A regex match.
         * @return string The name.
         */
        protected function getNameFromMatch (array $m) : string {
            if (!empty($m["req_v"])) return trim($m["req_v"]);
            if (!empty($m["opt_v"])) return trim($m["opt_v"]);
            
            foreach (["wild_n", "wild_1", "wild_n_opt", "wild_1_opt"] as $group) {
                if (!empty($m[$group])) return $m[$group];
            }
            
            return "";
        }
        
        /**
         * Extracts the type from a regex match.
         * @param array $m A regex match.
         * @return string|null The type or `null` if none exists.
         */
        protected function getTypeFromMatch (array $m) : ?string {
            if (!empty($m["req_t"])) return trim($m["req_t"]);
            if (!empty($m["opt_t"])) return trim($m["opt_t"]);
            return null;
        }

        /**
         * Extracts the parameters of a path.
         * @param string $path A path.
         * @return Parameter[] The parameters of the path.
         */
        public function extractPathParameters (string $path) : array {
            $regex = $this->variableRegex;

            # We use PREG_OFFSET_CAPTURE so we know exactly where the match is in the string
            if (!preg_match_all($regex, $path, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) return [];

            $params = [];
            $defaultType = $this->variableDefaultType;
        
            foreach ($matches as $m) {
                $rawMatch = $m[0][0];
                $offset = $m[0][1];
                
                # 1. Determine the "Segment" boundaries.
                # Find the last slash before the match and the first slash after it
                $lastSlash = strrpos($path, '/', $offset - strlen($path));
                $nextSlash = strpos($path, '/', $offset);
        
                $start = ($lastSlash === false) ? 0 : $lastSlash + 1;
                $end = ($nextSlash === false) ? strlen($path) : $nextSlash;
                
                # Extract the full segment text (e.g., "prefix-{id}").
                $segment = substr($path, $start, $end - $start);
        
                # 2. Identify if it's partial.
                # It's partial if the tag (like {id}) is not the same as the segment
                $isPartial = ($rawMatch !== $segment);
        
                # 3. Handle Optionality (Mapping back from PREG_OFFSET_CAPTURE nested arrays).
                $optional = !empty($m["opt"][0]) || !empty($m["wild_1_opt"][0]) || !empty($m["wild_n_opt"][0]);
        
                $params[] = new Parameter(
                    $this->getNameFromMatch(array_map(fn($match) => $match[0], $m)),
                    $this->getTypeFromMatch(array_map(fn($match) => $match[0], $m)) ?: $defaultType,
                    "path",
                    $rawMatch,
                    $optional,
                    $isPartial,
                    "path"
                );
            }
        
            return $params;
        }

        /**
         * Extracts the segments of a query.
         * @param string $query A query.
         * @return QuerySegment[] The segments of the query.
         */
        public function extractQuerySegments (string $query) : array {
            if (empty($query)) return [];
        
            $segments = [];
            $querySegments = preg_split('/&(?!amp;)/', $query);
            $regex = $this->variableRegex;
            $defaultType = $this->variableDefaultType;
        
            foreach ($querySegments as $segmentRaw) {
                $segmentParams = [];
                $segmentParts = explode('=', $segmentRaw, 2);
        
                foreach ($segmentParts as $i => $part) {
                    if (empty($part)) continue;
        
                    if (!preg_match_all($regex, $part, $matches, PREG_SET_ORDER)) continue;

                    foreach ($matches as $m) {
                        $optional = !empty($m["opt"]) || !empty($m["wild_1_opt"]) || !empty($m["wild_n_opt"]);
                        
                        $name = $this->getNameFromMatch($m);
                        $type = $this->getTypeFromMatch($m) ?: $defaultType;
    
                        $segmentParams[] = new Parameter(
                            $name,
                            $type,
                            $i === 0 ? "key" : "value",
                            $m[0],
                            $optional,
                            $m[0] !== $part,
                            "query"
                        );
                    }
                }
                
                $segments[] = new QuerySegment($segmentRaw, $segmentParams);
            }
        
            return $segments;
        }

        /**
         * Parses a pattern.
         * @param string $pattern A pattern.
         * @return RouteDefinition A route definition.
         */
        public function parsePattern (string $pattern) : RouteDefinition {
            # 1. Decompose the pattern into a URI object.
            $uri = $this->parsePatternUrl($pattern);
        
            # 2. Extract parameters from all possible URI components.
            $params = array_merge(
                $this->extractParameters($uri->getScheme() ?? "", "scheme"),
                $this->extractParameters($uri->getUsername() ?? "", "username"),
                $this->extractParameters($uri->getPassword() ?? "", "password"),
                $this->extractParameters($uri->getHost() ?? "", "host"),
                $this->extractParameters($uri->getPort() ?? "", "port"),
                $this->extractPathParameters($uri->getPath() ?? ""),
                $this->extractParameters($uri->getFragment() ?? "", "fragment")
            );
        
            # 3. Handle Query Segments (already specialised).
            $querySegments = $this->extractQuerySegments($uri->getQuery() ?? "");
        
            # 4. Determine Query Requirement (Forbidden logic).
            $queryRequirement = RouteQueryRequirement::OPTIONAL;
            $noQueryRequired = preg_match('/(?<!\\\\)!$/', $pattern);
            if ($noQueryRequired) {
                $queryRequirement = RouteQueryRequirement::FORBIDDEN;
            }
            elseif (!empty($querySegments)) {
                foreach ($querySegments as $segment) {
                    if ($segment->isStatic() || array_filter($segment->getParameters(), fn ($p) => !$p->isOptional())) {
                        $queryRequirement = RouteQueryRequirement::REQUIRED;
                        break;
                    }
                }
            }
        
            return new RouteDefinition($pattern, $uri, $params, $querySegments, $queryRequirement);
        }

        /**
         * Parses a pattern URL into its components.
         * @param string $pattern A pattern URL.
         * @return URI The parsed URI.
         */
        public function parsePatternUrl (string $pattern) : URI {
            $scheme = null;
            $user = null;
            $pass = null;
            $host = null;
            $port = null;
            $path = null;
            $query = null;
            $fragment = null;

            # 0. Extract fragment (#...).
            if (preg_match('#^(.*?)(?:\#(.*))$#', $pattern, $m)) {
                $pattern  = $m[1];
                $fragment = $m[2];
            }

            # 1. Extract query (?...).
            if (preg_match('#^(.*?)(?:\?(.*))$#', $pattern, $m)) {
                $pattern = $m[1];
                $query   = $m[2];
            }

            $rest = null;
            $parseAuthority = false;

            # 2a. Full scheme with authority (scheme://...).
            # Restored and updated to allow variable tags like {scheme}
            $schemeRegex = '#^([a-z][a-z0-9+\-.]*|\{[^}]+\})://(.*)$#i';
            if (preg_match($schemeRegex, $pattern, $m)) {
                $scheme = $m[1];
                $rest   = $m[2];
                $parseAuthority = true;

            # 2b. Network-path reference: leading '//' but no scheme.
            }
            elseif (str_starts_with($pattern, '//')) {
                $scheme = null;
                $rest   = substr($pattern, 2);
                $parseAuthority = true;

            # 2c. Generic scheme without authority (e.g., mailto:xxx).
            }
            elseif (preg_match('#^([a-z][a-z0-9+\-.]*):(.*)$#i', $pattern, $m)) {
                $scheme = $m[1];
                $rest   = $m[2];
                $parseAuthority = false; 

            # 2d. No scheme, but check if it starts with an authority (e.g. "sub.domain.com/path")
            }
            else {
                # Look for the first slash that isn't inside a variable {id:int/regex}.
                $firstSlash = $this->findFirstUnbalancedSlash($pattern);

                # If there is a slash and the part before it contains a dot or a variable...
                if ($firstSlash !== false) {
                    $potentialHost = substr($pattern, 0, $firstSlash);
                    
                    # If it looks like a domain (contains a dot) or a variable {host}...
                    if (str_contains($potentialHost, '.') || str_contains($potentialHost, '}')) {
                        $host = $potentialHost;
                        $path = substr($pattern, $firstSlash + 1);
                        return new URI(null, $host, $path, $query, null, $fragment);
                    }
                }

                # Default fallback: Path-only.
                return new URI(null, null, ltrim($pattern, "/"), $query, null, $fragment);
            }

            if ($parseAuthority) {
                # 3. Extract user:pass@ if present.
                # First, isolate the authority part from the path using brace-aware logic.
                $pathStart = $this->findFirstUnbalancedSlash($rest);

                if ($pathStart !== false) {
                    $authorityPart = substr($rest, 0, $pathStart);
                    $path = substr($rest, $pathStart + 1);
                } else {
                    $authorityPart = $rest;
                    $path = '';
                }

                if (preg_match('#^(?:([^:@]+)(?::([^@]+))?@)(.*)$#', $authorityPart, $m)) {
                    $user = $m[1];
                    $pass = $m[2] ?? null;
                    $authorityPart = $m[3];
                }

                # 4. Split host and port (bracket-aware last-colon detection).
                $host = $authorityPart;
                $port = null;

                $len = strlen($authorityPart);
                $braceLevel = 0;
                $bracketLevel = 0;
                $candidatePos = false;

                for ($i = 0; $i < $len; $i++) {
                    $ch = $authorityPart[$i];
                    if ($ch === '{') $braceLevel++;
                    elseif ($ch === '}') { if ($braceLevel > 0) $braceLevel--; }
                    elseif ($ch === '[') $bracketLevel++;
                    elseif ($ch === ']') { if ($bracketLevel > 0) $bracketLevel--; }

                    if ($ch === ':' && $braceLevel === 0 && $bracketLevel === 0) {
                        $candidatePos = $i;
                    }
                }

                if ($candidatePos !== false) {
                    $host = substr($authorityPart, 0, $candidatePos);
                    $port = substr($authorityPart, $candidatePos + 1);
                }
                else $host = $authorityPart;
            }
            
            # Logic for 2c fallback when no authority is expected
            else $path = $rest;

            return new URI($scheme, $host, $path, $query, $port, $fragment, $user, $pass);
        }
    }
?>