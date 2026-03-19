<?php
    /**
     * Project Name:    Wingman Nexus - Router Deferred Import Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Tests namespace.
    namespace Wingman\Nexus\Tests;

    # Import the following classes to the current scope.
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Router;
    use Wingman\Nexus\Targets\RouteTarget;

    /**
     * Behaviour tests for lazy rule importing in the router.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouterDeferredImportTest extends Test {
        /**
         * Ensures deferred groups are imported only when the URL prefix matches.
         * @return void
         */
        #[Group("Router")]
        #[Define(
            name: "Router::importLazy() — Loads Rules Only For Matching Prefix",
            description: "Deferred imports should stay unloaded for non-matching URLs and load once a matching URL is routed."
        )]
        public function testImportLazyLoadsRulesOnlyForMatchingPrefix () : void {
            $router = new Router();
            $fixtureFile = __DIR__ . "/Fixtures/LazyRouteRules.php";

            $router->importLazy("/api", RuleType::ROUTE, $fixtureFile);

            $firstResult = $router->route("/web/ping", "GET");

            $this->assertTrue($firstResult->hasError(), "A non-matching prefix should not trigger deferred import.");

            $secondResult = $router->route("/api/ping", "GET");

            $this->assertFalse($secondResult->hasError(), "A matching prefix should trigger deferred import and resolve.");
            $target = $secondResult->getTarget();

            $this->assertInstanceOf(RouteTarget::class, $target, "Resolved lazy route should return a RouteTarget.");
            $this->assertTrue(
                $target instanceof RouteTarget && $target->getAction() === "ping",
                "Lazy route should resolve to the imported target action."
            );
        }

        /**
         * Ensures URL generation forces all deferred groups to load before lookup.
         * @return void
         */
        #[Group("Router")]
        #[Define(
            name: "Router::generateUrl() — Loads All Deferred Rules",
            description: "Generating a URL should eagerly import all deferred route groups so named lookups can succeed."
        )]
        public function testGenerateUrlLoadsAllDeferredRules () : void {
            $router = new Router();
            $fixtureFile = __DIR__ . "/Fixtures/LazyRouteRules.php";

            $router->importLazy("/api", RuleType::ROUTE, $fixtureFile);

            $url = $router->generateUrl("lazy.api.ping");

            $this->assertTrue($url === "/api/ping", "generateUrl() should resolve routes coming from deferred imports.");
        }
    }
?>