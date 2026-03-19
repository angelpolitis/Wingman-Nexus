<?php
    /**
     * Project Name:    Wingman Nexus - Regression State Round-Trip Tests
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
    use Wingman\Nexus\Enums\RouteQueryRequirement;
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;
    use Wingman\Nexus\Objects\CompiledRoute;
    use Wingman\Nexus\Objects\RouteDefinition;
    use Wingman\Nexus\Objects\URI;
    use Wingman\Nexus\Targets\RouteTarget;

    /**
     * Regression tests for __set_state() restoration paths.
     * Locks the enum conversion and constructor argument order fixes.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RegressionStateRoundTripTest extends Test {
        #[Group("Regression")]
        #[Define(
            name: "CompiledRoute::__set_state() — Restores Fragment And Query Requirement",
            description: "CompiledRoute restoration must keep fragmentPattern in the right slot and rehydrate queryRequirement as enum."
        )]
        public function testCompiledRouteSetStateRestoresFragmentAndRequirement () : void {
            $original = new CompiledRoute(
                null,
                null,
                null,
                null,
                null,
                "/^\\/orders\\/([0-9]+)$/u",
                ["/^status=(open|closed)$/u"],
                "/^section-[a-z]+$/u",
                [],
                [],
                RouteQueryRequirement::REQUIRED
            );

            $restored = CompiledRoute::__set_state($original->getArray());
            $restoredArray = $restored->getArray();

            $this->assertTrue(
                $restoredArray["fragmentPattern"] === "/^section-[a-z]+$/u",
                "CompiledRoute::__set_state() should preserve fragmentPattern."
            );
            $this->assertTrue(
                $restored->getQueryRequirement() === RouteQueryRequirement::REQUIRED,
                "CompiledRoute::__set_state() should restore queryRequirement as RouteQueryRequirement enum."
            );
        }

        #[Group("Regression")]
        #[Define(
            name: "RouteDefinition::__set_state() — Restores Query Requirement Enum",
            description: "RouteDefinition restoration must convert queryRequirement scalar to RouteQueryRequirement enum."
        )]
        public function testRouteDefinitionSetStateRestoresQueryRequirementEnum () : void {
            $definition = new RouteDefinition(
                "/users/{id}",
                URI::from("/users/{id}"),
                [],
                [],
                RouteQueryRequirement::FORBIDDEN
            );

            $restored = RouteDefinition::__set_state($definition->getArray());

            $this->assertTrue(
                $restored->getQueryRequirement() === RouteQueryRequirement::FORBIDDEN,
                "RouteDefinition::__set_state() should restore queryRequirement as RouteQueryRequirement enum."
            );
        }

        #[Group("Regression")]
        #[Define(
            name: "RouteTarget::__set_state() — Restores Query Args Placement Enum",
            description: "RouteTarget restoration must convert queryArgsPlacement scalar to RouteTargetQueryArgsPlacement enum."
        )]
        public function testRouteTargetSetStateRestoresQueryArgsPlacementEnum () : void {
            $target = new RouteTarget(
                "App\\Controller\\UsersController",
                "show",
                ["example" => "value"],
                RouteTargetQueryArgsPlacement::BEFORE,
                ["auth"],
                ["users"],
                ["X-Test" => "1"],
                ["application/json"],
                true
            );

            $restored = RouteTarget::__set_state($target->getArray());

            $this->assertTrue(
                $restored->getQueryArgsPlacement() === RouteTargetQueryArgsPlacement::BEFORE,
                "RouteTarget::__set_state() should restore queryArgsPlacement as RouteTargetQueryArgsPlacement enum."
            );
        }
    }
?>