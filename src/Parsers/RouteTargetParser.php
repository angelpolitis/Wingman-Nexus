<?php
    /**
     * Project Name:    Wingman Nexus - Route Target Parser
     * Created by:      Angel Politis
     * Creation Date:   Nov 24 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Parsers namespace.
    namespace Wingman\Nexus\Parsers;

    # Import the following classes to the current scope.
    use Closure;
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;
    use Wingman\Nexus\Exceptions\InvalidRuleFormatException;
    use Wingman\Nexus\Exceptions\MissingRuleFieldException;
    use Wingman\Nexus\Interfaces\Target;
    use Wingman\Nexus\Objects\TargetMap;
    use Wingman\Nexus\Targets\AnonymousTarget;
    use Wingman\Nexus\Targets\RouteTarget;

    /**
     * Represents a route target parser.
     * @package Wingman\Nexus\Parsers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteTargetParser extends TargetParser {
        /**
         * The operators used to separate a class name from its action in a route command.
         * @var array
         */
        #[Configurable("nexus.symbols.classOperators")]
        protected array $classOperators = ['::', '@'];
        /**
         * Parses a command.
         * @param string $value The value of the command.
         * @return Target|null The target.
         * @throws Exception If the class or action is missing.
         */
        public function parseCommand (string $command) : ?Target {
            # Define the return value.
            $result = [];

            # Turn the command into an array by splitting it at the spaces.
            $commandArray = preg_split("/\s+/", $command);

            # Check whether the command has one item.
            if (sizeof($commandArray) === 1) {
                # Use the class operators to compile a regular expression.
                $operatorRegex = $this->createRegexFromValues($this->classOperators);

                # If the command is the wildcard, consider the namespace, class and action wildcards.
                if ($commandArray[0] == '*') $values = ['*', '*'];
                else {
                    # Return null if the command is the DENY symbol.
                    if ($commandArray[0] == $this->denySymbol) return null;
        
                    # Explode the command at the class operator to separate the class from the action.
                    $values = preg_split($operatorRegex, $commandArray[0]);
                }

                # Assume the value is an action if there are fewer than 2 parts.
                if (sizeof($values) < 2) {
                    $result["action"] = $values[0];
                }
                else {
                    # Replace all dots with backslashes to allow dots to be used to compose the qualified name of classes.
                    $values[0] = str_replace('.', "\\", $values[0]);
    
                    # Set the class, action and arguments in the result.
                    $result["class"] = $values[0];
                    $result["action"] = $values[1];
                    $result["arguments"] = [];
                }
            }

            return new RouteTarget(
                $result["class"] ?? null,
                $result["action"] ?? null,
                $result["arguments"] ?? []
            );
        }

        /**
         * Parses a map.
         * @param array $map A map.
         * @return TargetMap A route method-target map.
         */
        public function parseMap (array $map) : TargetMap {
            $result = new TargetMap();

            foreach ($map as $methodKey => $value) {
                # Parse the method key to get the individual methods.
                $methods = $this->parseMethodKey($methodKey);

                # If the value isn't a closure, parse it accordingly as a command or map rule.
                if (!($value instanceof Closure)) {
                    $value = is_string($value) ? $this->parseCommand($value) : $this->parseMapRule($value);
                }
                else {
                    $value = new AnonymousTarget($value, $methodKey);
                }

                foreach ($methods as $method) $result->setMethod($method, $value);
            }

            return $result;
        }

        /**
         * Parses a map rule.
         * @param array $map A map rule.
         * @return RouteTarget The target.
         * @throws InvalidRuleFormatException If the rule isn't an array.
         * @throws MissingRuleFieldException If the rule is missing required fields.
         */
        public function parseMapRule (array $rule) : RouteTarget {
            # Throw an exception if the rule isn't an array.
            if (!is_array($rule)) throw new InvalidRuleFormatException("A map rule must be a string command or array.");

            # Throw an exception if the rule doesn't have an action specified.
            if (!isset($rule["action"])) {
                throw new MissingRuleFieldException("A route map rule must have an 'action' specified.");
            }

            if (is_array($rule["action"])) {
                $actionItemsCount = sizeof($rule["action"]);
                if ($actionItemsCount == 0) {
                    throw new MissingRuleFieldException("A route map rule must have an 'action' specified.");
                }
                elseif ($actionItemsCount == 1) {
                    $rule["class"] = null;
                    $rule["action"] = $rule["action"][0];
                }
                else {
                    $rule["class"] = $rule["action"][1];
                    $rule["action"] = $rule["action"][0];
                }
            }

            # Replace all dots with backslashes to allow dots to be used to compose the qualified name of classes.
            if (isset($rule["class"])) $rule["class"] = str_replace('.', "\\", strval($rule["class"]));

            # Normalise whether the query arguments must be included in the arguments.
            $includeQueryArgs = $rule["includeQueryArgs"] ?? false;

            # Normalise the value as either 'prepend' or 'append', with true meaning 'append'.
            $queryArgsPlacement = match ($includeQueryArgs) {
                false => RouteTargetQueryArgsPlacement::NONE,
                true, "append" => RouteTargetQueryArgsPlacement::AFTER,
                "prepend" => RouteTargetQueryArgsPlacement::BEFORE,
                default => RouteTargetQueryArgsPlacement::NONE
            };

            return new RouteTarget(
                $rule["class"] ?? null,
                $rule["action"],
                $rule["arguments"] ?? [],
                $queryArgsPlacement,
                $rule["middleware"] ?? [],
                $rule["tags"] ?? [],
                $rule["headers"] ?? [],
                $rule["contentTypes"] ?? [],
                $rule["preservesQuery"] ?? true
            );
        }
    }
?>