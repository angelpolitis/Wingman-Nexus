<?php
    /**
     * Project Name:    Wingman Nexus - Invalid Import Content Exception
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
     * Thrown when a rule import file does not return the expected collection type.
     *
     * Raised by {@see \Wingman\Nexus\RuleImporter} when a JSON import file's
     * top-level value is not an array, or when a PHP import file does not
     * {@return} an iterable value. Rule files must produce a traversable
     * collection of rule definitions.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class InvalidImportContentException extends InvalidArgumentException implements NexusException {}
?>