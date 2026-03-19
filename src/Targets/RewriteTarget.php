<?php
    /**
     * Project Name:    Wingman Nexus - Rewrite Target
     * Created by:      Angel Politis
     * Creation Date:   Nov 22 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Targets namespace.
    namespace Wingman\Nexus\Targets;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Interfaces\Target;

    /**
     * Represents a rewrite target.
     * @package Wingman\Nexus\Targets
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RewriteTarget implements Target {
        /**
         * The status of a rewrite target.
         * @var string
         */
        public readonly string $path;

        /**
         * Whether a rewrite preserves the original query.
         * @var bool
         */
        public readonly bool $preservesQuery;

        /**
         * Creates a new rewrite target.
         * @param string $path The path of a rewrite target.
         * @param bool $preservesQuery Whether the rewrite should preserve the original query.
         */
        public function __construct (string $path, bool $preservesQuery = true) {
            $this->path = $path;
            $this->preservesQuery = $preservesQuery;
        }

        /**
         * Serialises a rewrite target.
         * @return array The serialised target.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a rewrite target.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->path = $data["path"];
            $this->preservesQuery = $data["preservesQuery"];
        }

        /**
         * Creates a new rewrite target (for var_export).
         * @param array $properties The properties used to create a new rewrite target.
         */
        public static function __set_state (array $properties) : static {
            return new static($properties["path"], $properties["preservesQuery"]);
        }

        /**
         * Creates a new rewrite target.
         * @param string $target The target of the rewrite.
         * @param bool $preservesQuery Whether the rewrite should preserve the original query.
         * @return static The new rewrite.
         */
        public static function from (string $target, bool $preservesQuery = true) : static {
            return new static($target, $preservesQuery);
        }

        /**
         * Gets a rewrite definition as an array.
         * @return array The information of a rewrite definition as an array.
         */
        public function getArray () : array {
            return [
                "path" => $this->path,
                "preservesQuery" => $this->preservesQuery
            ];
        }

        /**
         * Gets the path of a rewrite target.
         * @return string The path of the rewrite target.
         */
        public function getPath () : string {
            return $this->path;
        }

        /**
         * Gets whether a rewrite should preserve the original query.
         * @return bool Whether the rewrite should preserve the original query.
         */
        public function preservesQuery () : bool {
            return $this->preservesQuery;
        }
    }
?>