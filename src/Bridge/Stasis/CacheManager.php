<?php
    /**
     * Project Name:    Wingman Nexus — Stasis Bridge
     * Created by:      Angel Politis
     * Creation Date:   Mar 19 2026
     * Last Modified:   Mar 20 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Bridge.Stasis namespace.
    namespace Wingman\Nexus\Bridge\Stasis;

    # Guard against double-inclusion (e.g. via symlinked paths resolving to
    # different strings under require_once).
    if (class_exists(__NAMESPACE__ . '\CacheManager', false)) return;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Caching\CacheManager as BaseCacheManager;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\TypeRegistry;
    use Wingman\Stasis\Cacher;
    
    /**
     * A CacheManager implementation that delegates all persistence to a Wingman
     * Cacher instance, replacing Nexus's file-path-based internal cacher with a
     * key-value store that supports pluggable backends (local filesystem, APCu,
     * Redis, Memcached) and tagged invalidation.
     *
     * When Wingman Stasis is installed, the consuming application is responsible
     * for constructing a `CacheManager` with a pre-configured `Stasis` instance
     * and passing it to the `Router`:
     *
     * ```php
     * use Wingman\Stasis\Cacher;
     * use Wingman\Stasis\Adapters\RedisAdapter;
     * use Wingman\Nexus\Bridge\Stasis\CacheManager;
     * use Wingman\Nexus\Router;
     *
     * $cacher = (new Cacher())->setAdapter(new RedisAdapter($redis));
     * $router = new Router(cacheManager: new CacheManager($cacher));
     * ```
     *
     * ### Cache keys
     *
     * | Purpose | Key format |
     * |---|---|
     * | Registry (compiled / definitions / targets) | `nexus.registry.<type>.<slug>` |
     * | Per-file fingerprint cache | `nexus.file.<md5>` |
     *
     * Tags used for bulk invalidation: `nexus.registry` (all registry entries),
     * `nexus.files` (all per-file entries), `nexus` (everything).
     *
     * @package Wingman\Nexus\Bridge\Stasis
     * @author  Angel Politis <info@angelpolitis.com>
     * @since   1.0
     */
    class CacheManager extends BaseCacheManager {
        /**
         * The tag applied to every cache entry written by this bridge.
         * @var string
         */
        public const string TAG_ALL = "nexus";

        /**
         * The tag applied to per-file fingerprint entries.
         * @var string
         */
        public const string TAG_FILES = "nexus.files";

        /**
         * The tag applied to compiled / definitions / targets registry entries.
         * @var string
         */
        public const string TAG_REGISTRY = "nexus.registry";

        /**
         * The underlying Cacher instance.
         * @var Cacher
         */
        private Cacher $wingmanCacher;

        /**
         * Creates a new Cacher-backed cache manager.
         * @param Cacher $cacher The Cacher instance to delegate all persistence to.
         * @param array|Configuration $config Optional Nexus configuration
         *   (forwarded to the base CacheManager for compiler hydration).
         * @param TypeRegistry|null $types Optional pre-built type registry.
         */
        public function __construct (Cacher $cacher, array|Configuration $config = [], ?TypeRegistry $types = null) {
            parent::__construct($config, $types);
            $this->wingmanCacher = $cacher;
        }

        /**
         * Returns the cache key used for a given rule-type registry slot.
         * @param RuleType $ruleType The rule type.
         * @param "compiled"|"definitions"|"targets" $fileType The slot name.
         * @return string The cache key.
         */
        protected function getKey (RuleType $ruleType, string $fileType) : string {
            $slug = ["routes", "redirects", "rewrites"][$ruleType->value];
            return "nexus.registry.{$slug}.{$fileType}";
        }

        /**
         * Checks whether all three registry slots for a rule type are present and fresh in the cache.
         * @param RuleType $ruleType The type of rules.
         * @return bool Whether the registry cache is complete and valid.
         */
        public function isCacheValid (RuleType $ruleType) : bool {
            return $this->wingmanCacher->has($this->getKey($ruleType, "compiled"))
                && $this->wingmanCacher->has($this->getKey($ruleType, "definitions"))
                && $this->wingmanCacher->has($this->getKey($ruleType, "targets"));
        }

        /**
         * Loads processed rule data from the cache or rebuilds and stores it if absent or stale.
         * @param RuleType $ruleType The type of rules to load.
         * @return array{
         *     compiled: array{byName: array, byPattern: array},
         *     definitions: array{byName: array, byPattern: array},
         *     targets: array{byName: array, byPattern: array}
         * } The compiled, definitions and targets arrays.
         */
        public function load (RuleType $ruleType, array $rules) : array {
            if ($this->isCacheValid($ruleType)) {
                $compiled = $this->wingmanCacher->get($this->getKey($ruleType, "compiled"));
                $definitions = $this->wingmanCacher->get($this->getKey($ruleType, "definitions"));
                $targets = $this->wingmanCacher->get($this->getKey($ruleType, "targets"));

                if (is_array($compiled) && is_array($definitions) && is_array($targets)) {
                    Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_HIT);
                    return compact("compiled", "definitions", "targets");
                }
            }

            Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_MISS);
            return $this->buildCache($ruleType, $rules);
        }

        /**
         * Builds the processed rule data and writes it to the Cacher backend when caching is enabled.
         * @param RuleType $ruleType The type of rules whose cache to build.
         * @param array $rules The rules.
         * @return array{
         *     compiled: array{byName: array, byPattern: array},
         *     definitions: array{byName: array, byPattern: array},
         *     targets: array{byName: array, byPattern: array}
         * } The compiled, definitions and targets arrays.
         */
        public function buildCache (RuleType $ruleType, array $rules) : array {
            $result = parent::buildCache($ruleType, $rules);

            if ($this->cacheEnabled) {
                $tags = [static::TAG_ALL, static::TAG_REGISTRY];

                $this->wingmanCacher->set($this->getKey($ruleType, "compiled"), $result["compiled"], tags: $tags);
                $this->wingmanCacher->set($this->getKey($ruleType, "definitions"), $result["definitions"], tags: $tags);
                $this->wingmanCacher->set($this->getKey($ruleType, "targets"), $result["targets"], tags: $tags);

                Emitter::create()->with(ruleType: $ruleType, manager: $this)->emit(Signal::CACHE_REGISTRY_WRITTEN);
            }

            return $result;
        }

        /**
         * Writes the parsed content of a source rule file to the Cacher backend, keyed by the file's
         * MD5 hash, with a fingerprint stored alongside the content for freshness validation.
         * @param string $file The absolute path to the source file.
         * @param array $data The parsed rule data.
         */
        public function cache (string $file, array $data) : void {
            $key = "nexus.file." . md5($file);
            $fingerprint = $this->wingmanCacher->generateFingerprint([$file]);

            $this->wingmanCacher->set(
                $key,
                ["_c" => $data, "_fp" => $fingerprint],
                tags: [static::TAG_ALL, static::TAG_FILES]
            );

            Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_WRITTEN);
        }

        /**
         * Retrieves the previously cached parse result for a source rule file if one exists and is
         * still in sync with the file on disk (fingerprint match).
         * @param string $file The absolute path to the source file.
         * @return array|null The cached data, or `null` if absent, stale, or invalid.
         */
        public function fetchFromCache (string $file) : ?array {
            $key = "nexus.file." . md5($file);
            $cached = $this->wingmanCacher->get($key, null);

            if (!is_array($cached) || !isset($cached["_c"], $cached["_fp"])) {
                Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_MISS);
                return null;
            }

            $fingerprint = $this->wingmanCacher->generateFingerprint([$file]);

            if ($cached["_fp"] !== $fingerprint) {
                Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_MISS);
                return null;
            }

            Emitter::create()->with(file: $file, manager: $this)->emit(Signal::CACHE_FILE_HIT);
            return $cached["_c"];
        }
    }
?>