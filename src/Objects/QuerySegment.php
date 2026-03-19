<?php
    /**
     * Project Name:    Wingman Nexus - Query Segment
     * Created by:      Angel Politis
     * Creation Date:   Jan 27 2026
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\MissingParameterException;

    /**
     * Represents a query segment.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class QuerySegment {
        /**
         * The parameters in a segment.
         * @var Parameter[]
         */
        protected array $parameters = [];

        /**
         * The raw segment string.
         */
        protected string $raw;

        /**
         * Indicates whether a segment contains no parameters.
         * @var bool
         */
        protected bool $static = false;

        /**
         * Creates a new query segment.
         * @param string $raw The raw segment string.
         * @param Parameter[] $parameters The parameters in a segment.
         * @param bool $static Indicates whether a segment contains no parameters.
         */
        public function __construct (string $raw, array $parameters = []) {
            $this->raw = $raw;
            $this->parameters = $parameters;
            $this->static = count($parameters) === 0;
        }

        /**
         * Serialises a query segment.
         * @return array The serialised definition.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a query segment.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->parameters = $data["parameters"];
            $this->raw = $data["raw"];
            $this->static = $data["static"];
        }

        /**
         * Creates a new query segment (for var_export).
         * @param array $properties The properties used to create a new query segment.
         */
        public static function __set_state (array $properties) : static {
            return new static(
                $properties["raw"],
                $properties["parameters"],
            );
        }

        /**
         * Gets a query segment as an array.
         * @return array The information of a query segment as an array.
         */
        public function getArray () : array {
            return [
                "parameters" => $this->parameters,
                "raw" => $this->raw,
                "static" => $this->static
            ];
        }
    
        /**
         * Generates a segment by substituting parameter values.
         * @param array $values The parameter values.
         * @return string|null The generated segment or `null` if a required parameter is missing.
         * @throws Exception If a required parameter is missing.
         */
        public function generate (array $values) : ?string {
            if ($this->static) return $this->raw;
    
            $result = $this->raw;
            foreach ($this->parameters as $parameter) {
                $val = $values[$parameter->getName()] ?? null;
                
                if ($val === null) {
                    if ($parameter->isOptional()) return null;
                    throw new MissingParameterException("Missing required parameter '{$parameter->getName()}'.");
                }
                
                $result = str_replace($parameter->getRaw(), (string) $val, $result);
            }
            return $result;
        }

        /**
         * Gets the parameters in a segment.
         * @return Parameter[] The parameters.
         */
        public function getParameters () : array {
            return $this->parameters;
        }

        /**
         * Gets the raw segment string.
         * @return string The raw segment.
         */
        public function getRaw () : string {
            return $this->raw;
        }

        /**
         * Determines whether a segment contains no parameters.
         * @return bool Whether the segment is static.
         */
        public function isStatic () : bool {
            return $this->static;
        }
    }
?>