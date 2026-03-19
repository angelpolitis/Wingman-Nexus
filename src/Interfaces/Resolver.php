<?php
    /**
     * Project Name:    Wingman Nexus - Resolver
     * Created by:      Angel Politis
     * Creation Date:   Dec 02 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Interfaces namespace.
    namespace Wingman\Nexus\Interfaces;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Objects\RoutingResult;

    /**
     * Represents a resolver.
     * @package Wingman\Nexus\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    interface Resolver {
        /**
         * Resolves a URL and HTTP method to a routing result.
         * @param string $url The URL to resolve.
         * @param string $method The HTTP method to resolve.
         * @param array $steps The steps.
         * @return RoutingResult The routing result.
         */
        public function resolve (string $url, string $method, array $steps = []) : RoutingResult;
    }    
?>