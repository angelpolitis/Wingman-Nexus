<?php
    /**
     * Project Name:    Wingman Nexus - Wildcard Value Count Exception
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
     * Thrown when the number of values supplied for a wildcard parameter does not match the wildcard count.
     *
     * Raised by {@see \Wingman\Nexus\UrlGenerator} when an array of values is
     * provided for a wildcard segment but the array length differs from the
     * number of wildcard occurrences in the route pattern.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class WildcardValueCountException extends InvalidArgumentException implements NexusException {}
?>