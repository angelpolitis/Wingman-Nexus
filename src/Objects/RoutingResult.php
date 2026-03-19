<?php
    /**
     * Project Name:    Wingman Nexus - Routing Result
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

    # Import the following classes to the current scope.
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Interfaces\Target;

    /**
     * Represents the resulting of the routing process.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RoutingResult {
        /**
         * The target of a result.
         * @var Target|null
         */
        protected ?Target $target;

        /**
         * The arguments of a result.
         * @var ArgumentList|null
         */
        protected ?ArgumentList $arguments;

        /**
         * The error of a result.
         * @var RoutingError
         */
        protected ?RoutingError $error = null;

        /**
         * The intermediate results of a result.
         * @var static[]
         */
        protected array $steps;

        /**
         * Creates a new routing result.
         * @param Target|null $target The target of the result.
         * @param ArgumentList|null $args The arguments of the result.
         * @param static[] $steps The intermediate results of a result.
         */
        public function __construct (?Target $target = null, ?ArgumentList $args = null, array $steps = []) {
            $this->target = $target;
            $this->arguments = $args;
            $this->steps = $steps;
        }

        /**
         * Creates a new routing result.
         * @param Target $target The target of the result.
         * @param ArgumentList|null $args The arguments of the result.
         * @param static[] $steps The intermediate results of a result.
         * @return static The new result.
         */
        public static function from (Target $target, ArgumentList $args, array $steps = []) {
            return new static($target, $args, $steps);
        }

        /**
         * Gets the error of a result.
         * @return RoutingError The error.
         */
        public function getError () : ?RoutingError {
            return $this->error;
        }

        /**
         * Gets the intermediate results of a result.
         * @return static[] The intermediate results of the result.
         */
        public function getSteps () : array {
            return $this->steps;
        }

        /**
         * Gets the arguments of a result.
         * @return ArgumentList|null The arguments of the result.
         */
        public function getArgs () : ?ArgumentList {
            return $this->arguments;
        }

        /**
         * Gets the target of a result.
         * @return Target|null The target, or `null` if the result represents an error.
         */
        public function getTarget () : ?Target {
            return $this->target;
        }

        /**
         * Checks whether a result has an error.
         * @return bool Whether the result has an error.
         */
        public function hasError () : bool {
            return isset($this->error);
        }

        /**
         * Creates a new routing result that has an error.
         * @param RoutingError $error The error type.
         * @param static[] The intermediate results of the result.
         * @return static The new routing result.
         */
        public static function withError (RoutingError $error, array $steps = []) : static {
            $result = new static();
            $result->error = $error;
            $result->steps = $steps;
            return $result;
        }

        /**
         * Creates a new routing result out of an existing one that contains the specified intermediate results.
         * @param static $result The result.
         * @param static[] $steps The intermediate results of the result.
         * @return static The new routing result.
         */
        public static function withSteps (self $result, array $steps = []) : static {
            $result = clone $result;
            $result->steps = $steps;
            return $result;
        }
    }
?>