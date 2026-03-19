<?php
    /**
     * Project Name:    Wingman Nexus - Rewrite Snapshot
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
     * A read-only snapshot of a registered rewrite rule.
     *
     * Returned by {@see \Wingman\Nexus\Resolvers\RewriteResolver::getSnapshots()} and
     * surfaced to callers via {@see \Wingman\Nexus\Router::getRewrites()}.
     * Suitable for debugging, console tooling, and documentation generators.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RewriteSnapshot {
        /**
         * Creates a new rewrite snapshot.
         * @param string $name The registered route name.
         * @param string $pattern The URL pattern that triggers the rewrite.
         * @param bool $isFallback Whether this rule is a fallback rewrite.
         * @param string $destination The target path the request is internally rewritten to.
         * @param bool $preservesQuery Whether the original query string is forwarded to the destination.
         * @param array $parameters A flat list of pattern parameter descriptors.
         */
        public function __construct (
            public readonly string $name,
            public readonly string $pattern,
            public readonly bool $isFallback,
            public readonly string $destination,
            public readonly bool $preservesQuery,
            public readonly array $parameters = []
        ) {}
    }
?>