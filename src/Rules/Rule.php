<?php
    /**
     * Project Name:    Wingman Nexus - Rule
     * Created by:      Angel Politis
     * Creation Date:   Nov 23 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Rules namespace.
    namespace Wingman\Nexus\Rules;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Helper;

    /**
     * Represents a rule.
     * @package Wingman\Nexus\Rules
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class Rule {
        /**
         * The name of a rule.
         * @var string
         */
        protected string $name;

        /**
         * The pattern of a rule.
         * @var string
         */
        protected string $pattern;

        /**
         * The target of a rule.
         * @var mixed
         */
        protected mixed $target;

        /**
         * Creates a new rule.
         * @param string $name The name of a rule.
         * @param string $pattern The pattern that needs to be matched to cause the rule.
         * @param mixed $target $target The target of the rule.
         */
        public function __construct (string $name, string $pattern, mixed $target = null) {
            $this->name = $name;
            $this->pattern = $pattern;

            if (is_array($target)) {
                $normalisedTarget = [];
                foreach ($target as $method => $t) {
                    $normalisedTarget[Helper::normaliseMethod($method)] = $t;
                }
                $target = $normalisedTarget;
            }

            $this->target = $target;
        }

        /**
         * Creates a new rule.
         * @param string $name The name of a rule.
         * @param string $pattern The pattern that needs to be matched to cause the rule.
         * @param mixed $target $target The target of the rule.
         * @return static The new rule.
         */
        public static function from (string $name, string $pattern, mixed $target = null) : static {
            return new static($name, $pattern, $target);
        }

        /**
         * Creates a new rule using an expression.
         * @param string $name The name of a rule.
         * @param string $pattern The pattern of a rule.
         * @param string $expression The expression of a rule.
         * @return static The created rule.
         */
        public static function fromExpression (string $name, string $pattern, string $expression) : static {
            return static::from($name, $pattern, $expression);
        }

        /**
         * Creates a new rule using a map.
         * @param string $name The name of a rule.
         * @param string $pattern The pattern of a rule.
         * @param array $map The map of a rule.
         * @return static The created rule.
         */
        public static function fromMap (string $name, string $pattern, array $map) : static {
            return static::from($name, $pattern, $map);
        }

        /**
         * Gets the name of a rule.
         * @return string The name of the rule.
         */
        public function getName () : string {
            return $this->name;
        }

        /**
         * Gets the pattern of a rule.
         * @return string The pattern.
         */
        public function getPattern () : string {
            return $this->pattern;
        }

        /**
         * Gets the target of a rule.
         * @return string|array|null The target of the rule.
         */
        public function getTarget () : mixed {
            return $this->target;
        }
    }
?>