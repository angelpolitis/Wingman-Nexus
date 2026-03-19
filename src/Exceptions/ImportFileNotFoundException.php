<?php
    /**
     * Project Name:    Wingman Nexus - Import File Not Found Exception
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
     * Thrown when a rule import file does not exist on disk.
     *
     * Raised by {@see \Wingman\Nexus\RuleImporter} when the supplied file path
     * does not point to an existing, readable file before the path-escape check
     * or dispatch by extension is performed.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ImportFileNotFoundException extends RuntimeException implements NexusException {}
?>