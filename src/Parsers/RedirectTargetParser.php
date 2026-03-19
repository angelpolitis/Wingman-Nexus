<?php
    /**
     * Project Name:    Wingman Nexus - Redirect Target Parser
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
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Exceptions\EmptyRuleCommandException;
    use Wingman\Nexus\Exceptions\InvalidRuleFormatException;
    use Wingman\Nexus\Exceptions\InvalidStatusCodeException;
    use Wingman\Nexus\Exceptions\MissingRuleFieldException;
    use Wingman\Nexus\Targets\RedirectTarget;

    /**
     * Represents a redirect target parser.
     * @package Wingman\Nexus\Parsers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RedirectTargetParser extends TargetParser {
        /**
         * The regular expression used to validate HTTP status codes in redirect target commands.
         * @var string
         */
        #[Configurable("nexus.regex.httpStatus")]
        protected string $httpStatusRegex = '^(1\\d\\d|2[0-9]{2}|3[0-9]{2}|4[0-9]{2}|5[0-9]{2})$';
        /**
         * Parses a command.
         * @param string $value The value of the command.
         * @return RedirectTarget The target.
         * @throws Exception If the command is empty or invalid.
         */
        public function parseCommand (string $command) : RedirectTarget {
            $result = [];

            $command = trim($command);

            if (empty($command)) {
                throw new EmptyRuleCommandException("The command cannot be empty.");
            }

            $commandArray = preg_split("/\s+/", $command);
            
            if (sizeof($commandArray) >= 2) {
                if (!preg_match("/" . $this->httpStatusRegex . "/", $commandArray[0])) {
                    throw new InvalidStatusCodeException("When the command is composed of two items, the first must be an HTTP status code.");
                }

                $result["status"] = intval($commandArray[0]);

                $commandArray = array_slice($commandArray, 1, 1);
            }

            $result["path"] = $commandArray[0];

            return new RedirectTarget($result["path"], $result["status"] ?? null);
        }

        /**
         * Parses a map rule.
         * @param array $map A map rule.
         * @return RedirectTarget The target.
         * @throws InvalidRuleFormatException If the rule isn't an array.
         * @throws MissingRuleFieldException If the rule is missing required fields.
         * @throws InvalidStatusCodeException If the status code is invalid.
         */
        public function parseMapRule (array $rule) : RedirectTarget {
            if (!is_array($rule)) throw new InvalidRuleFormatException("A map rule must be a string command or array.");

            if (!isset($rule["path"])) {
                throw new MissingRuleFieldException("A redirect map rule must be have the target 'path' defined.");
            }

            if (isset($rule["status"]) && !is_numeric($rule["status"])) {
                throw new InvalidStatusCodeException("When provided, the status code must be numeric.");
            }

            $args = [$rule["path"]];

            if (isset($rule["status"])) $args[] = $rule["status"];
            if (isset($rule["headers"])) $args[] = $rule["headers"];
            if (isset($rule["preservesQuery"])) $args[] = $rule["preservesQuery"];

            return new RedirectTarget(...$args);
        }
    }
?>