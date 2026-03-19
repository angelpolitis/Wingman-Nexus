<?php
    /**
     * Project Name:    Wingman Nexus - Cache Write Exception
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
     * Thrown when a cache file cannot be written or atomically moved into place.
     *
     * Raised by {@see \Wingman\Nexus\Caching\Cacher::write()} when
     * {@see file_put_contents()} fails to write the temporary cache file, or
     * when the subsequent {@see rename()} to atomically replace the target fails.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class CacheWriteException extends RuntimeException implements NexusException {}
?>