<?php
    /**
     * Project Name:    Wingman Nexus - Route Registry
     * Created by:      Angel Politis
     * Creation Date:   Nov 11 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * Represents a route registry.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteRegistry {
        /**
         * The compiled routes.
         * @var array<string, CompiledRoute>
         */
        protected array $compiledRoutes;

        /**
         * The route definitions.
         * @var array<string, RouteDefinition>
         */
        protected array $definitions;

        /**
         * The target maps.
         * @var array<string, TargetMap>
         */
        protected array $targetMaps;

        /**
         * The compiled routes by pattern.
         * @var array<string, CompiledRoute>
         */
        protected array $compiledRoutesByPattern;

        /**
         * The route definitions by pattern.
         * @var array<string, RouteDefinition>
         */
        protected array $definitionsByPattern;

        /**
         * The target maps by pattern.
         * @var array<string, TargetMap>
         */
        protected array $targetMapsByPattern;

        /**
         * Creates a new registry.
         * @param array{byName : array<string, CompiledRoute>, byPattern : array<string, CompiledRoute>} $compiledRoutes The compiled routes.
         * @param array{byName : array<string, RouteDefinition>, byPattern : array<string, RouteDefinition>} $definitions The definitions.
         * @param array{byName : array<string, TargetMap>, byPattern : array<string, TargetMap>} $targetMaps The target maps.
         */
        public function __construct (array $compiledRoutes, array $definitions, array $targetMaps) {
            $this->compiledRoutes = $compiledRoutes["byName"];
            $this->definitions = $definitions["byName"];
            $this->targetMaps = $targetMaps["byName"];
            $this->compiledRoutesByPattern = $compiledRoutes["byPattern"];
            $this->definitionsByPattern = $definitions["byPattern"];
            $this->targetMapsByPattern = $targetMaps["byPattern"];
        }

        /**
         * Gets a compiled route by name.
         * @param string $name The name of the compiled route.
         * @return CompiledRoute|null The compiled route, or `null` if it doesn't exist.
         */
        public function getCompiledRoute (string $name): ?CompiledRoute {
            return $this->compiledRoutes[$name] ?? null;
        }

        /**
         * Gets a compiled route by pattern.
         * @param string $pattern The pattern of the compiled route.
         * @return CompiledRoute|null The compiled route, or `null` if it doesn't exist.
         */
        public function getCompiledRouteByPattern (string $pattern): ?CompiledRoute {
            return $this->compiledRoutesByPattern[$pattern] ?? null;
        }

        /**
         * Gets all compiled routes.
         * @return array<string, CompiledRoute> The compiled routes.
         */
        public function getCompiledRoutes () : array {
            return $this->compiledRoutes;
        }

        /**
         * Gets a route definition by name.
         * @param string $name The name of the route definition.
         * @return RouteDefinition|null The route definition, or `null` if it doesn't exist.
         */
        public function getDefinition (string $name) : ?RouteDefinition {
            return $this->definitions[$name] ?? null;
        }

        /**
         * Gets a route definition by pattern.
         * @param string $pattern The pattern of the route definition.
         * @return RouteDefinition|null The route definition, or `null` if it doesn't exist.
         */
        public function getDefinitionByPattern (string $pattern) : ?RouteDefinition {
            return $this->definitionsByPattern[$pattern] ?? null;
        }

        /**
         * Gets all route definitions.
         * @return array<string, RouteDefinition> The route definitions.
         */
        public function getDefinitions () : array {
            return $this->definitions;
        }

        /**
         * Gets a target map by name.
         * @param string $name The name of the target map.
         * @return TargetMap|null The target map, or `null` if it doesn't exist.
         */
        public function getTargetMap (string $name) : ?TargetMap {
            return $this->targetMaps[$name] ?? null;
        }

        /**
         * Gets a target map by pattern.
         * @param string $pattern The pattern of the target map.
         * @return TargetMap|null The target map, or `null` if it doesn't exist.
         */
        public function getTargetMapByPattern (string $pattern) : ?TargetMap {
            return $this->targetMapsByPattern[$pattern] ?? null;
        }

        /**
         * Gets all target maps.
         * @return array<string, TargetMap> The target maps.
         */
        public function getTargetsMaps () : array {
            return $this->targetMaps;
        }
    }
?>