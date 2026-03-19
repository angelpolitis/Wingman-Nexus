<?php
    /**
     * Project Name:    Wingman Nexus - Duplicate Route Exception
     * Created by:      Angel Politis
     * Creation Date:   Mar 19 2026
     * Last Modified:   Mar 19 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus\Exceptions;

    # Import the following classes to the current scope.
    use InvalidArgumentException;
    use Wingman\Nexus\Interfaces\NexusException;

    /**
     * Thrown when a route with a given name has already been registered.
     *
     * Raised by {@see \Wingman\Nexus\Caching\CacheManager::buildCache()} during
     * route compilation when the same route name appears more than once within
     * the active rule set. Route names must be unique within a resolver.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class DuplicateRouteException extends InvalidArgumentException implements NexusException {}
