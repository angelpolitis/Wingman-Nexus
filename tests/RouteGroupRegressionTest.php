<?php
    /**
     * Project Name:    Wingman Nexus - Route Group Regression Tests
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Tests namespace.
    namespace Wingman\Nexus\Tests;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\ImporterNotConfiguredException;
    use Wingman\Argus\Attributes\Define;
    use Wingman\Argus\Attributes\Group;
    use Wingman\Argus\Test;
    use Wingman\Nexus\AttributeScanner;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\RouteGroup;

    /**
     * Regression tests for route group behaviour.
     * Covers importer guard, name prefix application, and nested group composition.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteGroupRegressionTest extends Test {
        #[Group("Regression")]
        #[Define(
            name: "RouteGroup::import() — Throws Without Importer",
            description: "Calling import() before withImporter() should throw an ImporterNotConfiguredException instead of causing an uninitialised-property fatal error."
        )]
        public function testImportThrowsWithoutImporter () : void {
            $group = new RouteGroup(new AttributeScanner());

            $thrown = false;

            try {
                $group->import(RuleType::ROUTE, __DIR__ . "/fixtures/nonexistent.json");
            }
            catch (ImporterNotConfiguredException $e) {
                $thrown = true;
            }

            $this->assertTrue($thrown, "RouteGroup::import() should throw ImporterNotConfiguredException when importer is not configured.");
        }

        #[Group("Regression")]
        #[Define(
            name: "RouteGroup::buildRules() — Applies Name Prefix",
            description: "Name prefix must be prepended to route names during buildRules()."
        )]
        public function testBuildRulesAppliesNamePrefix () : void {
            $group = new RouteGroup(new AttributeScanner());

            $group
                ->withNamePrefix("api.v1.")
                ->add("users.index", "/users", "UsersController::index");

            $rules = $group->buildRules();

            $this->assertTrue(count($rules) === 1, "buildRules() should return one rule.");
            $this->assertTrue($rules[0]->getName() === "api.v1.users.index", "buildRules() should prepend the configured name prefix.");
        }

        #[Group("Regression")]
        #[Define(
            name: "RouteGroup::group() — Composes Outer And Inner Prefixes",
            description: "Nested groups should combine URL and name prefixes in registration order."
        )]
        public function testNestedGroupComposesOuterAndInnerPrefixes () : void {
            $group = new RouteGroup(new AttributeScanner());

            $group
                ->withPrefix("/api")
                ->withNamePrefix("api.")
                ->group(function (RouteGroup $inner) {
                    $inner
                        ->withPrefix("/v1")
                        ->withNamePrefix("v1.")
                        ->add("users.show", "/users/{id}", "UsersController::show");
                });

            $rules = $group->buildRules();

            $this->assertTrue(count($rules) === 1, "Nested group should yield one final rule.");
            $this->assertTrue($rules[0]->getName() === "api.v1.users.show", "Nested groups should combine name prefixes.");
            $this->assertTrue($rules[0]->getPattern() === "/api/v1/users/{id}", "Nested groups should combine URL prefixes.");
        }
    }
?>