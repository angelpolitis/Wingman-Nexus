<?php
    /**
     * Project Name:    Wingman Nexus - Route Attribute
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Attributes namespace.
    namespace Wingman\Nexus\Attributes;

    # Import the following classes to the current scope.
    use Attribute;
    use Wingman\Nexus\Enums\HttpMethod;
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;

    /**
     * Declares a method as a handler for a named route.
     *
     * Place this attribute on any public method whose owning class will be passed to
     * {@see \Wingman\Nexus\AttributeScanner::scan()}. When scanned, the method's class
     * and method name are recorded as the route target; no specific class type or
     * interface is required.
     *
     * Multiple `Route` attributes may be placed on the same method to register it
     * under several patterns simultaneously.
     * @package Wingman\Nexus\Attributes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
    class Route {
        /**
         * The content types accepted by the route.
         * @var string[]
         */
        public readonly array $contentTypes;

        /**
         * The response headers sent by the route.
         * @var array
         */
        public readonly array $headers;

        /**
         * The HTTP methods this route responds to.
         * @var HttpMethod[]|string[]
         */
        public readonly array $methods;

        /**
         * The middleware applied to the route.
         * @var string[]
         */
        public readonly array $middleware;

        /**
         * The name of the route, used for URL generation.
         * When `null`, the scanner derives a name from the class and method automatically.
         * @var string|null
         */
        public readonly ?string $name;

        /**
         * The URL pattern of the route.
         * @var string
         */
        public readonly string $pattern;

        /**
         * Whether the router should preserve query parameters not captured by the pattern.
         * @var bool
         */
        public readonly bool $preservesQuery;

        /**
         * The placement of query arguments relative to the routed target.
         * @var RouteTargetQueryArgsPlacement
         */
        public readonly RouteTargetQueryArgsPlacement $queryArgsPlacement;

        /**
         * The tags associated with the route.
         * @var string[]
         */
        public readonly array $tags;

        /**
         * Declares a method as a handler for a named route.
         * @param string $pattern The URL pattern of the route.
         * @param string|null $name The route name used for URL generation; auto-derived when `null`.
         * @param HttpMethod[]|string[] $methods The HTTP methods this route responds to.
         * @param string[] $middleware The middleware applied to the route.
         * @param string[] $tags The tags associated with the route.
         * @param array $headers The response headers sent by the route.
         * @param string[] $contentTypes The content types accepted by the route.
         * @param RouteTargetQueryArgsPlacement $queryArgsPlacement The placement of query arguments.
         * @param bool $preservesQuery Whether to preserve uncaptured query parameters.
         */
        public function __construct (
            string $pattern,
            ?string $name = null,
            array $methods = [HttpMethod::GET],
            array $middleware = [],
            array $tags = [],
            array $headers = [],
            array $contentTypes = [],
            RouteTargetQueryArgsPlacement $queryArgsPlacement = RouteTargetQueryArgsPlacement::NONE,
            bool $preservesQuery = true
        ) {
            $this->pattern = $pattern;
            $this->name = $name;
            $this->methods = $methods;
            $this->middleware = $middleware;
            $this->tags = $tags;
            $this->headers = $headers;
            $this->contentTypes = $contentTypes;
            $this->queryArgsPlacement = $queryArgsPlacement;
            $this->preservesQuery = $preservesQuery;
        }
    }
?>