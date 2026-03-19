<?php
    /**
     * Project Name:    Wingman Nexus - Verix Not Installed Exception
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
     * Thrown when schema-based parameter validation is attempted but the Wingman Verix package is not installed.
     *
     * Raised by {@see \Wingman\Nexus\Bridge\Verix\Validator} when the Verix
     * {@see \Wingman\Verix\Schema} class is not resolvable, indicating the
     * optional `wingman/verix` dependency has not been installed. The Verix
     * package is required only when route parameters declare a `schema` constraint.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class VerixNotInstalledException extends LogicException implements NexusException {}
?>