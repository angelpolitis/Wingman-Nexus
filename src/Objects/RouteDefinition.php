<?php
    /**
     * Project Name:    Wingman Nexus - Route Definition
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Enums\RouteQueryRequirement;
    use Wingman\Nexus\Exceptions\DuplicateParameterException;

    /**
     * Represents a route definition.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteDefinition {
        /**
         * The pattern of a route.
         * @var string
         */
        protected string $pattern;

        /**
         * The URI of a route.
         * @var URI
         */
        protected URI $uri;

        /**
         * The parameters of a route's components (minus the query).
         * @var Parameter[]
         */
        protected array $parameters;

        /**
         * The segments of a route's query.
         * @var QuerySegment[]
         */
        protected array $querySegments;

        /**
         * The requirement state of a route's query.
         * @var RouteQueryRequirement
         */
        protected RouteQueryRequirement $queryRequirement;

        /**
         * Creates a new route definition.
         * @param string $pattern The pattern.
         * @param URI $uri The URI.
         * @param Parameter[] $parameters The parameters.
         * @param QuerySegment[] $querySegments The query parameters.
         * @param RouteQueryRequirement $queryRequirement The requirement state of the query.
         * @throws Exception When duplicate parameter names are detected.
         */
        public function __construct (
            string $pattern,
            URI $uri,
            array $parameters = [],
            array $querySegments = [],
            RouteQueryRequirement $queryRequirement = RouteQueryRequirement::OPTIONAL
        ) {
            $this->pattern = $pattern;
            $this->uri = $uri;
            $this->parameters = $parameters;
            $this->querySegments = $querySegments;
            $this->queryRequirement = $queryRequirement;

            $names = array_map(fn($p) => $p->getName(), $this->parameters);
            if (count($names) !== count(array_unique($names))) {
                throw new DuplicateParameterException("Duplicate parameter names detected in route: " . $pattern);
            }
        }

        /**
         * Serialises a route definition.
         * @return array The serialised definition.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a route definition.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->pattern = $data["pattern"];
            $this->uri = $data["uri"];
            $this->parameters = $data["parameters"];
            $this->querySegments = $data["querySegments"];
            $this->queryRequirement = RouteQueryRequirement::from($data["queryRequirement"]);
        }

        /**
         * Creates a new route definition (for var_export).
         * @param array $properties The properties used to create a new route definition.
         */
        public static function __set_state (array $properties) : static {
            return new static(
                $properties["pattern"],
                $properties["uri"],
                $properties["parameters"],
                $properties["querySegments"],
                RouteQueryRequirement::from($properties["queryRequirement"])
            );
        }

        /**
         * Gets a route definition as an array.
         * @return array The information of a route definition as an array.
         */
        public function getArray () : array {
            return [
                "pattern" => $this->pattern,
                "uri" => $this->uri,
                "parameters" => $this->parameters,
                "querySegments" => $this->querySegments,
                "queryRequirement" => $this->queryRequirement->value
            ];
        }

        /**
         * Gets the parameters of a route, optionally filtered by location.
         * @param string|null $location The location (host, path, port, etc.).
         * @return Parameter[] The parameters.
         */
        public function getParameters (?string $location = null) : array {
            if ($location === null) {
                return $this->parameters;
            }
            return array_filter($this->parameters, fn ($p) => $p->getLocation() === $location);
        }

        /**
         * Gets the pattern of a route.
         * @return string The pattern of the route.
         */
        public function getPattern () : string {
            return $this->pattern;
        }

        /**
         * Gets the query requirement of a route.
         * @return RouteQueryRequirement The query requirement of the route.
         */
        public function getQueryRequirement () : RouteQueryRequirement {
            return $this->queryRequirement;
        }

        /**
         * Gets the query segments of a route.
         * @return QuerySegment[] The query segments of the route.
         */
        public function getQuerySegments () : array {
            return $this->querySegments;
        }

        /**
         * Gets the URI of a route.
         * @return URI The URI of the route.
         */
        public function getUri () : URI {
            return $this->uri;
        }

        /**
         * Gets whether the presence of a query in a route is forbidden.
         * @return bool Whether the route requires no query.
         */
        public function isQueryForbidden () : bool {
            return $this->queryRequirement === RouteQueryRequirement::FORBIDDEN;
        }

        /**
         * Gets whether the presence of a query in a route is required.
         * @return bool Whether the route requires a query.
         */
        public function isQueryRequired () : bool {
            return $this->queryRequirement === RouteQueryRequirement::REQUIRED;
        }
        
        /**
         * Gets whether the presence of a query in a route is optional.
         * @return bool Whether the route requires no query.
         */
        public function isQueryOptional () : bool {
            return $this->queryRequirement === RouteQueryRequirement::OPTIONAL;
        }
    }
?>