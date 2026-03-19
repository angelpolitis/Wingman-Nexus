<?php
    /**
     * Project Name:    Wingman Nexus - Importer Not Configured Exception
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
     * Thrown when import() is called on a RouteGroup that has no RuleImporter configured.
     *
     * Raised by {@see \Wingman\Nexus\RouteGroup::import()} when the internal
     * {@see \Wingman\Nexus\RuleImporter} has not been injected via
     * {@see \Wingman\Nexus\RouteGroup::withImporter()} before attempting
     * to load rule files. This is a programming error detectable at development time.
     * @package Wingman\Nexus\Exceptions
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ImporterNotConfiguredException extends LogicException implements NexusException {}
?>