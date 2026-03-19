<?php
    /**
     * Project Name:    Wingman Nexus - Rewrite Target Parser
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
    use Wingman\Nexus\Exceptions\EmptyRuleCommandException;
    use Wingman\Nexus\Exceptions\InvalidRewriteCommandException;
    use Wingman\Nexus\Exceptions\InvalidRuleFormatException;
    use Wingman\Nexus\Exceptions\MissingRuleFieldException;
    use Wingman\Nexus\Targets\RewriteTarget;

    /**
     * Represents a rewrite target parser.
     * @package Wingman\Nexus\Parsers
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RewriteTargetParser extends TargetParser {
        /**
         * Parses a command.
         * @param string $value The value of the command.
         * @return RewriteTarget The target.
         * @throws Exception If the command is empty or invalid.
         */
        public function parseCommand (string $command) : RewriteTarget {
            $result = [];

            $command = trim($command);

            if (empty($command)) {
                throw new EmptyRuleCommandException("The command cannot be empty.");
            }

            $commandArray = preg_split("/\s+/", $command);
            
            if (sizeof($commandArray) > 1) {
                throw new InvalidRewriteCommandException("A rewrite command must only specify the target path.");
            }

            $result["path"] = $commandArray[0];

            return new RewriteTarget($result["path"]);
        }

        /**
         * Parses a map rule.
         * @param array $map A map rule.
         * @return RewriteTarget The target.
         * @throws InvalidRuleFormatException If the rule isn't an array.
         * @throws MissingRuleFieldException If the rule is missing required fields.
         */
        public function parseMapRule (array $rule) : RewriteTarget {
            if (!is_array($rule)) throw new InvalidRuleFormatException("A map rule must be a string command or array.");

            if (!isset($rule["path"])) {
                throw new MissingRuleFieldException("A rewrite map rule must be have the target 'path' defined.");
            }

            $args = [$rule["path"]];

            if (isset($rule["preservesQuery"])) $args[] = $rule["preservesQuery"];

            return new RewriteTarget(...$args);
        }
    }
?>