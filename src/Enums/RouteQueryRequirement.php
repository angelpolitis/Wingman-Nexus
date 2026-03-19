<?php
    /**
     * Project Name:    Wingman Nexus - Route Query Requirement
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Enums namespace.
    namespace Wingman\Nexus\Enums;

    /**
     * Represents the state of requirement for a route query.
     * @package Wingman\Nexus\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum RouteQueryRequirement : int {
        /**
         * This option indicates that a query can be optionally present in a route.
         * @var int
         */
        case OPTIONAL = 0;

        /**
         * This option indicates that a query is required to be present in a route.
         * @var int
         */
        case REQUIRED = 1;

        /**
         * This option indicates that a query is forbidden from being present in a route.
         * @var int
         */
        case FORBIDDEN = 2;

        /**
         * Resolves a route query requirement from an integer or returns the existing instance.
         * @param static|int $requirement The requirement to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|int $requirement) : static {
            return $requirement instanceof static ? $requirement : static::from($requirement);
        }
    }
?>