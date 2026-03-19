<?php
    /**
     * Project Name:    Wingman Nexus - Invocation Argument Exception
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
    use RuntimeException;
    use Wingman\Nexus\Interfaces\NexusException;

    /**
     * Thrown when an anonymous route target is invoked with the wrong number of arguments.
     *
     * Raised by {@see \Wingman\Nexus\Targets\AnonymousTarget::invoke()} when
     * the number of values extracted from the matched URL does not correspond
     * to the number of arguments expected by the wrapped callable.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class InvocationArgumentException extends RuntimeException implements NexusException {}
?>