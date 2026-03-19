<?php
    /**
     * Project Name:    Wingman Nexus - Cortex Bridge - Configuration
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Bridge.Cortex namespace.
    namespace Wingman\Nexus\Bridge\Cortex;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to different strings
    # under require_once). If the alias or stub is already in place there is nothing to do.
    if (class_exists(__NAMESPACE__ . '\Configuration', false)) return;

    # If Cortex is installed, alias the Cortex Configuration class to this namespace.
    if (class_exists(\Wingman\Cortex\Configuration::class)) {
        class_alias(\Wingman\Cortex\Configuration::class, __NAMESPACE__ . '\Configuration');
        return;
    }

    # Import the following classes to the current scope.
    use ReflectionNamedType;
    use ReflectionObject;
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;

    /**
     * A no-op stub used when Cortex is not available.
     * Mirrors the subset of `Wingman\Cortex\Configuration`'s static API that Nexus components
     * rely on — primarily `hydrate()` — so that all call sites remain valid regardless of
     * whether Cortex is installed. When an array is passed as the configuration source,
     * the stub reads `#[Configurable]` annotations and populates matching properties using
     * their declared dot-notation keys. When no config is provided the annotated properties
     * retain their declared PHP defaults.
     * @package Wingman\Nexus\Bridge\Cortex
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Configuration {
        /**
         * Hydrates `#[Configurable]`-annotated properties of `$target` from a flat dot-notation
         * array. When `$source` is already a stub instance it is returned as-is (nothing to read
         * from it, so all annotated properties keep their declared defaults). This mirrors the
         * essential behaviour of `Wingman\Cortex\Configuration::hydrate()` so that callers
         * function correctly whether or not Cortex is installed.
         * @param object $target The object whose properties should be hydrated.
         * @param array|self $source A flat dot-notation key-value array, or a stub instance.
         * @param array $map Ignored in the stub; present for API compatibility.
         * @param bool $strict Ignored in the stub; present for API compatibility.
         * @return self A stub instance.
         */
        public static function hydrate (object $target, array|self $source = [], array $map = [], bool $strict = false) : self {
            if (is_array($source) && !empty($source)) {
                # Remap any short keys to their full dotted equivalents before hydrating.
                foreach ($map as $shortKey => $fullKey) {
                    if (array_key_exists($shortKey, $source) && !array_key_exists($fullKey, $source)) {
                        $source[$fullKey] = $source[$shortKey];
                    }
                }

                $reflection = new ReflectionObject($target);

                foreach ($reflection->getProperties() as $property) {
                    foreach ($property->getAttributes() as $attribute) {
                        if ($attribute->getName() !== Configurable::class) continue;

                        $key = $attribute->newInstance()->getKey();

                        if (!array_key_exists($key, $source)) continue;

                        $value = $source[$key];
                        $type = $property->getType();

                        if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                            $value = match ($type->getName()) {
                                "bool" => (bool) $value,
                                "int" => (int) $value,
                                "float" => (float) $value,
                                "string" => (string) $value,
                                default  => $value
                            };
                        }

                        /**
                         * @disregard
                         * @psalm-suppress DeprecatedMethod
                         * @noinspection PhpDeprecatedApiInspection
                         */
                        if (method_exists($property, "setAccessible")) $property->setAccessible(true);
                        $property->setValue($target, $value);
                    }
                }
            }

            return $source instanceof self ? $source : new static();
        }
    }
?>