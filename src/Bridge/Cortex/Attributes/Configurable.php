<?php
    /**
     * Project Name:    Wingman Nexus - Cortex Bridge - Configurable Attribute
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Bridge.Cortex.Attributes namespace.
    namespace Wingman\Nexus\Bridge\Cortex\Attributes;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the alias or stub is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\Configurable', false)) return;

    # Import the following classes to the current scope.
    use Attribute;

    # If Cortex is installed, alias its Configurable attribute to this namespace.
    if (class_exists(\Wingman\Cortex\Attributes\Configurable::class)) {
        class_alias(\Wingman\Cortex\Attributes\Configurable::class, __NAMESPACE__ . '\Configurable');
        return;
    }

    /**
     * A no-op stub used when Cortex is not available.
     * Mirrors the `Wingman\Cortex\Attributes\Configurable` attribute so that all annotated
     * properties remain valid regardless of whether Cortex is installed. The stub also exposes
     * `getKey()` so that the `Configuration::hydrate()` stub can resolve the configuration key
     * it should read from when populating properties from a flat array.
     * @package Wingman\Nexus\Bridge\Cortex\Attributes
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    #[Attribute(Attribute::TARGET_PROPERTY)]
    class Configurable {
        /**
         * The dot-notation configuration key mapped to this property.
         * @var string
         */
        private string $key;

        /**
         * Creates a new configurable attribute.
         * @param string $key The dot-notation configuration key (e.g. `"nexus.wildcard_1"`).
         * @param string $description An optional human-readable description; ignored by the stub.
         * @param mixed ...$rest Additional arguments accepted for API compatibility; all ignored.
         */
        public function __construct (string $key, string $description = "", mixed ...$rest) {
            $this->key = $key;
        }

        /**
         * Gets the dot-notation configuration key.
         * @return string The configuration key.
         */
        public function getKey () : string {
            return $this->key;
        }
    }
?>