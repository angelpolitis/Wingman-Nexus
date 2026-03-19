<?php
    /**
     * Project Name:    Wingman Nexus - Attribute Scanner Fixture Controller
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Tests.Fixtures namespace.
    namespace Wingman\Nexus\Tests\Fixtures;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Attributes\Route;
    use Wingman\Nexus\Enums\HttpMethod;
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;

    /**
     * Fixture controller used to verify attribute scanning behaviour.
     * @package Wingman\Nexus\Tests\Fixtures
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AttributeScannerFixtureController {
        /**
         * Example action used by attribute scanning tests.
         * @return void
         */
        #[Route(
            pattern: "/docs/{id:int}",
            methods: [HttpMethod::GET, "POST"],
            middleware: ["auth"],
            tags: ["docs"],
            headers: ["X-Fixture" => "yes"],
            contentTypes: ["application/json"],
            queryArgsPlacement: RouteTargetQueryArgsPlacement::BEFORE,
            preservesQuery: false
        )]
        public function showDocument () : void {
            ;
        }
    }
?>