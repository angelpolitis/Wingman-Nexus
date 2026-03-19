<?php
    /**
     * Project Name:    Wingman Nexus - Url Generator
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
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Bridge\Verix\Validator;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Exceptions\InvalidParameterValueException;
    use Wingman\Nexus\Exceptions\MissingParameterException;
    use Wingman\Nexus\Exceptions\WildcardValueCountException;
    use Wingman\Nexus\Objects\ArgumentList;
    use Wingman\Nexus\Objects\ArgumentSet;
    use Wingman\Nexus\Objects\RouteDefinition;

    /**
     * Represents a URL generator.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class UrlGenerator {
        /**
         * The symbol used to represent a single-segment wildcard in patterns.
         * @var string
         */
        protected string $wildcard1;

        /**
         * The symbol used to represent a multi-segment wildcard in patterns.
         * @var string
         */
        protected string $wildcardN;

        /**
         * The definition of a route.
         * @var RouteDefinition
         */
        protected RouteDefinition $routeDefinition;

        /**
         * The type registry of a URL generator.
         * @var TypeRegistry
         */
        protected TypeRegistry $types;

        /**
         * Creates a new URL generator.
         * @param RouteDefinition $routeDefinition A route definition.
         * @param string $wildcard1 The single-segment wildcard symbol.
         * @param string $wildcardN The multi-segment wildcard symbol.
         * @param TypeRegistry $types The type registry to use.
         */
        public function __construct (RouteDefinition $routeDefinition, string $wildcard1, string $wildcardN, TypeRegistry $types) {
            $this->routeDefinition  = $routeDefinition;
            $this->wildcard1 = $wildcard1;
            $this->wildcardN = $wildcardN;
            $this->types = $types;
        }

        /**
         * Normalises a parameter value for URL inclusion.
         * @param string $name The name of the parameter.
         * @param mixed &$value The value of the parameter (by reference).
         * @param bool $isPath Whether the parameter is part of the path.
         */
        protected function normaliseParameter (string $name, mixed &$value) : void {
            $wildcardN = $this->wildcardN;
        
            $encoder = function ($val) {
                $encoded = rawurlencode(strval($val));
                return $encoded;
            };
        
            if ($name === $wildcardN && is_array($value)) {
                $value = array_map($encoder, $value);
            }
            else {
                $value = $encoder(is_array($value) ? reset($value) : $value);
            }
        }

        /**
         * Replaces placeholders in a template with parameter values.
         * @param string $template The template string.
         * @param ?ArgumentSet $parameters The parameters to use.
         * @param string $location The location of the parameters (path or query).
         * @return string The template with placeholders replaced.
         * @throws Exception If a required parameter is missing.
         */
        protected function replacePlaceholders (string $template, ?ArgumentSet $parameters, string $location) : string {
            # Filter parameters specifically for this URI component (host, path, scheme, etc.).
            $params = array_filter($this->routeDefinition->getParameters(), fn ($p) => $p->getLocation() === $location);
            $result = $template;
        
            foreach ($params as $i => $param) {
                $name = $param->getName();
                $wildcardN = $this->wildcardN;
                $wildcard1 = $this->wildcard1;
        
                # 1. Resolve Value (Handling Wildcards vs Named).
                if ($name === $wildcardN || $name === $wildcard1) {
                    $value = $parameters->indexed[$i] ?? null;
                }
                else {
                    $value = $parameters->named[$name] ?? $parameters->indexed[$i] ?? null;
                }
        
                # 2. Handle Missing Values.
                if ($value === null) {
                    if ($param->isOptional()) {
                        # If path, try to remove leading slash. Otherwise, just remove the tag..
                        $search = ($location === "path") ? ['/' . $param->getRaw(), $param->getRaw()] : $param->getRaw();
                        $result = str_replace($search, '', $result);
                        continue;
                    }
                    throw new MissingParameterException("Missing required parameter '$name' for $location.");
                }
        
                # 3. Validation & Normalization.
                $this->validateParameter($name, $param->getType(), $value);
                $this->normaliseParameter($name, $value);
        
                # 4. Replacement.
                $replacement = is_array($value) ? implode('/', $value) : strval($value);
                $quotedRaw = preg_quote($param->getRaw(), '/');
                $result = preg_replace('/' . $quotedRaw . '/', $replacement, $result, 1);
            }
        
            return $result;
        }

        /**
         * Validates a value candidate for a wildcard.
         * @param string $wildcard The wildcard symbol.
         * @param mixed $value The value to be validated.
         * @throws WildcardValueCountException If the value is an array whose items don't match the number of wildcard instances.
         */
        protected function validateWildcardValue (string $wildcard, mixed $value) : void {
            if (!isset($value)) return;

            if (!Helper::isIndexedArray($value)) return;

            $value = Helper::isIndexedArray($value) ? $value : [$value];
    
            $valueCount = sizeof($value);

            $wildcardCount = sizeof(array_filter($this->routeDefinition->getParameters(), fn ($p) => $p->getName() === $wildcard));

            if ($wildcardCount > 0 && $valueCount != $wildcardCount) {
                throw new WildcardValueCountException("When specifying an array of values for the wildcard '$wildcard', the values must match the number of wildcards. $wildcardCount needed, $valueCount given.");
            }
        }

        /**
         * Generates a URL from a route.
         * @param ?ArgumentList $parameters The parameters to use for all URI components.
         * @param bool $preserveQueryExtraParameters Whether extra parameters should be appended to the query.
         * @return string The generated URL.
         */
        public function generate (?ArgumentList $parameters = null, bool $preserveQueryExtraParameters = true) : string {
            $parameters = $parameters ?? new ArgumentList();
            
            $url = "";

            # 1. Scheme
            $scheme = $this->generateScheme($parameters->getSchemeSet());
            if ($scheme !== null && $scheme !== "") {
                $url .= $scheme . ":";
            }

            # 2. Authority (Username, Password, Host, Port)
            $authority = $this->generateAuthority($parameters);
            if ($authority !== null && $authority !== "") {
                $url .= "//" . $authority;
            }

            # 3. Path
            $path = $this->generatePath($parameters->getPathSet());
            if ($path !== "") {
                if ($authority !== null) {
                    $url = rtrim($url, '/') . '/' . ltrim($path, '/');
                }
                else $url .= $path;
            }

            # 4. Query
            if (!empty($this->routeDefinition->getQuerySegments()) || !empty($parameters->query->extra)) {
                $query = $this->generateQuery($parameters->getQuerySet(), $preserveQueryExtraParameters);
                if ($query !== null && $query !== "") {
                    $url .= '?' . ltrim($query, '?');
                }
            }

            # 5. Fragment
            $fragment = $this->generateFragment($parameters->getFragmentSet());
            if ($fragment !== null && $fragment !== "") {
                $url .= '#' . ltrim($fragment, '#');
            }

            Emitter::create()->with(url: $url, definition: $this->routeDefinition, generator: $this)->emit(Signal::URL_GENERATED);

            return $url;
        }
        
        /**
         * Generate the authority (host + port) of a route.
         * @param ?ArgumentList $parameters The parameters to use.
         * @return string|null The compiled authority or null if none exists.
         * @throws Exception If no value has been provided for a required parameter.
         */
        public function generateAuthority (?ArgumentList $parameters = null) : ?string {
            $uri = $this->routeDefinition->getUri();
            $host = $uri->getHost();
            if ($host === null) return null;
        
            $auth = "";
        
            # 1. Userinfo: username[:password]@
            $user = $uri->getUsername();
            if ($user !== null) {
                $auth .= $this->replacePlaceholders($user, $parameters->getUsernameSet(), "username");
                $pass = $uri->getPassword();
                if ($pass !== null) {
                    $auth .= ":" . $this->replacePlaceholders($pass, $parameters->getPasswordSet(), "password");
                }
                $auth .= "@";
            }
        
            # 2. Host
            $auth .= $this->replacePlaceholders($host, $parameters->getHostSet(), "host");
        
            # 3. Port
            $port = $uri->getPort();
            if ($port !== null) {
                $auth .= ":" . $this->replacePlaceholders((string) $port, $parameters->getPortSet(), "port");
            }
        
            return $auth;
        }

        /**
         * Generate the fragment of a route.
         * @param ?ArgumentSet $parameters The parameters to use.
         * @return string|null The compiled fragment (without the #) or null if none exists.
         * @throws Exception If no value has been provided for a required parameter.
         */
        public function generateFragment (?ArgumentSet $parameters = null) : ?string {
            $uri = $this->routeDefinition->getUri();
            $fragmentTemplate = $uri->getFragment();
        
            # 1. If the pattern actually defined a fragment template (e.g., #section-{id}).
            if ($fragmentTemplate !== null) {
                return $this->replacePlaceholders($fragmentTemplate, $parameters, "fragment");
            }
        
            # 2. Fallback: If no template exists, check for an "extra" fragment from the request.
            if ($parameters !== null && isset($parameters->extra['#'])) {
                return (string) $parameters->extra['#'];
            }
        
            return null;
        }

        /**
         * Generate the path of a route.
         * @param ?ArgumentSet $parameters The parameters to use.
         * @return string The compiled path.
         * @throws Exception If no value has been provided for a required parameter.
         */
        public function generatePath (?ArgumentSet $parameters = null) : string {
            $uri = $this->routeDefinition->getUri();
            if (!$uri->getPath()) return "";
            $path = $this->replacePlaceholders($uri->getPath(), $parameters, "path");
            $path = preg_replace('#/+#', '/', $path);
            return '/' . ltrim($path, '/');
        }

        /**
         * Generate the query of a route.
         * @param ?ArgumentSet $parameters The parameters to use.
         * @param bool $preserveQueryExtraParameters Whether the extra (unmatched) parameters of the query must be kept.
         * @return string The compiled query.
         * @throws Exception If no value has been provided for a required parameter.
         */
        public function generateQuery (?ArgumentSet $parameters = null, bool $preserveQueryExtraParameters = true) : string {
            $extra = $parameters ? $parameters->extra : [];
            $namedParams = $parameters ? $parameters->named : [];
            
            # 1. Iterate through segments to find and process parameters.
            foreach ($this->routeDefinition->getQuerySegments() as $segment) {
                if ($segment->isStatic()) continue;
        
                foreach ($segment->getParameters() as $param) {
                    $name = $param->getName();
                    
                    if (isset($namedParams[$name])) {
                        $this->validateParameter($name, $param->getType(), $namedParams[$name]);
                        $this->normaliseParameter($name, $namedParams[$name]);
                    }
                }
            }
        
            $generatedSegments = [];
            
            # 2. Generate the segment strings using the now-normalised parameters.
            foreach ($this->routeDefinition->getQuerySegments() as $segment) {
                $result = $segment->generate($namedParams); 
                
                if ($result !== null) {
                    $generatedSegments[] = $result;
                }
            }
        
            # 3. Handle extra parameters.
            if ($preserveQueryExtraParameters && !empty($extra)) {
                foreach ($extra as $key => $value) {
                    $generatedSegments[] = rawurlencode($key) . "=" . rawurlencode(strval($value));
                }
            }
        
            return implode('&', $generatedSegments);
        }

        /**
         * Generate the scheme of a route.
         * @param ?ArgumentSet $parameters The parameters to use.
         * @return string|null The compiled scheme or null if none exists.
         * @throws Exception If no value has been provided for a required parameter.
         */
        public function generateScheme (?ArgumentSet $parameters = null) : ?string {
            $uri = $this->routeDefinition->getUri();
            $scheme = $uri->getScheme();

            if ($scheme === null) {
                return null;
            }

            $generatedScheme = $this->replacePlaceholders($scheme, $parameters, "scheme");

            return strtolower($generatedScheme);
        }

        /**
         * Validates a parameter.
         * @param string $name The name of the parameter.
         * @param string $type The type of the parameter.
         * @param mixed $value The value of the parameter.
         * @throws Exception If the type of value given is different than the one expected.
         */
        public function validateParameter (string $name, string $type, mixed $value) : void {
            $segments = is_array($value) ? $value : [$value];

            if (Validator::isSchemaExpression($type)) {
                foreach ($segments as $segment) {
                    Validator::validate($segment, $type, $name);
                }
                return;
            }

            $regex = sprintf("/^%s$/u", $this->types->resolve($type));

            foreach ($segments as $segment) {
                if (preg_match($regex, strval($segment))) continue;

                $display = is_array($value) ? '[' . implode(', ', $value) . ']' : $value;
                throw new InvalidParameterValueException("The parameter '$name' expects type '$type'; '$display' contains an invalid segment '$segment'.");
            }
        }
    }
?>