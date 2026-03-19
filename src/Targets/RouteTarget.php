<?php
    /**
     * Project Name:    Wingman Nexus - Route Target
     * Created by:      Angel Politis
     * Creation Date:   Nov 10 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Targets namespace.
    namespace Wingman\Nexus\Targets;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;
    use Wingman\Nexus\Interfaces\Target;

    /**
     * Represents a route target.
     * @package Wingman\Nexus\Targets
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteTarget implements Target {
        /**
         * The class of a route target.
         * @var string|null
         */
        public readonly ?string $class;

        /**
         * The action of a route target.
         * @var string|null
         */
        public readonly ?string $action;

        /**
         * The arguments of a route target.
         * @var array
         */
        public readonly array $arguments;

        /**
         * The middleware of a route target.
         * @var array
         */
        public readonly array $middleware;

        /**
         * The tags of a route target.
         * @var array
         */
        public readonly array $tags;

        /**
         * The headers of a route target.
         * @var array
         */
        public readonly array $headers;

        /**
         * The content types of a route target.
         * @var array
         */
        public readonly array $contentTypes;
        
        /**
         * The placement of the query arguments of a route target.
         * @var RouteTargetQueryArgsPlacement
         */
        public readonly RouteTargetQueryArgsPlacement $queryArgsPlacement;

        /**
         * Whether a route preserves query parameters that weren't part of the specification.
         * @var bool
         */
        public readonly bool $preservesQuery;

        /**
         * Creates a new route target.
         * @param string|null $class The class of a route target.
         * @param string|null $action The action of a route target.
         * @param array $arguments The arguments of a route target.
         * @param RouteTargetQueryArgsPlacement $queryArgsPlacement The placement of the query arguments of a route target.
         * @param array $middleware The middleware of a route target.
         * @param array $tags The tags of a route target.
         * @param array $headers The headers of a route target.
         * @param array $contentTypes The content types of a route target.
         * @param bool $preservesQuery Whether uncaptured query parameters must be kept.
         */
        public function __construct (
            ?string $class = null,
            ?string $action = null,
            array $arguments = [],
            RouteTargetQueryArgsPlacement $queryArgsPlacement = RouteTargetQueryArgsPlacement::NONE,
            array $middleware = [],
            array $tags = [],
            array $headers = [],
            array $contentTypes = [],
            bool $preservesQuery = true
        ) {
            $this->class = $class;
            $this->action = $action;
            $this->arguments = $arguments;
            $this->queryArgsPlacement = $queryArgsPlacement;
            $this->middleware = $middleware;
            $this->tags = $tags;
            $this->headers = $headers;
            $this->contentTypes = $contentTypes;
            $this->preservesQuery = $preservesQuery;
        }

        /**
         * Serialises a route target.
         * @return array The serialised target.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a route target.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->class = $data["class"];
            $this->action = $data["action"];
            $this->arguments = $data["arguments"];
            $this->queryArgsPlacement = RouteTargetQueryArgsPlacement::from($data["queryArgsPlacement"]);
            $this->middleware = $data["middleware"];
            $this->tags = $data["tags"];
            $this->headers = $data["headers"];
            $this->contentTypes = $data["contentTypes"];
            $this->preservesQuery = $data["preservesQuery"];
        }

        /**
         * Creates a new route target (for var_export).
         * @param array $properties The properties used to create a new route target.
         */
        public static function __set_state (array $properties) : static {
            return new static(
                $properties["class"],
                $properties["action"],
                $properties["arguments"],
                RouteTargetQueryArgsPlacement::from($properties["queryArgsPlacement"]),
                $properties["middleware"],
                $properties["tags"],
                $properties["headers"],
                $properties["contentTypes"],
                $properties["preservesQuery"]
            );
        }

        /**
         * Gets whether the query arguments of a route target are appended.
         * @return bool Whether the query arguments of a route target are appended.
         */
        public function areQueryArgsAppended () : bool {
            return $this->queryArgsPlacement === RouteTargetQueryArgsPlacement::AFTER;
        }

        /**
         * Gets whether the query arguments of a route target are excluded.
         * @return bool Whether the query arguments of a route target are excluded.
         */
        public function areQueryArgsExcluded () : bool {
            return $this->queryArgsPlacement === RouteTargetQueryArgsPlacement::NONE;
        }

        /**
         * Gets whether the query arguments of a route target are prepended.
         * @return bool Whether the query arguments of a route target are prepended.
         */
        public function areQueryArgsPrepended () : bool {
            return $this->queryArgsPlacement === RouteTargetQueryArgsPlacement::BEFORE;
        }

        /**
         * Gets a route definition as an array.
         * @return array The information of a route definition as an array.
         */
        public function getArray () : array {
            return [
                "class" => $this->class,
                "action" => $this->action,
                "arguments" => $this->arguments,
                "queryArgsPlacement" => $this->queryArgsPlacement->value,
                "middleware" => $this->middleware,
                "tags" => $this->tags,
                "headers" => $this->headers,
                "contentTypes" => $this->contentTypes,
                "preservesQuery" => $this->preservesQuery
            ];
        }

        /**
         * Gets the action of a route target.
         * @return string|null The action of the route target or `null` if no action is specified.
         */
        public function getAction () : ?string {
            return $this->action;
        }

        /**
         * Gets the arguments of a route target.
         * @return array The arguments of the route target.
         */
        public function getArguments () : array {
            return $this->arguments;
        }

        /**
         * Gets the class of a route target.
         * @return string|null The class of the route target.
         */
        public function getClass () : ?string {
            return $this->class;
        }

        /**
         * Gets the content types of a route target.
         * @return array The content types of the route target.
         */
        public function getContentTypes () : array {
            return $this->contentTypes;
        }

        /**
         * Gets the headers of a route target.
         * @return array The headers of the route target.
         */
        public function getHeaders () : array {
            return $this->headers;
        }

        /**
         * Gets the middleware of a route target.
         * @return array The middleware of the route target.
         */
        public function getMiddleware () : array {
            return $this->middleware;
        }

        /**
         * Gets the placement of the query arguments of a route target.
         * @return RouteTargetQueryArgsPlacement The placement of the query arguments.
         */
        public function getQueryArgsPlacement () : RouteTargetQueryArgsPlacement {
            return $this->queryArgsPlacement;
        }

        /**
         * Gets the tags of a route target.
         * @return array The tags of the route target.
         */
        public function getTags () : array {
            return $this->tags;
        }

        /**
         * Gets whether a route should preserve query parameters that weren't specified.
         * @return bool Whether the route should preserve query parameters that weren't specified.
         */
        public function preservesQuery () : bool {
            return $this->preservesQuery;
        }
    }
?>