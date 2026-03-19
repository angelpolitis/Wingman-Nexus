<?php
    /**
     * Project Name:    Wingman Nexus - Compiled Route
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Enums\RouteQueryRequirement;

    /**
     * Represents a compiled route.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CompiledRoute {
        /**
         * The fragment pattern.
         * @var string|null
         */
        public readonly ?string $fragmentPattern;

        /**
         * The host pattern.
         * @var string|null
         */
        public readonly ?string $hostPattern;

        /**
         * The parameters.
         * @var Parameter[]
         */
        public readonly array $parameters;

        /**
         * The password pattern.
         * @var string|null
         */
        public readonly ?string $passwordPattern;

        /**
         * The path pattern.
         * @var string
         */
        public readonly string $pathPattern;

        /**
         * The port pattern.
         * @var string|null
         */
        public readonly ?string $portPattern;

        /**
         * The query patterns.
         * @var string[]
         */
        public readonly array $queryPatterns;

        /**
         * The query segments.
         * @var array
         */
        public readonly array $querySegments;

        /**
         * The query requirement.
         * @var RouteQueryRequirement
         */
        public readonly RouteQueryRequirement $queryRequirement;

        /**
         * The scheme pattern.
         * @var string|null
         */
        public readonly ?string $schemePattern;

        /**
         * The username pattern.
         * @var string|null
         */
        public readonly ?string $usernamePattern;

        /**
         * Creates a new compiled route.
         * @param string|null $schemePattern The scheme pattern.
         * @param string|null $usernamePattern The username pattern.
         * @param string|null $passwordPattern The password pattern.
         * @param string|null $hostPattern The host pattern.
         * @param string|null $portPattern The port pattern.
         * @param string $pathPattern The path pattern.
         * @param array $queryPatterns The query patterns.
         * @param array $parameters The parameters.
         * @param array $querySegments The query segments.
         * @param RouteQueryRequirement $queryRequirement The query requirement.
         */
        public function __construct (
            ?string $schemePattern = null,
            ?string $usernamePattern = null,
            ?string $passwordPattern = null,
            ?string $hostPattern = null,
            ?string $portPattern = null,
            string $pathPattern = "",
            array $queryPatterns = [],
            ?string $fragmentPattern = null,
            array $parameters = [],
            array $querySegments = [],
            RouteQueryRequirement $queryRequirement = RouteQueryRequirement::OPTIONAL
        ) {
            $this->schemePattern = $schemePattern;
            $this->usernamePattern = $usernamePattern;
            $this->passwordPattern = $passwordPattern;
            $this->hostPattern = $hostPattern;
            $this->portPattern = $portPattern;
            $this->pathPattern = $pathPattern;
            $this->queryPatterns = $queryPatterns;
            $this->fragmentPattern = $fragmentPattern;
            $this->parameters = $parameters;
            $this->querySegments = $querySegments;
            $this->queryRequirement = $queryRequirement;
        }
        
        /**
         * Serialises a compiled route.
         * @return array The serialised route.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a compiled route.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->schemePattern = $data["schemePattern"];
            $this->usernamePattern = $data["usernamePattern"];
            $this->passwordPattern = $data["passwordPattern"];
            $this->hostPattern = $data["hostPattern"];
            $this->portPattern = $data["portPattern"];
            $this->pathPattern = $data["pathPattern"];
            $this->fragmentPattern = $data["fragmentPattern"];
            $this->queryPatterns = $data["queryPatterns"];
            $this->parameters = $data["parameters"];
            $this->querySegments = $data["querySegments"];
            $this->queryRequirement = RouteQueryRequirement::from($data["queryRequirement"]);
        }

        /**
         * Creates a new compiled route (for var_export).
         * @param array $properties The properties used to create a new route.
         */
        public static function __set_state (array $properties) : static {
            return new static(
                $properties["schemePattern"],
                $properties["usernamePattern"],
                $properties["passwordPattern"],
                $properties["hostPattern"],
                $properties["portPattern"],
                $properties["pathPattern"],
                $properties["queryPatterns"],
                $properties["fragmentPattern"],
                $properties["parameters"],
                $properties["querySegments"],
                RouteQueryRequirement::from($properties["queryRequirement"])
            );
        }

        /**
         * Gets the information of a route as an array.
         * @return array The parameters.
         */
        public function getArray () : array {
            return [
                "schemePattern" => $this->schemePattern,
                "usernamePattern" => $this->usernamePattern,
                "passwordPattern" => $this->passwordPattern,
                "hostPattern" => $this->hostPattern,
                "portPattern" => $this->portPattern,
                "pathPattern" => $this->pathPattern,
                "fragmentPattern" => $this->fragmentPattern,
                "queryPatterns" => $this->queryPatterns,
                "parameters" => $this->parameters,
                "querySegments" => $this->querySegments,
                "queryRequirement" => $this->queryRequirement->value
            ];
        }

        /**
         * Gets the pattern for the host of a route.
         * @return string|null The pattern, or `null` if the host is static.
         */
        public function getHostPattern () : ?string {
            return $this->hostPattern;
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
         * Gets the pattern for the password of a route.
         * @return string|null The pattern, or `null` if the password is static.
         */
        public function getPasswordPattern () : ?string {
            return $this->passwordPattern;
        }

        /**
         * Gets the pattern for the path of a route.
         * @return string The pattern.
         */
        public function getPathPattern () : string {
            return $this->pathPattern;
        }

        /**
         * Gets the pattern for the port of a route.
         * @return string|null The pattern, or `null` if the port is static.
         */
        public function getPortPattern () : ?string {
            return $this->portPattern;
        }

        /**
         * Gets the segments for the query of a route.
         * @return QuerySegments[] The segments.
         */
        public function getQuerySegments () : array {
            return $this->querySegments;
        }

        /**
         * Gets the patterns for the query of a route.
         * @return string[] The patterns.
         */
        public function getQueryPatterns () : array {
            return $this->queryPatterns;
        }

        /**
         * Gets the requirement status for the query of a route.
         * @return RouteQueryRequirement The requirement status.
         */
        public function getQueryRequirement () : RouteQueryRequirement {
            return $this->queryRequirement;
        }

        /**
         * Gets the pattern for the scheme of a route.
         * @return string|null The pattern, or `null` if the scheme is static.
         */
        public function getSchemePattern () : ?string {
            return $this->schemePattern;
        }

        /**
         * Gets the pattern for the username of a route.
         * @return string|null The pattern, or `null` if the username is static.
         */
        public function getUsernamePattern () : ?string {
            return $this->usernamePattern;
        }
    }
?>