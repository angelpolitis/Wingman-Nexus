<?php
    /**
     * Project Name:    Wingman Nexus - Invalid Cache File Exception
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
     * Thrown when a file does not have the expected Nexus cache structure.
     *
     * Raised by {@see \Wingman\Nexus\Caching\Cacher::read()} when the file is
     * readable but does not begin with the Nexus cache header, indicating it
     * was not produced by the Nexus caching subsystem.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class InvalidCacheFileException extends RuntimeException implements NexusException {}
?>