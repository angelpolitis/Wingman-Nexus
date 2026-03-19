<?php
    /**
     * Project Name:    Wingman Nexus - Route
     * Created by:      Angel Politis
     * Creation Date:   Dec 15 2023
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2023-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Rules namespace.
    namespace Wingman\Nexus\Rules;

    /**
     * Represents a route.
     * @package Wingman\Nexus\Rules
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Route extends Rule {
        /**
         * The target of a route.
         * @var string|array|callable|null
         */
        protected mixed $target;

        /**
         * Creates a new route.
         * @param string $name The name of a route.
         * @param string $pattern The pattern of a route.
         * @param string|array|callable|null $target The expression, map or callback of a route.
         */
        public function __construct (string $name, string $pattern, mixed $target = null) {
            parent::__construct($name, $pattern, $target);
        }

        /**
         * Creates a new route.
         * @param string $name The name of a route.
         * @param string $pattern The pattern of a route.
         * @param string|array|callable|null $target The expression, map or callback of a route.
         * @return static The created route.
         */
        public static function from (string $name, string $pattern, mixed $target = null) : static {
            return parent::from($name, $pattern, $target);
        }

        /**
         * Gets the target of a route.
         * @return string|array|callable|null The target of the route.
         */
        public function getTarget () : mixed {
            return $this->target;
        }
    }
?>