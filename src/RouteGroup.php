<?php
    /**
     * Project Name:    Wingman Nexus - Route Group
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 19 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Exceptions\ImporterNotConfiguredException;
    use Wingman\Nexus\Objects\GroupedCallable;
    use Wingman\Nexus\Objects\TargetMap;
    use Wingman\Nexus\Rules\Route;
    use Wingman\Nexus\Targets\RouteTarget;

    /**
     * Represents a route group — a shared set of attributes (prefix, namePrefix, middleware, tags, headers)
     * that are applied to every route registered within a {@see Router::group()} callback.
     *
     * A group acts as a scoped sub-router: it exposes `import()`, `scan()`, `add()`, and `group()`
     * methods that mirror their `Router` counterparts and collect rules internally. When the callback
     * returns, the router calls {@see RouteGroup::buildRules()} to retrieve the finalised rules
     * with the group's settings already applied.
     *
     * Groups may be nested by calling {@see RouteGroup::group()} on the instance passed to the
     * callback. The inner group's URL prefix and name prefix are appended to the outer group's,
     * and its middleware, tags, and headers are prepended after the outer group's.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RouteGroup {
        /**
         * The headers of a route group.
         * @var array
         */
        private array $headers = [];

        /**
         * The middleware of a route group.
         * @var string[]
         */
        private array $middleware = [];

        /**
         * The route name prefix of a route group.
         * @var string
         */
        private string $namePrefix = "";

        /**
         * The URL pattern prefix of a route group.
         * @var string
         */
        private string $prefix = "";

        /**
         * The collected raw rules of a route group, before group settings are applied.
         * @var Route[]
         */
        private array $rawRules = [];

        /**
         * The rule importer used to load JSON and PHP rule files.
         * @var RuleImporter|null
         */
        private ?RuleImporter $ruleImporter = null;

        /**
         * The tags of a route group.
         * @var string[]
         */
        private array $tags = [];

        /**
         * Creates a new route group.
         * @param AttributeScanner $attributeScanner The attribute scanner to use for class-based imports.
         */
        public function __construct (
            private readonly AttributeScanner $attributeScanner
        ) {}

        /**
         * Applies the group's middleware, tags, and headers to a raw map array
         * (the format produced by `AttributeScanner` and inline `add()` calls that use arrays).
         * @param array $map The raw method-keyed map array.
         * @return array The updated map array.
         */
        private function applyToMapArray (array $map) : array {
            foreach ($map as $method => $entry) {
                if (!is_array($entry)) continue;

                $map[$method]["middleware"] = array_merge(
                    $this->middleware,
                    $entry["middleware"] ?? []
                );
                $map[$method]["tags"] = array_merge(
                    $this->tags,
                    $entry["tags"] ?? []
                );
                $map[$method]["headers"] = array_merge(
                    $this->headers,
                    $entry["headers"] ?? []
                );
            }

            return $map;
        }

        /**
         * Applies the group's middleware, tags, and headers to a single `RouteTarget`, returning
         * a new instance with the merged values.
         * @param RouteTarget $target The target to update.
         * @return RouteTarget The updated target.
         */
        private function applyToRouteTarget (RouteTarget $target) : RouteTarget {
            return new RouteTarget(
                $target->class,
                $target->action,
                $target->arguments,
                $target->queryArgsPlacement,
                array_merge($this->middleware, $target->middleware),
                array_merge($this->tags, $target->tags),
                array_merge($this->headers, $target->headers),
                $target->contentTypes,
                $target->preservesQuery
            );
        }

        /**
         * Applies the group's middleware, tags, and headers to every entry in a `TargetMap`.
         * @param TargetMap $map The target map to update.
         * @return TargetMap The updated map.
         */
        private function applyToTargetMap (TargetMap $map) : TargetMap {
            foreach ($map->getMethods() as $method) {
                $target = $map->getTarget($method);

                if (!$target instanceof RouteTarget) continue;

                $map->setMethod($method, $this->applyToRouteTarget($target));
            }

            return $map;
        }

        /**
         * Adds a route directly to the group.
         * @param string $name The route name.
         * @param string $pattern The URL pattern.
         * @param mixed $target The target callable, action string, or method map.
         * @return static The group.
         */
        public function add (string $name, string $pattern, mixed $target) : static {
            $this->rawRules[] = Route::from($name, $pattern, $target);
            return $this;
        }

        /**
         * Applies the group's prefix, namePrefix, middleware, tags, and headers to every
         * collected rule and returns the finalised rule list. Called by the router after the
         * group callback returns.
         * @return Route[] The finalised rules with group settings applied.
         */
        public function buildRules () : array {
            $result = [];

            foreach ($this->rawRules as $rule) {
                $name = $this->namePrefix . $rule->getName();
                $pattern = $this->prefix . $rule->getPattern();
                $target = $rule->getTarget();

                if ($target instanceof TargetMap) {
                    $target = $this->applyToTargetMap($target);
                }
                elseif (is_array($target)) {
                    $target = $this->applyToMapArray($target);
                }
                elseif (is_callable($target) && (!empty($this->middleware) || !empty($this->tags) || !empty($this->headers))) {
                    $target = new GroupedCallable($target, $this->middleware, $this->tags, $this->headers);
                }

                $result[] = Route::from($name, $pattern, $target);
            }

            return $result;
        }

        /**
         * Opens a nested route group, applying both this group's and the inner group's
         * attributes to every route registered within the callback.
         *
         * The inner group's URL prefix is appended after the outer group's prefix, and its
         * name prefix is appended after the outer group's name prefix. Middleware, tags, and
         * headers from the inner group are layered on top of those from the outer group when
         * {@see buildRules()} is called.
         *
         * The rule importer — if configured on the outer group — is forwarded to the inner group
         * automatically.
         * @param callable(RouteGroup): void $callback A callable that receives the inner group.
         * @return static The outer group.
         */
        public function group (callable $callback) : static {
            $inner = new static($this->attributeScanner);

            if ($this->ruleImporter !== null) {
                $inner->withImporter($this->ruleImporter);
            }

            $callback($inner);

            array_push($this->rawRules, ...$inner->buildRules());

            return $this;
        }

        /**
         * Imports one or more rule files of a given type into the group.
         *
         * The `$ruleType` parameter determines how the imported rules are interpreted.
         * Use {@see RuleType::ROUTE} for standard route files (the most common case),
         * {@see RuleType::REDIRECT} for redirect definition files, and
         * {@see RuleType::REWRITE} for rewrite definition files.
         * @param RuleType $ruleType The type of rules to import.
         * @param string $file The first file to import.
         * @param string ...$files The other files to import.
         * @return static The group.
         * @throws ImporterNotConfiguredException When no rule importer has been configured via {@see withImporter()}.
         */
        public function import (RuleType $ruleType, string $file, string ...$files) : static {
            if ($this->ruleImporter === null) {
                throw new ImporterNotConfiguredException("Cannot import rule files: no RuleImporter has been configured. Call withImporter() first.");
            }

            $imported = $this->ruleImporter->import($ruleType, $file, ...$files);
            array_push($this->rawRules, ...$imported);
            return $this;
        }

        /**
         * Scans one or more classes for `#[Route]` attributes and adds the discovered routes to the group.
         * @param string ...$classes The fully-qualified names of the classes to scan.
         * @return static The group.
         */
        public function scan (string ...$classes) : static {
            $discovered = $this->attributeScanner->scan(...$classes);
            array_push($this->rawRules, ...$discovered);
            return $this;
        }

        /**
         * Sets the response headers merged into every route in the group.
         * @param array $headers An associative array of header names to values.
         * @return static The group.
         */
        public function withHeaders (array $headers) : static {
            $this->headers = $headers;
            return $this;
        }

        /**
         * Sets the rule importer for a route group.
         * @param RuleImporter $ruleImporter The rule importer.
         * @return static The group.
         */
        public function withImporter (RuleImporter $ruleImporter) : static {
            $this->ruleImporter = $ruleImporter;
            return $this;
        }

        /**
         * Sets the middleware prepended to every route in the group.
         * @param string[] $middleware The middleware class names.
         * @return static The group.
         */
        public function withMiddleware (string ...$middleware) : static {
            $this->middleware = $middleware;
            return $this;
        }

        /**
         * Sets the route name prefix applied to every route in the group.
         * @param string $namePrefix The prefix (e.g. `"api.v1."`). A trailing dot is recommended.
         * @return static The group.
         */
        public function withNamePrefix (string $namePrefix) : static {
            $this->namePrefix = $namePrefix;
            return $this;
        }

        /**
         * Sets the URL path prefix applied to every route in the group.
         * @param string $prefix The prefix (e.g. `"/api/v1"`).
         * @return static The group.
         */
        public function withPrefix (string $prefix) : static {
            $this->prefix = $prefix;
            return $this;
        }

        /**
         * Sets the tags merged into every route in the group.
         * @param string[] $tags The tag names.
         * @return static The group.
         */
        public function withTags (string ...$tags) : static {
            $this->tags = $tags;
            return $this;
        }
    }
?>