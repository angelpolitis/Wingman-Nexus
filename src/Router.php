<?php
    /**
     * Project Name:    Wingman Nexus - Router
     * Created by:      Angel Politis
     * Creation Date:   Jul 23 2019
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2019-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use Wingman\Nexus\AttributeScanner;
    use Wingman\Nexus\Exceptions\RouteNotFoundException;
    use Wingman\Nexus\Bridge\Aegis\UrlSigner;
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Caching\CacheManager;
    use Wingman\Nexus\Enums\ResourceAction;
    use Wingman\Nexus\Exceptions\UrlSigningNotConfiguredException;
    use Wingman\Nexus\Enums\RoutingError;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Objects\RoutingPath;
    use Wingman\Nexus\Objects\RoutingResult;
    use Wingman\Nexus\Objects\RouteSnapshot;
    use Wingman\Nexus\Resolvers\RedirectResolver;
    use Wingman\Nexus\Resolvers\RewriteResolver;
    use Wingman\Nexus\Resolvers\RouteResolver;
    use Wingman\Nexus\RouteGroup;
    use Wingman\Nexus\Rules\Route;
    use Wingman\Nexus\TypeRegistry;

    /**
     * Responsible for handling incoming requests by rewriting, redirect or routing them to the appropriate controller or callable.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Router {
        /**
         * The Aegis URL signer bridge used by {@see generateSignedUrl()} and {@see validateSignedUrl()}.
         * Null until {@see configureUrlSigning()} is called.
         * @var UrlSigner|null
         */
        private ?UrlSigner $urlSigner = null;

        /**
         * The shared attribute scanner instance used by {@see scan()} and {@see group()}.
         * A single instance is reused to avoid repeated instantiation overhead on every call.
         * @var AttributeScanner
         */
        private AttributeScanner $attributeScanner;

        /**
         * The route rule importer of a router.
         * @var RuleImporter
         */
        protected RuleImporter $ruleImporter;

        /**
         * The redirect resolver of a router.
         * @var RedirectResolver
         */
        protected RedirectResolver $redirectResolver;

        /**
         * The rewrite resolver of a router.
         * @var RewriteResolver
         */
        protected RewriteResolver $rewriteResolver;

        /**
         * The route resolver of a router.
         * @var RouteResolver
         */
        protected RouteResolver $routeResolver;

        /**
         * The deferred rule groups pending lazy import.
         * Each entry holds a URL path prefix, a rule type, and the file paths to load when the prefix matches.
         * @var array<array{prefix: string, ruleType: RuleType, files: string[]}>
         */
        protected array $deferredGroups = [];

        /**
         * The routing path of a router.
         * @var RoutingPath
         */
        protected RoutingPath $path;

        /**
         * The variable data types forwarded to `TypeRegistry`.
         * @var array
         */
        #[Configurable("nexus.variableDataTypes")]
        protected array $variableDataTypes = TypeRegistry::DEFAULT_TYPES;

        /**
         * The fallback type name forwarded to `TypeRegistry`.
         * @var string
         */
        #[Configurable("nexus.variableDefaultType")]
        protected string $variableDefaultType = TypeRegistry::DEFAULT_TYPE;

        /**
         * Creates a new router.
         * @param array|Configuration $config The configuration to use, if any.
         */
        public function __construct (array|Configuration $config = []) {
            Configuration::hydrate($this, $config);
            $types = new TypeRegistry($this->variableDataTypes, $this->variableDefaultType);
            $cacheManager = new CacheManager($config, $types);
            $this->path = new RoutingPath();
            $this->attributeScanner = new AttributeScanner();
            $this->ruleImporter = new RuleImporter($cacheManager, $config);
            $this->redirectResolver = new RedirectResolver($cacheManager, $this->path, $config, $types);
            $this->rewriteResolver = new RewriteResolver($cacheManager, $this->path, $config, $types);
            $this->routeResolver = new RouteResolver($cacheManager, $this->path, $config, $types);
        }

        /**
         * Asserts that URL signing has been configured, throwing a {@see UrlSigningNotConfiguredException} if not.
         * @throws UrlSigningNotConfiguredException When {@see configureUrlSigning()} has not been called.
         */
        private function assertUrlSigningConfigured () : void {
            if ($this->urlSigner !== null) return;

            throw new UrlSigningNotConfiguredException(
                "URL signing is not configured. Call Router::configureUrlSigning() with your Aegis secret before "
                . "calling generateSignedUrl() or validateSignedUrl()."
            );
        }

        /**
         * Loads all pending deferred rule groups regardless of their prefix.
         * Called before URL generation, where all registered routes must be available.
         */
        private function loadAllDeferred () : void {
            foreach ($this->deferredGroups as $group) {
                $this->import($group["ruleType"], ...$group["files"]);
            }
            $this->deferredGroups = [];
        }

        /**
         * Loads deferred rule groups whose URL path prefix matches the given URL.
         * Groups that do not match are kept in the pending list for future requests.
         * @param string $url The URL to match against.
         */
        private function loadDeferredForUrl (string $url) : void {
            $path = parse_url($url, PHP_URL_PATH) ?? $url;
            $remaining = [];

            foreach ($this->deferredGroups as $group) {
                if (str_starts_with($path, $group["prefix"])) {
                    $this->import($group["ruleType"], ...$group["files"]);
                }
                else $remaining[] = $group;
            }

            $this->deferredGroups = $remaining;
        }

        /**
         * Configures the Aegis-backed signed-URL bridge.
         *
         * Must be called before {@see generateSignedUrl()} or {@see validateSignedUrl()}.
         * Requires the `wingman/aegis` package to be installed; if it is absent a
         * {@see UrlSigningNotConfiguredException} will be thrown when signing or verification is attempted.
         * @param string $secret The HMAC secret. Must be at least 32 characters.
         * @param int $defaultTtl The default URL lifetime in seconds.
         * @param string $signatureParam The query parameter name for the signature value.
         * @param string $expiryParam The query parameter name for the expiry timestamp.
         * @return static The router.
         */
        public function configureUrlSigning (
            string $secret,
            int $defaultTtl = UrlSigner::DEFAULT_TTL,
            string $signatureParam = UrlSigner::DEFAULT_SIGNATURE_PARAM,
            string $expiryParam = UrlSigner::DEFAULT_EXPIRY_PARAM
        ) : static {
            $this->urlSigner = new UrlSigner($secret, $defaultTtl, $signatureParam, $expiryParam);
            return $this;
        }

        /**
         * Registers a named fallback route.
         *
         * The fallback route is evaluated after every normal route has been tested and found
         * no match, guaranteeing last-resort ordering independent of registration order or
         * wildcard patterns. Multiple fallback routes are tried in the order they were registered.
         * @param string $name The name of the fallback route.
         * @param string $pattern The URL pattern (e.g. `"/**"` to match any path).
         * @param mixed $target The target callable, action string, or method map.
         * @return static The router.
         */
        public function addFallback (string $name, string $pattern, mixed $target) : static {
            $this->routeResolver->addFallbackRules([Route::from($name, $pattern, $target)]);
            return $this;
        }

        /**
         * Registers standard RESTful CRUD routes for a class.
         *
         * Generates up to four grouped route entries covering the conventional seven
         * {@see ResourceAction} actions. Actions sharing the same URL pattern are merged
         * into a single route entry so that Nexus can distinguish method-not-allowed from
         * not-found correctly.
         *
         * Generated route names use the primary read action for each URL group:
         * - `{prefix}.index`  → `GET /{base}` (also handles `POST /{base}` for `store`)
         * - `{prefix}.create` → `GET /{base}/create`
         * - `{prefix}.show`   → `GET /{base}/{id}` (also handles `PUT`, `PATCH`, `DELETE`)
         * - `{prefix}.edit`   → `GET /{base}/{id}/edit`
         *
         * When `index` is absent the collection entry is named `{prefix}.store`;
         * when `show` is absent the member entry is named `{prefix}.update`.
         * @param string $base The URL base path (e.g. `"users"` or `"api/users"`).
         * @param string $class The fully-qualified class name.
         * @param array<string|ResourceAction> $only Allowlist of actions; all others are skipped. Empty means all actions are included.
         * @param array<string|ResourceAction> $except Excludelist of actions to skip.
         * @return static The router.
         */
        public function addResource (string $base, string $class, array $only = [], array $except = []) : static {
            $base = "/" . trim($base, "/");
            $prefix = str_replace("/", ".", ltrim($base, "/"));

            $resolve = static fn (array $items) : array => array_map(
                static fn (string|ResourceAction $item) : string => $item instanceof ResourceAction ? $item->value : $item,
                $items
            );

            $only = $resolve($only);
            $except = $resolve($except);

            $filtered = array_filter(
                ResourceAction::cases(),
                fn (ResourceAction $a) => (empty($only) || in_array($a->value, $only, true))
                    && !in_array($a->value, $except, true)
            );
            $selected = array_combine(array_column($filtered, "value"), array_values($filtered));

            $collection = [];
            $member = [];

            foreach ([ResourceAction::INDEX, ResourceAction::STORE] as $action) {
                if (!isset($selected[$action->value])) continue;
                foreach ($action->getHttpMethods() as $method) {
                    $collection[$method] = "$class::{$action->value}";
                }
            }

            foreach ([ResourceAction::SHOW, ResourceAction::UPDATE, ResourceAction::DESTROY] as $action) {
                if (!isset($selected[$action->value])) continue;
                foreach ($action->getHttpMethods() as $method) {
                    $member[$method] = "$class::{$action->value}";
                }
            }

            $collectionName = isset($selected[ResourceAction::INDEX->value])
                ? ResourceAction::INDEX->value
                : ResourceAction::STORE->value;
            $memberName = isset($selected[ResourceAction::SHOW->value])
                ? ResourceAction::SHOW->value
                : ResourceAction::UPDATE->value;

            $routes = [];

            if (!empty($collection)) {
                $routes[] = Route::from("$prefix.$collectionName", $base, $collection);
            }

            if (isset($selected[ResourceAction::CREATE->value])) {
                $create = ResourceAction::CREATE;
                $routes[] = Route::from(
                    "$prefix.{$create->value}",
                    "$base/{$create->value}",
                    array_fill_keys($create->getHttpMethods(), "$class::{$create->value}")
                );
            }

            if (!empty($member)) {
                $routes[] = Route::from("$prefix.$memberName", "$base/{id}", $member);
            }

            if (isset($selected[ResourceAction::EDIT->value])) {
                $edit = ResourceAction::EDIT;
                $routes[] = Route::from(
                    "$prefix.{$edit->value}",
                    "$base/{id}/{$edit->value}",
                    array_fill_keys($edit->getHttpMethods(), "$class::{$edit->value}")
                );
            }

            $this->routeResolver->addRules($routes);
            return $this;
        }

        /**
         * Generates a signed URL for a named route.
         *
         * Requires {@see configureUrlSigning()} to have been called first, and the
         * `wingman/aegis` package to be installed. The returned URL includes an expiry
         * timestamp and an HMAC signature so that any tampering or expiry can be detected
         * by {@see validateSignedUrl()}.
         * @param string $name The name of the route.
         * @param array $params A flat associative array of parameter values.
         * @param int|null $ttl The lifetime in seconds. Defaults to the TTL configured in {@see configureUrlSigning()} when null.
         * @param bool $preserveQueryExtras Whether to append unrecognised parameters to the query string.
         * @return string The signed URL.
         * @throws UrlSigningNotConfiguredException When URL signing has not been configured.
         */
        public function generateSignedUrl (string $name, array $params = [], ?int $ttl = null, bool $preserveQueryExtras = true) : string {
            $this->assertUrlSigningConfigured();
            $url = $this->generateUrl($name, $params, $preserveQueryExtras);
            return $this->urlSigner->sign($url, $ttl);
        }

        /**
         * Generates a URL for a named route.
         * @param string $name The name of the route.
         * @param array $params A flat associative array of parameter values. Parameters matching
         *   named route tokens are substituted into the URL; any remaining entries are appended
         *   to the query string when $preserveQueryExtras is true.
         * @param bool $preserveQueryExtras Whether to append unrecognised parameters to the query string.
         * @return string The generated URL.
         * @throws RouteNotFoundException If no route with the given name is registered.
         */
        public function generateUrl (string $name, array $params = [], bool $preserveQueryExtras = true) : string {
            $this->loadAllDeferred();
            return $this->routeResolver->generateUrl($name, $params, $preserveQueryExtras);
        }

        /**
         * Returns a snapshot of every registered redirect, including fallback redirects.
         *
         * Forces all deferred rule groups to load before collecting. The returned snapshots
         * are plain value objects suitable for serialisation, `var_dump()`'d, or consumed
         * by documentation tools and console commands.
         * @return \Wingman\Nexus\Objects\RedirectSnapshot[] The snapshots, in registration order.
         */
        public function getRedirects () : array {
            $this->loadAllDeferred();
            return $this->redirectResolver->getSnapshots();
        }

        /**
         * Returns a snapshot of every registered rewrite, including fallback rewrites.
         *
         * Forces all deferred rule groups to load before collecting. The returned snapshots
         * are plain value objects suitable for serialisation, `var_dump()`'d, or consumed
         * by documentation tools and console commands.
         * @return \Wingman\Nexus\Objects\RewriteSnapshot[] The snapshots, in registration order.
         */
        public function getRewrites () : array {
            $this->loadAllDeferred();
            return $this->rewriteResolver->getSnapshots();
        }

        /**
         * Returns a snapshot of every registered route, including fallback routes.
         *
         * Forces all deferred rule groups to load before collecting, so routes registered
         * via {@see importLazy()} are included in the result. The returned snapshots are
         * plain value objects and can be freely serialised, `var_dump()`'d, or consumed
         * by documentation tools and console commands.
         * @return RouteSnapshot[] The snapshots, in registration order: normal routes first, then fallbacks.
         */
        public function getRoutes () : array {
            $this->loadAllDeferred();
            return $this->routeResolver->getSnapshots();
        }

        /**
         * Clears all registered routes, redirects, rewrites, fallbacks, and pending lazy-import
         * groups, then invalidates every resolver registry.
         *
         * Use this method in long-lived process environments (Swoole, RoadRunner, ReactPHP)
         * when the same Router instance must be fully reconfigured between requests.
         * After calling reset() the router is in an empty, ready-to-configure state
         * equivalent to a freshly constructed instance.
         * @return static The router.
         */
        public function reset () : static {
            $this->redirectResolver->reset();
            $this->rewriteResolver->reset();
            $this->routeResolver->reset();
            $this->deferredGroups = [];
            return $this;
        }

        /**
         * Opens a route group, applying shared attributes to every route registered within
         * the callback.
         *
         * The callback receives a {@see RouteGroup} instance pre-configured with the router's
         * rule importer and attribute scanner. Use its fluent `with*` methods to set the
         * prefix, middleware, tags, or headers, and then call `add()`, `import()`, or `scan()`
         * to register routes. All routes are registered with the route resolver when the
         * callback returns.
         *
         * Groups may be nested by calling `$group->group()` on the RouteGroup instance
         * received by the callback, producing routes whose attributes combine both groups.
         * @param callable(RouteGroup): void $callback A callable that receives the group.
         * @return static The router.
         */
        public function group (callable $callback) : static {
            $group = (new RouteGroup($this->attributeScanner))->withImporter($this->ruleImporter);
            $callback($group);
            $this->routeResolver->addRules($group->buildRules());
            return $this;
        }

        /**
         * Imports specified files into a router.
         * @param RuleType $ruleType The type of rule to import.
         * @param string $file The first file to import.
         * @param string ...$files The other files to import.
         * @return static The router.
         */
        public function import (RuleType $ruleType, string $file, string ...$files) : static {
            $resolver = match ($ruleType) {
                RuleType::REDIRECT => $this->redirectResolver,
                RuleType::REWRITE => $this->rewriteResolver,
                RuleType::ROUTE => $this->routeResolver,
            };

            $resolver->addRules($this->ruleImporter->import($ruleType, $file, ...$files));

            return $this;
        }

        /**
         * Registers one or more rule files for deferred (lazy) import.
         *
         * The files are not parsed or compiled until a request URL whose path starts with the given
         * prefix arrives. Unmatched groups are kept in the pending list and re-evaluated on every
         * subsequent request. After a group is loaded it is removed from the pending list, so each
         * file is imported exactly once.
         * @param string $prefix The URL path prefix that triggers loading (e.g. `"/api"`).
         * @param RuleType $ruleType The type of rules contained in the files.
         * @param string $file The first rule file to register.
         * @param string ...$files The other rule files to register.
         * @return static The router.
         */
        public function importLazy (string $prefix, RuleType $ruleType, string $file, string ...$files) : static {
            $this->deferredGroups[] = [
                "prefix" => $prefix,
                "ruleType" => $ruleType,
                "files" => [$file, ...$files]
            ];
            return $this;
        }

        /**
         * Imports specified files into a router as redirects.
         * @param string $files The first file to import.
         * @param string ...$files The other files to import.
         * @return static The router.
         */
        public function importRedirects (string $file, string ...$files) : static {
            return $this->import(RuleType::REDIRECT, $file, ...$files);
        }

        /**
         * Imports specified files into a router as rewrites.
         * @param string $files The first file to import.
         * @param string ...$files The other files to import.
         * @return static The router.
         */
        public function importRewrites (string $file, string ...$files) : static {
            return $this->import(RuleType::REWRITE, $file, ...$files);
        }

        /**
         * Imports specified files into a router as routes.
         * @param string $files The first file to import.
         * @param string ...$files The other files to import.
         * @return static The router.
         */
        public function importRoutes (string $file, string ...$files) : static {
            return $this->import(RuleType::ROUTE, $file, ...$files);
        }

        /**
         * Routes a request.
         *
         * When `$contentType` is non-empty, only routes whose declared content-type list contains
         * the supplied value are candidates for route resolution. Routes with no declared content
         * types are never filtered and will match any content type. Rewrite and redirect resolution
         * are unaffected by `$contentType`; filtering is applied only during the final route lookup.
         * @param string $url A URL.
         * @param string $method An HTTP method.
         * @param string $contentType The request content type used to filter routes that declare content-type constraints.
         * @return RoutingResult The result of the process that will contain a target or error.
         */
        public function route (string $url, string $method = "GET", string $contentType = "") : RoutingResult {
            $this->loadDeferredForUrl($url);
            Emitter::create()->with(url: $url, method: $method, router: $this)->emit(Signal::ROUTING_STARTED);

            $rewriteResult = $this->rewriteResolver->resolve($url, $method);

            if (!$rewriteResult->hasError()) {
                $redirectResult = $this->redirectResolver->resolveRewriteResult($rewriteResult, $method);
                $result = (!$redirectResult->hasError() || $redirectResult->getError() !== RoutingError::NOT_FOUND)
                    ? $redirectResult
                    : $this->routeResolver->resolveRewriteResult($rewriteResult, $method, $contentType);
            }
            elseif ($rewriteResult->getError() !== RoutingError::NOT_FOUND) {
                $result = $rewriteResult;
            }
            else {
                $redirectResult = $this->redirectResolver->resolve($url, $method);
                $result = (!$redirectResult->hasError() || $redirectResult->getError() !== RoutingError::NOT_FOUND)
                    ? $redirectResult
                    : $this->routeResolver->resolve($url, $method, [], $contentType);
            }

            Emitter::create()->with(url: $url, method: $method, result: $result, router: $this)->emit(Signal::ROUTING_COMPLETED);

            return $result;
        }

        /**
         * Scans one or more classes for `#[Route]` attributes and registers the discovered routes.
         *
         * Any public method annotated with {@see \Wingman\Nexus\Attributes\Route} in the supplied
         * classes will be registered as a route. The class type is unrestricted; no particular
         * interface or base class is required.
         * @param string ...$classes The fully-qualified names of the classes to scan.
         * @return static The router.
         */
        public function scan (string ...$classes) : static {
            $this->routeResolver->addRules($this->attributeScanner->scan(...$classes));
            return $this;
        }

        /**
         * Verifies the signature and expiry of a signed URL.
         *
         * Requires {@see configureUrlSigning()} to have been called first. Returns false
         * when the URL is expired or its signature does not match, allowing callers to
         * respond with a 403 or 410 without distinguishing between the two cases.
         * @param string $url The signed URL to verify, exactly as received in the request.
         * @return bool Whether the URL is unexpired and its signature is valid.
         * @throws UrlSigningNotConfiguredException When URL signing has not been configured.
         */
        public function validateSignedUrl (string $url) : bool {
            $this->assertUrlSigningConfigured();
            return $this->urlSigner->verify($url);
        }
    }
?>