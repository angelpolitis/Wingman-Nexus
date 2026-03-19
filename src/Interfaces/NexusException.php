<?php
    /**
     * Project Name:    Wingman Nexus - Nexus Exception
     * Created by:      Angel Politis
     * Creation Date:   Mar 19 2026
     * Last Modified:   Mar 19 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Interfaces namespace.
    namespace Wingman\Nexus\Interfaces;

    # Import the following classes to the current scope.
    use Throwable;

    /**
     * Marker interface for all Nexus routing exceptions.
     *
     * Every custom exception thrown by the Nexus package implements this
     * interface, allowing integrators to catch all Nexus-related errors at
     * any desired level of granularity — either the specific exception class
     * (e.g. {@see \Wingman\Nexus\Exceptions\RouteNotFoundException}) or the
     * entire package family via {@see NexusException}.
     * @package Wingman\Nexus\Interfaces
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    interface NexusException extends Throwable {}
?>