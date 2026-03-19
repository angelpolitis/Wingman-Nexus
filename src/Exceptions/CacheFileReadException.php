<?php
    /**
     * Project Name:    Wingman Nexus - Cache File Read Exception
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
     * Thrown when a cache file exists but cannot be read.
     *
     * Raised by {@see \Wingman\Nexus\Caching\Cacher::read()} when
     * {@see file_get_contents()} fails to read the contents of a located
     * cache file, typically due to a permissions or I/O issue.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CacheFileReadException extends RuntimeException implements NexusException {}
?>