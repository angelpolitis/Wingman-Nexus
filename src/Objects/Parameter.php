<?php
    /**
     * Project Name:    Wingman Nexus - Parameter
     * Created by:      Angel Politis
     * Creation Date:   Jan 27 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * Represents a parameter.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Parameter {
        /**
         * The location of a parameter.
         * @var string|null
         */
        protected ?string $location = null;

        /**
         * The name of a parameter.
         * @var string
         */
        protected string $name;

        /**
         * Indicates whether a parameter is optional.
         * @var bool
         */
        protected bool $optional;

        /**
         * Indicates whether a parameter is partial.
         * @var bool
         */
        protected bool $partial;

        /**
         * The raw representation of a parameter.
         * @var string
         */
        protected string $raw;

        /**
         * The role of a parameter ('key' or 'value').
         * @var string
         */
        protected string $role;

        /**
         * The type of a parameter.
         * @var string
         */
        protected string $type;

        /**
         * Creates a new parameter.
         * @param string $name The name of the parameter.
         * @param string $type The type of the parameter.
         * @param string $role The role of the parameter ('key' or 'value').
         * @param string $raw The raw representation of the parameter.
         * @param bool $optional Indicates whether the parameter is optional.
         * @param bool $partial Indicates whether the parameter is partial.
         * @param string|null $location The location of the parameter, or `null` if not applicable.
         */
        public function __construct (string $name, string $type, string $role, string $raw, bool $optional, bool $partial, ?string $location = null) {
            $this->name = $name;
            $this->type = $type;
            $this->role = $role;
            $this->raw = $raw;
            $this->optional = $optional;
            $this->partial = $partial;
            $this->location = $location;
        }

        /**
         * Serialises a parameter.
         * @return array The serialised definition.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a parameter.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->name = $data["name"];
            $this->type = $data["type"];
            $this->role = $data["role"];
            $this->raw = $data["raw"];
            $this->optional = $data["optional"];
            $this->partial = $data["partial"];
            $this->location = $data["location"] ?? null;
        }

        /**
         * Creates a new parameter (for var_export).
         * @param array $properties The properties used to create a new parameter.
         */
        public static function __set_state (array $properties) : static {
            return new static(
                $properties["name"],
                $properties["type"],
                $properties["role"],
                $properties["raw"],
                $properties["optional"],
                $properties["partial"],
                $properties["location"] ?? null
            );
        }

        /**
         * Gets a parameter as an array.
         * @return array The information of a parameter as an array.
         */
        public function getArray () : array {
            return [
                "name" => $this->name,
                "type" => $this->type,
                "role" => $this->role,
                "raw" => $this->raw,
                "optional" => $this->optional,
                "partial" => $this->partial,
                "location" => $this->location
            ];
        }

        /**
         * Gets the location of a parameter.
         * @return string|null The location of the parameter, or `null` if not applicable.
         */
        public function getLocation () : ?string {
            return $this->location;
        }

        /**
         * Gets the name of a parameter.
         * @return string The name of the parameter.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the raw representation of a parameter.
         * @return string The raw representation of the parameter.
         */
        public function getRaw () : string {
            return $this->raw;
        }

        /**
         * Gets the role of a parameter.
         * @return string The role of the parameter ('key' or 'value').
         */
        public function getRole () : string {
            return $this->role;
        }

        /**
         * Gets the type of a parameter.
         * @return string The type of the parameter.
         */
        public function getType () : string {
            return $this->type;
        }

        /**
         * Gets whether the parameter is optional.
         * @return bool Whether the parameter is optional.
         */
        public function isOptional () : bool {
            return $this->optional;
        }

        /**
         * Gets whether the parameter is partial.
         * @return bool Whether the parameter is partial.
         */
        public function isPartial () : bool {
            return $this->partial;
        }
    }
?>