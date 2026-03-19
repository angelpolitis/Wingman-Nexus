<?php
    /**
     * Project Name:    Wingman Nexus - Regression Utilities Tests
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
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Helper;
    use Wingman\Nexus\Objects\RoutingResult;
    use Wingman\Nexus\TypeRegistry;

    /**
     * Regression tests for low-level utility behaviour.
     * Covers fixes for coerce("null"), pInt+ expansion, and nullable target access.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RegressionUtilitiesTest extends Test {
        #[Group("Regression")]
        #[Define(
            name: "Helper::coerce() — Preserves Literal Null String",
            description: "The string \"null\" remains a string and is not coerced to PHP null."
        )]
        public function testCoercePreservesLiteralNullString () : void {
            $value = Helper::coerce("null");

            $this->assertTrue($value === "null", "Helper::coerce() should keep the literal string \"null\" unchanged.");
        }

        #[Group("Regression")]
        #[Define(
            name: "TypeRegistry — uInt Accepts Large Unsigned Integers",
            description: "uInt (via pInt+) must match values with three or more digits."
        )]
        public function testUIntTypeAcceptsLargeUnsignedIntegers () : void {
            $registry = new TypeRegistry();
            $pattern = '/^(' . $registry->resolve("uInt") . ')$/u';

            $this->assertTrue(preg_match($pattern, "100") === 1, "uInt should validate \"100\" after fixing pInt+.");
            $this->assertTrue(preg_match($pattern, "123456789") === 1, "uInt should validate larger unsigned integers.");
        }

        #[Group("Regression")]
        #[Define(
            name: "RoutingResult::getTarget() — Returns Null For Errors",
            description: "Error results must expose a nullable target without triggering type errors."
        )]
        public function testRoutingResultGetTargetReturnsNullForErrorResult () : void {
            $result = RoutingResult::withError(RoutingError::NOT_FOUND);

            $this->assertTrue($result->getTarget() === null, "getTarget() should return null for error results.");
        }
    }
?>