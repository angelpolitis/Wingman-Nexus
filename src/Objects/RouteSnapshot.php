<?php
    /**
     * Project Name:    Wingman Nexus - Route Snapshot
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * A read-only snapshot of a registered route, produced by the introspection API.
     *
     * Captures every piece of static information about a route at the moment
     * {@see \Wingman\Nexus\Router::getRoutes()} is called: name, pattern, typed
     * URL parameters, allowed HTTP methods, and per-method target details (class,
     * action, middleware, tags, response headers, content types).
     *
     * The snapshot is deliberately kept as a plain value object so that it can
     * trivially be serialised, `var_dump()`'d, or consumed by OpenAPI generators
     * and console tooling without any extra mapping step.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    readonly class RouteSnapshot {
        /**
         * Creates a new route snapshot.
         * @param string $name The registered name of the route.
         * @param string $pattern The raw URL pattern as registered (before compilation).
         * @param bool $isFallback Whether the route was registered as a fallback route.
         * @param array<array{name: string, type: string, location: ?string, optional: bool}> $parameters
         *   The typed URL parameters extracted from the pattern, in declaration order.
         * @param string[] $methods The HTTP methods this route accepts, uppercased.
         * @param array<string, array{class: ?string, action: ?string, middleware: string[], tags: string[], headers: array, contentTypes: array}> $targets
         *   Per-method target details, keyed by uppercased HTTP method name. Will be
         *   empty for routes backed by raw callables or uncompiled array targets.
         */
        public function __construct (
            public string $name,
            public string $pattern,
            public bool $isFallback,
            public array $parameters,
            public array $methods,
            public array $targets
        ) {}
    }
?>