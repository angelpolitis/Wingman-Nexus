<?php
    /**
     * Project Name:    Wingman Nexus - Cache Path Escape Exception
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
     * Thrown when a resolved cache path escapes the configured cache root directory.
     *
     * Raised by {@see \Wingman\Nexus\Caching\Cacher} when a path traversal
     * attempt is detected during cache read or write operations. This acts as a
     * security guard against directory traversal in cache key inputs.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CachePathEscapeException extends RuntimeException implements NexusException {}
?>