<?php
    /**
     * Project Name:    Wingman Nexus - Routing Error
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
     * Represents an error that can occur during the routing process.
     * @package Wingman\Nexus\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum RoutingError : int {
        /**
         * This option indicates that an error is unknown.
         * @var int
         */
        case UNKNOWN = 0;

        /**
         * This option indicates that the HTTP method used in the request is not allowed.
         * @var int
         */
        case METHOD_NOT_ALLOWED = 1;

        /**
         * This option indicates that the requested resource was not found.
         * @var int
         */
        case NOT_FOUND = 2;

        /**
         * This option indicates that the maximum rewrite depth has been exceeded.
         * @var int
         */
        case MAX_REWRITE_DEPTH_EXCEEDED = 3;

        /**
         * This option indicates that the maximum redirect depth has been exceeded.
         * @var int
         */
        case MAX_REDIRECT_DEPTH_EXCEEDED = 4;

        /**
         * This option indicates that a routing cycle has been identified.
         * @var int
         */
        case CYCLE_IDENTIFIED = 5;

        /**
         * This option indicates that a rewrite cycle has been identified.
         * @var int
         */
        case REWRITE_CYCLE_IDENTIFIED = 6;

        /**
         * This option indicates that a redirect cycle has been identified.
         * @var int
         */
        case REDIRECT_CYCLE_IDENTIFIED = 7;

        /**
         * Resolves a routing error from an integer or returns the existing instance.
         * @param static|int $error The error to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|int $error) : static {
            return $error instanceof static ? $error : static::from($error);
        }
    }
?>