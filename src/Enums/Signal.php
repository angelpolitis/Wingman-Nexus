<?php
    /**
     * Project Name:    Wingman Nexus - Signal
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Enums namespace.
    namespace Wingman\Nexus\Enums;

    /**
     * Represents a signal emitted by Nexus during routing lifecycle operations.
     *
     * Each case maps to a lowercase dot-notation string identifier consumed by Corvus listeners.
     * Cases can be passed directly to `emit()` — coercion to their string value via `->value` is
     * required when the method expects a plain string.
     *
     * @package Wingman\Nexus\Enums
     * @author  Angel Politis <info@angelpolitis.com>
     * @since   1.0
     */
    enum Signal : string {

        // ─── Cache ────────────────────────────────────────────────────────────────

        /**
         * Emitted when a JSON rule file is resolved from the per-file cache (fingerprint match).
         * Payload: `file` (string), `importer` (RuleImporter).
         */
        case CACHE_FILE_HIT = "nexus.cache.file.hit";

        /**
         * Emitted when a JSON rule file is not in the per-file cache or its fingerprint is stale.
         * Payload: `file` (string), `importer` (RuleImporter).
         */
        case CACHE_FILE_MISS = "nexus.cache.file.miss";

        /**
         * Emitted after a parsed JSON rule file has been written to the per-file cache.
         * Payload: `file` (string), `manager` (CacheManager).
         */
        case CACHE_FILE_WRITTEN = "nexus.cache.file.written";

        /**
         * Emitted when the compiled route registry for a rule type is loaded from its cache files.
         * Payload: `ruleType` (RuleType), `manager` (CacheManager).
         */
        case CACHE_REGISTRY_HIT = "nexus.cache.registry.hit";

        /**
         * Emitted when no valid registry cache exists for a rule type and `buildCache()` is called.
         * Payload: `ruleType` (RuleType), `manager` (CacheManager).
         */
        case CACHE_REGISTRY_MISS = "nexus.cache.registry.miss";

        /**
         * Emitted after the compiled registry for a rule type has been persisted to cache files.
         * Payload: `ruleType` (RuleType), `manager` (CacheManager).
         */
        case CACHE_REGISTRY_WRITTEN = "nexus.cache.registry.written";

        // ─── Compilation ──────────────────────────────────────────────────────────

        /**
         * Emitted after a single rule's pattern has been compiled into a `CompiledRoute`.
         * Payload: `name` (string), `pattern` (string), `ruleType` (RuleType), `manager` (CacheManager).
         */
        case ROUTE_COMPILED = "nexus.route.compiled";

        // ─── Import ───────────────────────────────────────────────────────────────

        /**
         * Emitted after one or more rule files have been imported into a resolver.
         * Payload: `ruleType` (RuleType), `files` (string[]), `rules` (Rule[]), `importer` (RuleImporter).
         */
        case RULES_IMPORTED = "nexus.rules.imported";

        // ─── Registry / Resolver ──────────────────────────────────────────────────

        /**
         * Emitted after rules have been appended to a resolver's rule list.
         * Payload: `rules` (Rule[]), `ruleType` (RuleType), `resolver` (Resolver).
         */
        case RULES_ADDED = "nexus.rules.added";

        /**
         * Emitted after a resolver's route registry has been rebuilt from the cache manager.
         * Payload: `ruleType` (RuleType), `resolver` (Resolver).
         */
        case REGISTRY_REFRESHED = "nexus.registry.refreshed";

        /**
         * Emitted when a registered rule has matched the incoming request.
         * Payload: `rule` (Rule), `url` (string), `method` (string), `resolver` (Resolver).
         */
        case MATCH_FOUND = "nexus.match.found";

        /**
         * Emitted when no registered rule matches the incoming request.
         * Payload: `url` (string), `method` (string), `ruleType` (RuleType), `resolver` (Resolver).
         */
        case MATCH_NOT_FOUND = "nexus.match.not.found";

        // ─── Rewrite ──────────────────────────────────────────────────────────────

        /**
         * Emitted after each successful hop in a rewrite chain.
         * Payload: `step` (int), `fromUrl` (string), `toUrl` (string), `resolver` (RewriteResolver).
         */
        case REWRITE_STEP_RESOLVED = "nexus.rewrite.step.resolved";

        /**
         * Emitted when the rewrite chain terminates naturally (final rewritten URL found).
         * Payload: `steps` (int), `url` (string), `resolver` (RewriteResolver).
         */
        case REWRITE_COMPLETED = "nexus.rewrite.completed";

        /**
         * Emitted when a rewrite cycle is detected (the next URL was already visited).
         * Payload: `url` (string), `resolver` (RewriteResolver).
         */
        case REWRITE_CYCLE_DETECTED = "nexus.rewrite.cycle.detected";

        /**
         * Emitted when the maximum rewrite chain depth is exceeded.
         * Payload: `depth` (int), `url` (string), `resolver` (RewriteResolver).
         */
        case MAX_REWRITE_DEPTH_EXCEEDED = "nexus.rewrite.depth.exceeded";

        // ─── Redirect ─────────────────────────────────────────────────────────────

        /**
         * Emitted after each successful hop in a redirect chain.
         * Payload: `step` (int), `fromUrl` (string), `toUrl` (string), `resolver` (RedirectResolver).
         */
        case REDIRECT_STEP_RESOLVED = "nexus.redirect.step.resolved";

        /**
         * Emitted when the redirect chain terminates naturally (final redirect target resolved).
         * Payload: `steps` (int), `url` (string), `resolver` (RedirectResolver).
         */
        case REDIRECT_COMPLETED = "nexus.redirect.completed";

        /**
         * Emitted when a redirect cycle is detected (the next URL was already encountered).
         * Payload: `url` (string), `resolver` (RedirectResolver).
         */
        case REDIRECT_CYCLE_DETECTED = "nexus.redirect.cycle.detected";

        /**
         * Emitted when the maximum redirect chain depth is exceeded.
         * Payload: `depth` (int), `url` (string), `resolver` (RedirectResolver).
         */
        case MAX_REDIRECT_DEPTH_EXCEEDED = "nexus.redirect.depth.exceeded";

        // ─── Route ────────────────────────────────────────────────────────────────

        /**
         * Emitted when a URL matches a route's pattern but no target is registered for the HTTP method.
         * Payload: `url` (string), `method` (string), `resolver` (RouteResolver).
         */
        case METHOD_NOT_ALLOWED = "nexus.route.method.not.allowed";

        /**
         * Emitted when an OPTIONS request matches one or more registered route patterns.
         * Payload: `url` (string), `methods` (string[]), `target` (OptionsTarget), `resolver` (RouteResolver).
         */
        case OPTIONS_RESOLVED = "nexus.route.options.resolved";

        /**
         * Emitted when a fallback route matches an otherwise unmatched request.
         * Payload: `rule` (Route), `url` (string), `method` (string), `resolver` (Resolver).
         */
        case FALLBACK_MATCHED = "nexus.route.fallback.matched";

        // ─── URL Generation ───────────────────────────────────────────────────────

        /**
         * Emitted after a URL has been fully assembled from a route definition and arguments.
         * Payload: `url` (string), `definition` (RouteDefinition), `generator` (UrlGenerator).
         */
        case URL_GENERATED = "nexus.url.generated";

        // ─── Router ───────────────────────────────────────────────────────────────

        /**
         * Emitted at the start of a routing operation, before any resolver is invoked.
         * Payload: `url` (string), `method` (string), `router` (Router).
         */
        case ROUTING_STARTED = "nexus.routing.started";

        /**
         * Emitted at the end of a routing operation, once a final `RoutingResult` has been produced.
         * Payload: `url` (string), `method` (string), `result` (RoutingResult), `router` (Router).
         */
        case ROUTING_COMPLETED = "nexus.routing.completed";

        /**
         * Resolves a signal from a string or returns the existing instance.
         * @param static|string $signal The signal to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|string $signal) : static {
            return $signal instanceof static ? $signal : static::from($signal);
        }
    }
?>