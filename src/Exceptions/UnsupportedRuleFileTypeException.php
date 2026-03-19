<?php
    /**
     * Project Name:    Wingman Nexus - Unsupported Rule File Type Exception
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

    # Import the following classes to the currentArgumentException.
    use InvalidArgumentException;
    use Wingman\Nexus\Interfaces\NexusException;

    /**
     * Thrown when a rule import file has an extension that is not supported.
     *
     * Raised by {@see \Wingman\Nexus\RuleImporter::import()} when the file
     * extension is not among the registered handlers (e.g., neither `.json`
     * nor `.php`). Register additional handlers via
     * {@see \Wingman\Nexus\RuleImporter::withHandler()} to support extra formats.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class UnsupportedRuleFileTypeException extends InvalidArgumentException implements NexusException {}
?>