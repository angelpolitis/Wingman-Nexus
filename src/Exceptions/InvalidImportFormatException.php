<?php
    /**
     * Project Name:    Wingman Nexus - Invalid Import Format Exception
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
     * Thrown when a JSON rule import file cannot be decoded.
     *
     * Raised by {@see \Wingman\Nexus\RuleImporter::importJSON()} when
     * {@see json_decode()} fails and {@see json_last_error()} returns a
     * non-zero code, indicating malformed JSON in the rule source file.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class InvalidImportFormatException extends RuntimeException implements NexusException {}
?>