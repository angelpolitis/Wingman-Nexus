<?php
    /**
     * Project Name:    Wingman Nexus - Target Map
     * Created by:      Angel Politis
     * Creation Date:   Nov 11 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\TargetSizeMismatchException;
    use Wingman\Nexus\Interfaces\Target;
    use Wingman\Nexus\Helper;

    /**
     * Represents a method-target map.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TargetMap {
        /**
         * The inner map.
         * @var array<string, Target>
         */
        protected array $map = [];

        /**
         * Creates a new method target map.
         * @param static|array<string, Target> $map The target map.
         */
        public function __construct (self|array $map = []) {
            if ($map instanceof static) {
                $map = $map->map;
            }

            foreach ($map as $method => $target) {
                $this->setMethod($method, $target);
            }
        }

        /**
         * Creates a new target map (for var_export).
         * @param array $properties The properties used to create a new target map.
         */
        public static function __set_state (array $properties) : static {
            return new static(
                $properties["map"]
            );
        }

        /**
         * Serialises a target map.
         * @return array The serialised data.
         */
        public function __serialize () : array {
            return $this->map;
        }

        /**
         * Unserialises a target map.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->map = $data;
        }

        /**
         * Maps a target to a method.
         * @param string $method A method.
         * @param Target|null $target A target.
         * @return static The map.
         */
        protected function storeTarget (string $method, ?Target $target) : static {
            $this->validateTarget($target);
            $this->map[Helper::normaliseMethod($method)] = $target;
            return $this;
        }

        /**
         * Validates a target.
         * @param Target|null $target A target.
         */
        protected function validateTarget (?Target $target) : void {
            ;
        }

        /**
         * Gets the information of a map as an array.
         * @return array The map as an array.
         */
        public function getArray () : array {
            return $this->map;
        }

        /**
         * Gets the methods set in a map.
         * @return string[] The methods.
         */
        public function getMethods () : array {
            return array_keys($this->map);
        }

        /**
         * Gets the target for a method within a map.
         * @param string $method The method.
         * @return Target|null The target, if any.
         */
        public function getTarget (string $method) : ?Target {
            return $this->map[Helper::normaliseMethod($method)] ?? $this->map['*'] ?? null;
        }
        
        /**
         * Maps a method to a target.
         * @param string $method The method.
         * @param Target|null $target The target.
         * @return static The map.
         */
        public function setMethod (string $method, ?Target $target) : static {
            return $this->storeTarget($method, $target);
        }

        /**
         * Sets multiple methods of a map to one or more rules or commands.
         * @param string[] $methods The methods.
         * @param (?Target)[] $targets The targets.
         * @return static The map.
         * @throws TargetSizeMismatchException If the methods and targets arrays have different sizes.
         */
        public function setMethods (array $methods, array $targets) : static {
            # Throw an exception if the two arguments have different sizes.
            if (sizeof($methods) != sizeof($targets)) {
                throw new TargetSizeMismatchException("When setting multiple methods to multiple targets, the arrays must have equal sizes.");
            }

            # Iterate over the methods and assign the appropriate rule to each one.
            foreach ($methods as $i => $method) $this->setMethod($method, $targets[$i]);

            return $this;
        }

        /**
         * Unsets a method of a target map.
         * @param string $method The method.
         * @return static The target map.
         */
        public function unsetMethod (string $method) : static {
            unset($this->map[Helper::normaliseMethod($method)]);
            return $this;
        }

        /**
         * Unsets multiple methods of a target map.
         * @param array $methods The methods.
         * @return static The target map.
         */
        public function unsetMethods (array $methods) : static {
            foreach ($methods as $method) $this->unsetMethod($method);
            return $this;
        }
    }
?>