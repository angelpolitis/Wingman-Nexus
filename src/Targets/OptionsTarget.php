<?php
    /**
     * Project Name:    Wingman Nexus - Options Target
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Targets namespace.
    namespace Wingman\Nexus\Targets;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Interfaces\Target;

    /**
     * Represents an OPTIONS target returned when an HTTP OPTIONS request matches one or more routes.
     *
     * Carries the full list of allowed HTTP methods at the matched URL and any aggregated
     * response headers collected from the matching route targets.
     * @package Wingman\Nexus\Targets
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class OptionsTarget implements Target {
        /**
         * The allowed HTTP methods at the matched URL.
         * @var string[]
         */
        public readonly array $allowedMethods;

        /**
         * The aggregated response headers from matching route targets.
         * @var array
         */
        public readonly array $headers;

        /**
         * Creates a new options target.
         * @param string[] $allowedMethods The allowed HTTP methods at the matched URL.
         * @param array $headers The aggregated response headers from matching route targets.
         */
        public function __construct (array $allowedMethods, array $headers = []) {
            $this->allowedMethods = $allowedMethods;
            $this->headers = $headers;
        }

        /**
         * Gets the allowed HTTP methods of an options target.
         * @return string[] The allowed HTTP methods at the matched URL.
         */
        public function getAllowedMethods () : array {
            return $this->allowedMethods;
        }

        /**
         * Gets the aggregated response headers of an options target.
         * @return array The aggregated response headers from matching route targets.
         */
        public function getHeaders () : array {
            return $this->headers;
        }
    }
?>