<?php
    /**
     * Project Name:    Wingman Nexus - Attribute Scanner And Enum Tests
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
    use Wingman\Nexus\AttributeScanner;
    use Wingman\Nexus\Enums\HttpMethod;
    use Wingman\Nexus\Enums\ResourceAction;
    use Wingman\Nexus\Tests\Fixtures\AttributeScannerFixtureController;

    require_once __DIR__ . "/Fixtures/AttributeScannerFixtureController.php";

    /**
     * Regression and behaviour tests for attribute scanning and enum utilities.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AttributeScannerAndEnumsTest extends Test {
        /**
         * Ensures scanner-generated route names and metadata are derived correctly when no explicit name is provided.
         * @return void
         */
        #[Group("Scanner")]
        #[Define(
            name: "AttributeScanner::scan() — Derives Route Names And Metadata",
            description: "Scanning route attributes should derive default names and preserve method-level metadata in the generated target map."
        )]
        public function testAttributeScannerDerivesRouteNamesAndMetadata () : void {
            $scanner = new AttributeScanner();
            $rules = $scanner->scan(AttributeScannerFixtureController::class);

            $this->assertCount(1, $rules, "Fixture scanning should produce exactly one route rule.");

            $rule = $rules[0];
            $target = $rule->getTarget();

            $this->assertTrue(
                $rule->getName() === "wingman.nexus.tests.fixtures.attributescannerfixturecontroller.showDocument",
                "The scanner should derive a lowercased dot-notation route name when none is provided."
            );
            $this->assertTrue($rule->getPattern() === "/docs/{id:int}", "The route pattern should match the attribute pattern.");
            $this->assertArrayHasKey("GET", $target, "The generated target map should include GET.");
            $this->assertArrayHasKey("POST", $target, "The generated target map should include POST.");
            $this->assertTrue($target["GET"]["includeQueryArgs"] === "prepend", "BEFORE placement should map to prepend includeQueryArgs.");
            $this->assertTrue($target["GET"]["preservesQuery"] === false, "The preservesQuery flag should be preserved.");
            $this->assertContains("auth", $target["GET"]["middleware"], "Middleware metadata should be preserved.");
            $this->assertContains("docs", $target["GET"]["tags"], "Tag metadata should be preserved.");
            $this->assertTrue($target["GET"]["headers"]["X-Fixture"] === "yes", "Header metadata should be preserved.");
        }

        /**
         * Ensures HTTP method helpers and resource action resolution remain consistent.
         * @return void
         */
        #[Group("Enums")]
        #[Define(
            name: "HttpMethod And ResourceAction — Resolve And Method Mapping",
            description: "HTTP method helpers should exclude OPTIONS for routable methods and resource actions should resolve case-insensitively."
        )]
        public function testHttpMethodAndResourceActionResolveAndMappings () : void {
            $routable = HttpMethod::getRoutable();

            $this->assertNotContains("OPTIONS", $routable, "Routable methods should exclude OPTIONS.");
            $this->assertContains("GET", $routable, "Routable methods should include GET.");
            $this->assertTrue(HttpMethod::resolve("post") === HttpMethod::POST, "resolve() should be case-insensitive for method names.");

            $resolvedAction = ResourceAction::resolve("Update");
            $this->assertTrue($resolvedAction === ResourceAction::UPDATE, "ResourceAction::resolve() should be case-insensitive.");
            $this->assertContains("PUT", $resolvedAction->getHttpMethods(), "UPDATE should include PUT.");
            $this->assertContains("PATCH", $resolvedAction->getHttpMethods(), "UPDATE should include PATCH.");
        }
    }
?>