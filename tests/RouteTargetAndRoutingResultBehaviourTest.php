<?php
    /**
     * Project Name:    Wingman Nexus - Route Target And Routing Result Behaviour Tests
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
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Objects\ArgumentList;
    use Wingman\Nexus\Objects\ArgumentSet;
    use Wingman\Nexus\Objects\RoutingResult;
    use Wingman\Nexus\Targets\RouteTarget;

    /**
     * Behaviour tests for RouteTarget query placement helpers and RoutingResult lifecycle methods.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteTargetAndRoutingResultBehaviourTest extends Test {
        /**
         * Ensures query-argument placement helper methods reflect the configured enum value.
         * @return void
         */
        #[Group("Targets")]
        #[Define(
            name: "RouteTarget Query Placement Helpers — Reflect Enum State",
            description: "RouteTarget helper methods should correctly indicate whether query arguments are prepended, appended, or excluded."
        )]
        public function testRouteTargetQueryPlacementHelpersReflectEnumState () : void {
            $before = new RouteTarget(queryArgsPlacement: RouteTargetQueryArgsPlacement::BEFORE);
            $after = new RouteTarget(queryArgsPlacement: RouteTargetQueryArgsPlacement::AFTER);
            $none = new RouteTarget(queryArgsPlacement: RouteTargetQueryArgsPlacement::NONE);

            $this->assertTrue($before->areQueryArgsPrepended(), "BEFORE should report prepended query arguments.");
            $this->assertFalse($before->areQueryArgsAppended(), "BEFORE should not report appended query arguments.");
            $this->assertFalse($before->areQueryArgsExcluded(), "BEFORE should not report excluded query arguments.");

            $this->assertTrue($after->areQueryArgsAppended(), "AFTER should report appended query arguments.");
            $this->assertFalse($after->areQueryArgsPrepended(), "AFTER should not report prepended query arguments.");

            $this->assertTrue($none->areQueryArgsExcluded(), "NONE should report excluded query arguments.");
            $this->assertFalse($none->areQueryArgsPrepended(), "NONE should not report prepended query arguments.");
            $this->assertFalse($none->areQueryArgsAppended(), "NONE should not report appended query arguments.");
        }

        /**
         * Ensures withSteps() clones results and preserves the original instance.
         * @return void
         */
        #[Group("RoutingResult")]
        #[Define(
            name: "RoutingResult::withSteps() — Clones Instead Of Mutating",
            description: "withSteps() should return a cloned result with the new steps while keeping the original result unchanged."
        )]
        public function testRoutingResultWithStepsClonesInsteadOfMutating () : void {
            $target = new RouteTarget("App\\Controllers\\UsersController", "show");
            $args = new ArgumentList(["path" => new ArgumentSet(["id" => 42])]);
            $result = RoutingResult::from($target, $args);

            $step = RoutingResult::withError(RoutingError::NOT_FOUND);
            $withSteps = RoutingResult::withSteps($result, [$step]);

            $this->assertTrue($withSteps !== $result, "withSteps() should return a cloned result instance.");
            $this->assertCount(0, $result->getSteps(), "The original result should remain unchanged.");
            $this->assertCount(1, $withSteps->getSteps(), "The cloned result should contain the provided steps.");
            $this->assertFalse($withSteps->hasError(), "Cloning steps should not alter the base success state.");
        }
    }
?>