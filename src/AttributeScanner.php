<?php
    /**
     * Project Name:    Wingman Nexus - Attribute Scanner
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use ReflectionClass;
    use ReflectionMethod;
    use Wingman\Nexus\Attributes\Route as RouteAttribute;
    use Wingman\Nexus\Enums\HttpMethod;
    use Wingman\Nexus\Enums\RouteTargetQueryArgsPlacement;
    use Wingman\Nexus\Rules\Route as RouteRule;

    /**
     * Discovers {@see RouteAttribute} attributes on class methods and converts them into
     * {@see RouteRule} rule objects ready to be registered with the router.
     *
     * Scanning is intentionally class-agnostic: any class with public methods annotated with
     * `#[Route]` is a valid input, regardless of what the class represents or extends.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AttributeScanner {
        /**
         * Converts a `RouteAttribute` into the map array accepted by `RouteTargetParser::parseMap()`.
         * Each HTTP method in the attribute maps to one route-map-rule entry carrying the class
         * and method name as the target together with any additional routing metadata.
         * @param RouteAttribute $attr The route attribute instance.
         * @param string $class The fully-qualified name of the class that owns the method.
         * @param string $method The name of the method.
         * @return array<string, array> The method-keyed target map array.
         */
        private function buildTarget (RouteAttribute $attr, string $class, string $method) : array {
            $includeQueryArgs = match ($attr->queryArgsPlacement) {
                RouteTargetQueryArgsPlacement::AFTER  => "append",
                RouteTargetQueryArgsPlacement::BEFORE => "prepend",
                default => false
            };

            $entry = [
                "class" => $class,
                "action" => $method,
                "middleware" => $attr->middleware,
                "tags" => $attr->tags,
                "headers" => $attr->headers,
                "contentTypes" => $attr->contentTypes,
                "includeQueryArgs" => $includeQueryArgs,
                "preservesQuery" => $attr->preservesQuery,
            ];

            $target = [];

            foreach ($attr->methods as $method) {
                $httpMethod = $method instanceof HttpMethod ? $method->value : HttpMethod::resolve($method)->value;
                $target[$httpMethod] = $entry;
            }

            return $target;
        }

        /**
         * Derives a route name from a class and method name when the attribute does not specify one.
         * The class name is lowercased and its namespace separators are replaced with dots, then
         * the method name is appended, producing a dot-notation identifier (e.g. `app.users.show`).
         * @param string $class The fully-qualified class name.
         * @param string $method The method name.
         * @return string The derived route name.
         */
        private function generateName (string $class, string $method) : string {
            return strtolower(str_replace("\\", ".", ltrim($class, "\\"))) . "." . $method;
        }

        /**
         * Scans the public methods of each supplied class for `#[Route]` attributes and returns
         * a list of {@see RouteRule} objects ready to be added to the router.
         *
         * A single method may carry multiple `#[Route]` attributes to register it under
         * several patterns simultaneously. Only public methods are inspected.
         * @param string ...$classes The fully-qualified names of the classes to scan.
         * @return RouteRule[] The discovered route rules.
         */
        public function scan (string ...$classes) : array {
            $rules = [];

            foreach ($classes as $className) {
                $className = str_replace('.', "\\", $className);
                $reflection = new ReflectionClass($className);

                foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $attributes = $method->getAttributes(RouteAttribute::class);

                    foreach ($attributes as $attribute) {
                        $attr = $attribute->newInstance();
                        $name = $attr->name ?? $this->generateName($className, $method->getName());
                        $target = $this->buildTarget($attr, $className, $method->getName());
                        $rules[] = new RouteRule($name, $attr->pattern, $target);
                    }
                }
            }

            return $rules;
        }
    }
?>