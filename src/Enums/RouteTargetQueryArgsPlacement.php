<?php
    /**
     * Project Name:    Wingman Nexus - Route Target Query Args Placement
     * Created by:      Angel Politis
     * Creation Date:   Nov 10 2025
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
    enum RouteTargetQueryArgsPlacement : int {
        /**
         * This option indicates that no specific placement is defined for query arguments in a route target.
         * @var int
         */
        case NONE = 0;

        /**
         * This option indicates that query arguments should be placed before the route target.
         * @var int
         */
        case BEFORE = 1;

        /**
         * This option indicates that query arguments should be placed after the route target.
         * @var int
         */
        case AFTER = 2;

        /**
         * Resolves a query args placement from an integer or returns the existing instance.
         * @param static|int $placement The placement to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|int $placement) : static {
            return $placement instanceof static ? $placement : static::from($placement);
        }
    }
?>