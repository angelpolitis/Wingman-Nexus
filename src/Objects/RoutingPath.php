<?php
    /**
     * Project Name:    Wingman Nexus - Routing Path
     * Created by:      Angel Politis
     * Creation Date:   Dec 02 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * Represents a routing path.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RoutingPath {
        /**
         * The items of a routing path.
         * @var array
         */
        protected array $items = [];

        /**
         * The map of a routing path.
         * @var array
         */
        protected array $map = [];

        /**
         * The URLs of a routing path.
         * @var array
         */
        protected array $urls = [];

        /**
         * Adds an entry to a routing path.
         * @param string $url A URL.
         * @param ?RoutingResult $result A routing result.
         */
        public function add (string $url, ?RoutingResult $result) : static {
            $this->items[] = $result;
            $this->urls[] = $url;
            $this->map[$url] = $result;
            return $this;
        }

        /**
         * Checks whether an entry exists in a routing path.
         * @param string $url A URL to look for in the map.
         */
        public function has (string $url) : bool {
            return isset($this->map[$url]);
        }
    }
?>