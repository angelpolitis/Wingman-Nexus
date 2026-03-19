<?php
    /**
     * Project Name:    Wingman Nexus - Argument Set
     * Created by:      Angel Politis
     * Creation Date:   Nov 22 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * Represents an argument set as it occurs after matching a URL against a route.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ArgumentSet {
        /**
         * All arguments of a set, indexed.
         * @var array<int, mixed>
         */
        public readonly array $indexed;

        /**
         * The named arguments of a set.
         * @var array<string, mixed>
         */
        public readonly array $named;

        /**
         * The unnamed arguments of a set.
         * @var array<string, mixed>
         */
        public readonly array $unnamed;

        /**
         * The extra arguments of a set.
         * @var array<string, mixed>
         */
        public readonly array $extra;

        /**
         * Creates a new argument set.
         * @return array $named The named arguments of the set.
         * @return array $unnamed The unnamed arguments of the set.
         * @param array $indexed All arguments of the set, indexed.
         * @param array $extra The extra arguments of the set.
         */
        public function __construct (array $named = [], array $unnamed = [], array $indexed = [], array $extra = []) {
            $this->named = $named;
            $this->unnamed = $unnamed;
            $this->indexed = $indexed;
            $this->extra = $extra;
        }

        /**
         * Exports a set as an array.
         * @return array The data of the set.
         */
        public function getArray () : array {
            return [
                "named" => $this->named,
                "unnamed" => $this->unnamed,
                "indexed" => $this->indexed,
                "extra" => $this->extra
            ];
        }

        /**
         * Gets the extra arguments of a set.
         * @return array The extra arguments.
         */
        public function getExtra () : array {
            return $this->extra;
        }

        /**
         * Gets the arguments of a set, indexed.
         * @return array The arguments.
         */
        public function getIndexed () : array {
            return $this->indexed;
        }

        /**
         * Gets the named arguments of a set.
         * @return array The named arguments.
         */
        public function getNamed () : array {
            return $this->named;
        }

        /**
         * Gets the unnamed arguments of a set.
         * @return array The unnamed arguments.
         */
        public function getUnnamed () : array {
            return $this->unnamed;
        }

        /**
         * Checks whether an argument set is empty.
         * @return bool Whether the set is empty.
         */
        public function isEmpty () : bool {
            return empty($this->indexed) && empty($this->named) && empty($this->unnamed) && empty($this->extra);
        }
    }
?>