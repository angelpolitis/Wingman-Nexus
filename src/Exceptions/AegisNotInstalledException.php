<?php
    /**
     * Project Name:    Wingman Nexus - Aegis Not Installed Exception
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
     * Thrown when signed URL generation is attempted but the Wingman Aegis package is not installed.
     *
     * Raised by {@see \Wingman\Nexus\Bridge\Aegis\UrlSigner} when the Wingman
     * Aegis signing service class is not resolvable, indicating the optional
     * `wingman/aegis` dependency has not been installed. Install the package and
     * configure it via {@see \Wingman\Nexus\Router::configureUrlSigning()}.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AegisNotInstalledException extends LogicException implements NexusException {}
?>