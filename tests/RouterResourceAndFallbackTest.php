<?php
    /**
     * Project Name:    Wingman Nexus - Router Resource And Fallback Tests
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
    use Wingman\Nexus\Router;
    use Wingman\Nexus\Targets\RouteTarget;

    /**
     * Behaviour tests for Router resource registration and fallback routing.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouterResourceAndFallbackTest extends Test {
        /**
         * Ensures resource registration produces the expected CRUD route snapshot set.
         * @return void
         */
        #[Group("Router")]
        #[Define(
            name: "Router::addResource() — Creates Conventional CRUD Snapshots",
            description: "Resource registration should generate index/create/show/edit routes with expected methods and target actions."
        )]
        public function testAddResourceCreatesConventionalCrudSnapshots () : void {
            $router = new Router();

            $router->addResource("users", "App\\Controllers\\UsersController");

            $routes = $router->getRoutes();

            $this->assertCount(4, $routes, "addResource() should generate four grouped CRUD routes.");
            $this->assertTrue($routes[0]->name === "users.index", "The collection route should be named users.index.");
            $this->assertTrue($routes[0]->pattern === "/users", "The collection route should match /users.");
            $this->assertContains("GET", $routes[0]->methods, "The collection route should include GET for index.");
            $this->assertContains("POST", $routes[0]->methods, "The collection route should include POST for store.");
            $this->assertTrue(
                $routes[0]->targets["POST"]["action"] === "store",
                "The POST method should map to the store action."
            );

            $this->assertTrue($routes[1]->name === "users.create", "The create route should be named users.create.");
            $this->assertTrue($routes[1]->pattern === "/users/create", "The create route should match /users/create.");

            $this->assertTrue($routes[2]->name === "users.show", "The member route should be named users.show.");
            $this->assertTrue($routes[2]->pattern === "/users/{id}", "The member route should match /users/{id}.");
            $this->assertContains("PUT", $routes[2]->methods, "The member route should include PUT for update.");
            $this->assertContains("PATCH", $routes[2]->methods, "The member route should include PATCH for update.");
            $this->assertContains("DELETE", $routes[2]->methods, "The member route should include DELETE for destroy.");

            $this->assertTrue($routes[3]->name === "users.edit", "The edit route should be named users.edit.");
            $this->assertTrue($routes[3]->pattern === "/users/{id}/edit", "The edit route should match /users/{id}/edit.");
        }

        /**
         * Ensures fallback routes are marked as fallback snapshots and resolve when no normal route matches.
         * @return void
         */
        #[Group("Router")]
        #[Define(
            name: "Router::addFallback() — Registers Last Resort Route",
            description: "Fallback routes should appear as fallback snapshots and resolve for unmatched URLs."
        )]
        public function testAddFallbackRegistersLastResortRoute () : void {
            $router = new Router();

            $router
                ->addResource("users", "App\\Controllers\\UsersController")
                ->addFallback("fallback.catchAll", "/**", "App\\Controllers\\ErrorsController::notFound");

            $routes = $router->getRoutes();
            $lastRoute = end($routes);

            $this->assertTrue($lastRoute->isFallback === true, "Fallback snapshots should be marked as fallback routes.");
            $this->assertTrue($lastRoute->name === "fallback.catchAll", "The fallback route name should be preserved.");

            $result = $router->route("/unknown/path", "GET");

            $this->assertFalse($result->hasError(), "Fallback routing should resolve instead of returning not found.");
            $target = $result->getTarget();

            $this->assertInstanceOf(RouteTarget::class, $target, "Fallback resolution should return a RouteTarget.");
            $this->assertTrue(
                $target instanceof RouteTarget && $target->getAction() === "notFound",
                "Fallback target action should match the configured action."
            );
        }
    }
?>