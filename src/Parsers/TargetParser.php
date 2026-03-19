<?php
    /**
     * Project Name:    Wingman Nexus - Target Parser
     * Created by:      Angel Politis
     * Creation Date:   Nov 10 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Parsers namespace.
    namespace Wingman\Nexus\Parsers;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Interfaces\NexusException;
    use Wingman\Nexus\Interfaces\Target;
    use Wingman\Nexus\Objects\TargetMap;

    /**
     * Represents a target parser.
     * @package Wingman\Nexus\Parsers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    abstract class TargetParser {
        /**
         * The symbol that denotes a denied route when used as the entire target expression.
         * @var string
         */
        #[Configurable("nexus.symbols.deny")]
        protected string $denySymbol = '-';

        /**
         * The delimiters used to separate multiple commands within a single target expression.
         * @var array
         */
        #[Configurable("nexus.symbols.commandDelimiters")]
        protected array $commandDelimiters = [';'];

        /**
         * The delimiters used to separate multiple HTTP methods within a method key.
         * @var array
         */
        #[Configurable("nexus.symbols.methodDelimiters")]
        protected array $methodDelimiters = [',', '|'];

        /**
         * The class of the expected target.
         * @var string|null
         */
        protected ?string $targetClass = null;

        /**
         * Creates a new target parser.
         * @param array|Configuration $config A flat dot-notation array or Cortex `Configuration`
         *   instance used to override the default parser behaviour.
         * @param string|null $targetClass The class of the expected target.
         */
        public function __construct (array|Configuration $config = [], ?string $targetClass = null) {
            Configuration::hydrate($this, $config);
            $this->targetClass = $targetClass;
        }

        /**
         * Builds a regex that matches any of the given delimiter values.
         * @param array|string $values One or more delimiter strings.
         * @param string $padding Optional regex padding inserted before and after each value.
         * @return string The created regular expression.
         */
        protected function createRegexFromValues (array|string $values, string $padding = "") : string {
            if (!is_array($values)) $values = [$values];

            return "/$padding(?:" . implode('|', array_map("preg_quote", $values)) . ")$padding/";
        }

        /**
         * Parses a command.
         * @param string $value The value of the command.
         * @return Target|null The target.
         * @throws NexusException If the command is malformed or missing required fields.
         */
        abstract public function parseCommand (string $command) : ?Target;

        /**
         * Parses an expression.
         * @param string $expression An expression.
         * @return TargetMap A method-target map.
         * @throws NexusException If the expression is malformed.
         */
        public function parseExpression (string $expression) : TargetMap {
            $map = new TargetMap();

            if ($expression == $this->denySymbol) {
                $map->setMethod('*', null);
                return $map;
            }

            if ($expression == '*') $expression = "* *";

            $rawCommands = preg_split($this->createRegexFromValues($this->commandDelimiters, '\s*'), $expression);

            $commands = [];

            foreach ($rawCommands as $rawCommand) {
                $parts = preg_split("/\s+/", $rawCommand, 2);

                if (sizeof($parts) < 2) {
                    array_unshift($parts, '*');
                }

                $commands[] = [$parts[0] => $parts[1]];
            }

            $rule = array_merge(...$commands);
            $rule = array_change_key_case($rule);
            
            foreach ($rule as $methodKey => $value) {
                $methods = $this->parseMethodKey($methodKey);
                $value = $this->parseCommand($value);
                foreach ($methods as $method) $map->setMethod($method, $value);
            }

            return $map;
        }

        /**
         * Parses a map.
         * @param array $map A map.
         * @return TargetMap A method-target map.
         */
        public function parseMap (array $map) : TargetMap {
            $result = new TargetMap();

            foreach ($map as $methodKey => $value) {
                $methods = $this->parseMethodKey($methodKey);
                $value = is_string($value) ? $this->parseCommand($value) : $this->parseMapRule($value);
                foreach ($methods as $method) $result->setMethod($method, $value);
            }

            return $result;
        }

        /**
         * Parses a map rule.
         * @param array $map A map rule.
         * @return Target The target.
         * @throws NexusException If the map rule is missing required fields.
         */
        abstract public function parseMapRule (array $rule) : Target;

        /**
         * Parses a method key to extract the individual methods.
         * @param string $methodKey The method or methods, as a string.
         * @return array The methods.
         */
        public function parseMethodKey (string $methodKey) : array {
            $delimiterRegex = $this->createRegexFromValues($this->methodDelimiters);
            return preg_split($delimiterRegex, $methodKey);
        }
    }
?>