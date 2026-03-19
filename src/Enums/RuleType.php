<?php
    /**
     * Project Name:    Wingman Nexus - Rule Type
     * Created by:      Angel Politis
     * Creation Date:   Nov 25 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Enums namespace.
    namespace Wingman\Nexus\Enums;

    /**
     * Represents the type of a routing rule.
     * @package Wingman\Nexus\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum RuleType : int {
        /**
         * This option indicates that a rule is a redirect.
         * @var int
         */
        case REDIRECT = 0;

        /**
         * This option indicates that a rule is a rewrite.
         * @var int
         */
        case REWRITE = 1;

        /**
         * This option indicates that a rule is a route.
         * @var int
         */
        case ROUTE = 2;

        /**
         * Resolves a rule type from an integer or returns the existing instance.
         * @param static|int $type The type to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|int $type) : static {
            return $type instanceof static ? $type : static::from($type);
        }
    }
?>