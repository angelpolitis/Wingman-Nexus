<?php
    /**
     * Project Name:    Wingman Nexus - Invalid Parameter Value Exception
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
     * Thrown when a URL parameter value does not satisfy the parameter's type constraint.
     *
     * Raised by {@see \Wingman\Nexus\UrlGenerator} during URL generation when
     * a caller-supplied value for a typed parameter fails the pattern check
     * registered for that type in the {@see \Wingman\Nexus\TypeRegistry}.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class InvalidParameterValueException extends InvalidArgumentException implements NexusException {}
?>