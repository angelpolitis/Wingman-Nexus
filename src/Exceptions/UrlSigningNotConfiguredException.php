<?php
    /**
     * Project Name:    Wingman Nexus - Url Signing Not Configured Exception
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
    use LogicException;
    use Wingman\Nexus\Interfaces\NexusException;

    /**
     * Thrown when a signed URL operation is attempted before URL signing has been configured.
     *
     * Raised by {@see \Wingman\Nexus\Router::assertUrlSigningConfigured()} when
     * {@see \Wingman\Nexus\Router::generateSignedUrl()} or
     * {@see \Wingman\Nexus\Router::validateSignedUrl()} is called without a
     * prior call to {@see \Wingman\Nexus\Router::configureUrlSigning()}.
     * This is a programming error that should be caught during development.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class UrlSigningNotConfiguredException extends LogicException implements NexusException {}
?>