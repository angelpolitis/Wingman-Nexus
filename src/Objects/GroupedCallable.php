<?php
    /**
     * Project Name:    Wingman Nexus - Grouped Callable
     * Created by:      Angel Politis
     * Creation Date:   Mar 19 2026
     * Last Modified:   Mar 19 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * A value object that wraps a callable route target and carries the group-level
     * middleware, tags, and headers inherited from a {@see \Wingman\Nexus\RouteGroup}.
     *
     * When a callable is registered inside a `group()` callback that declares
     * middleware, tags, or headers, `RouteGroup::buildRules()` wraps the raw callable
     * in a `GroupedCallable` so that those attributes survive the `Route::from()` call
     * and are available to the resolver when the route is matched.
     *
     * The resolver unwraps the callable, constructs an `AnonymousTarget` with the
     * bundled attributes, and includes the attributes in the returned `RoutingResult`.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class GroupedCallable {
        /**
         * The headers carried by a grouped callable.
         * @var array
         */
        private array $headers;

        /**
         * The middleware carried by a grouped callable.
         * @var string[]
         */
        private array $middleware;

        /**
         * The tags carried by a grouped callable.
         * @var string[]
         */
        private array $tags;

        /**
         * Creates a new grouped callable.
         * @param callable $callable The raw callable target.
         * @param string[] $middleware The middleware class names inherited from the group.
         * @param string[] $tags The tag names inherited from the group.
         * @param array $headers The response headers inherited from the group.
         */
        public function __construct (
            private readonly mixed $callable,
            array $middleware = [],
            array $tags = [],
            array $headers = []
        ) {
            $this->middleware = $middleware;
            $this->tags = $tags;
            $this->headers = $headers;
        }

        /**
         * Gets the raw callable of a grouped callable.
         * @return callable The callable.
         */
        public function getCallable () : mixed {
            return $this->callable;
        }

        /**
         * Gets the headers of a grouped callable.
         * @return array The headers.
         */
        public function getHeaders () : array {
            return $this->headers;
        }

        /**
         * Gets the middleware of a grouped callable.
         * @return string[] The middleware class names.
         */
        public function getMiddleware () : array {
            return $this->middleware;
        }

        /**
         * Gets the tags of a grouped callable.
         * @return string[] The tag names.
         */
        public function getTags () : array {
            return $this->tags;
        }
    }
?>