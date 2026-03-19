<?php
    /**
     * Project Name:    Wingman Nexus - URI Immutability And Composition Tests
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
    use Wingman\Nexus\Objects\URI;

    /**
     * Behaviour tests for URI value-object composition and immutability.
     * @package Wingman\Nexus\Tests
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class URIImmutabilityAndCompositionTest extends Test {
        /**
         * Ensures URI parsing and string composition normalise scheme and host casing while preserving authority details.
         * @return void
         */
        #[Group("URI")]
        #[Define(
            name: "URI::from() And __toString() — Normalise Scheme And Host",
            description: "URI parsing should normalise scheme and host to lowercase while preserving credentials, port, path, query and fragment."
        )]
        public function testUriFromAndToStringNormaliseSchemeAndHost () : void {
            $uri = URI::from("HTTPS://User:Pass@Example.COM:8443/docs/path?x=1#top");

            $this->assertTrue(
                strval($uri) === "https://User:Pass@example.com:8443/docs/path?x=1#top",
                "String conversion should preserve all components with normalised scheme and host."
            );
            $this->assertTrue($uri->getAuthority() === "User:Pass@example.com:8443", "Authority should include credentials, host and port.");
            $this->assertTrue($uri->getPathWithQuery() === "/docs/path?x=1", "Path with query should include the current query string.");
        }

        /**
         * Ensures query mutator helpers return new URI instances and preserve previous state.
         * @return void
         */
        #[Group("URI")]
        #[Define(
            name: "URI Query Mutators — Return New Instances",
            description: "withParam(), withParams() and withoutParams() should return new immutable URI instances while preserving previous state."
        )]
        public function testUriQueryMutatorsReturnNewImmutableInstances () : void {
            $original = URI::from("/search?one=1&two=2");
            $withExtra = $original->withParam("three", "3");
            $withoutSome = $withExtra->withoutParams("one", "three");

            $this->assertTrue($original !== $withExtra, "withParam() should return a new URI instance.");
            $this->assertTrue($withExtra !== $withoutSome, "withoutParams() should return a new URI instance.");

            $this->assertStringContains("one=1", $original->getQuery() ?? "", "Original URI query should remain unchanged.");
            $this->assertStringContains("two=2", $original->getQuery() ?? "", "Original URI query should remain unchanged.");

            $this->assertStringContains("three=3", $withExtra->getQuery() ?? "", "withParam() should add the given key-value pair.");

            $finalQuery = $withoutSome->getQuery() ?? "";
            $this->assertStringContains("two=2", $finalQuery, "withoutParams() should keep parameters that were not removed.");
            $this->assertStringNotContains("one=1", $finalQuery, "withoutParams() should remove the first specified parameter.");
            $this->assertStringNotContains("three=3", $finalQuery, "withoutParams() should remove additional specified parameters.");
        }
    }
?>