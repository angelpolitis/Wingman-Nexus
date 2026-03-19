<?php
    /**
     * Project Name:    Wingman Nexus - Empty Rule Command Exception
     * Created by:      Angel Politis
     * Creation Date:   Mar 19 2026
     * Last Modified:   Mar 19 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Exceptions namespace.
    namespace Wingman\Nexus\Exceptions;

    # Import the following classes to the current scope.
    use InvalidArgumentException;
    use Wingman\Nexus\Interfaces\NexusException;

    /**
     * Thrown when a redirect or rewrite rule command string resolves to an empty array.
     *
     * Raised by the rule target parsers
     * ({@see \Wingman\Nexus\Parsers\RedirectTargetParser} and
     * {@see \Wingman\Nexus\Parsers\RewriteTargetParser}) when the command
     * string, after splitting on the pipe delimiter, yields no usable tokens.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class EmptyRuleCommandException extends InvalidArgumentException implements NexusException {}
?>