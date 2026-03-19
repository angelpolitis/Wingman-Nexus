# Caching

Nexus can compile and cache route definitions to disk, eliminating pattern-parsing overhead on every request. Caching is disabled by default and must be explicitly enabled for production deployments.

---

## What Gets Cached

Nexus maintains three separate cache files per rule type (`routes`, `redirects`, `rewrites`):

| Cache file | Content |
| --- | --- |
| `compiled` | Pre-parsed route patterns converted to regex, with extracted parameter metadata |
| `definitions` | Normalised route metadata: name, pattern, parameter list, query segments |
| `targets` | HTTP method → handler maps for each route |

A fourth cache exists per imported JSON file: Nexus fingerprints each JSON source file by its last-modified time and stores the parsed rule objects. If the source file changes, the cache is invalidated automatically on the next request.

---

## Cache Invalidation

Nexus uses file fingerprinting — a hash of each source file's modification timestamp — to detect stale caches. When any source file has changed, the relevant cache entries are regenerated transparently on the next request. No manual cache-clearing step is required for normal development workflows.

---

## Enabling the Cache

Nexus accepts cache settings through either an associative array or a `Configuration` object.

### Short-form keys

Array input supports the mapped keys from `CacheManager::KEY_MAP`:

```php
$router = new Router([
    "cachingEnabled" => true,
    "cacheDir" => "/var/cache/myapp/nexus"
]);
```

When caching is disabled (default), Nexus keeps an in-memory registry for the current request only.

### Configuration object (dotted keys)

`Configuration` input uses canonical configuration keys:

```php
$configuration = new Configuration([
    "nexus.cache.enabled" => true,
    "nexus.locations.cache" => "/var/cache/myapp/nexus"
]);

$router = new Router($configuration);
```

---

## Configuration Keys

Canonical keys live under `nexus.cache.*` and `nexus.locations.*`. The short-key aliases below apply to array input.

| Short Key | Full Key | Type | Default | Description |
| --- | --- | --- | --- | --- |
| `cachingEnabled` | `nexus.cache.enabled` | `bool` | `false` | Whether compiled route data is persisted to disk |
| `cacheDir` | `nexus.locations.cache` | `string` | `<package>/cache` | Filesystem path of the cache directory |
| `fileExtension` | `nexus.cache.fileExtension` | `string` | `"cache"` | File extension appended to every cache filename |
| `compiledFile` | `nexus.cache.compiledFile` | `string` | `"compiled"` | Base filename (without extension) for the compiled-routes file |
| `definitionsFile` | `nexus.cache.definitionsFile` | `string` | `"definitions"` | Base filename (without extension) for the route-definitions file |
| `targetsFile` | `nexus.cache.targetsFile` | `string` | `"targets"` | Base filename (without extension) for the target-maps file |
| `variableRegex` | `nexus.variableRegex` | `string` | `PatternParser::DEFAULT_VARIABLE_REGEX` | Regex pattern for variable detection |
| `variableDefaultType` | `nexus.variableDefaultType` | `string` | `PatternParser::DEFAULT_VARIABLE_DEFAULT_TYPE` | Type name for variables with no explicit annotation |

---

## Cache Directory Layout

When caching is enabled, the cache directory contains one subdirectory per rule type and a `files/` subdirectory for per-file JSON caches.

```
cache/
├── files/
│   ├── 3f4a8b...cache   ← per-file JSON parse cache (keyed by MD5 of file path)
│   └── ...
├── routes/
│   ├── compiled.cache
│   ├── definitions.cache
│   └── targets.cache
├── redirects/
│   ├── compiled.cache
│   ├── definitions.cache
│   └── targets.cache
└── rewrites/
    ├── compiled.cache
    ├── definitions.cache
    └── targets.cache
```

---

## Cache Signals (Corvus Integration)

When `wingman/corvus` is installed, Nexus emits the following signals during cache operations:

| Signal | Emitted when |
| --- | --- |
| `nexus.cache.file.hit` | A JSON rule file was resolved from the per-file cache |
| `nexus.cache.file.miss` | A JSON rule file was not cached or its fingerprint was stale |
| `nexus.cache.file.written` | A parsed JSON rule file was written to the per-file cache |
| `nexus.cache.registry.hit` | The compiled route registry was loaded from its cache files |
| `nexus.cache.registry.miss` | No valid registry cache existed; `buildCache()` was called |
| `nexus.cache.registry.written` | The compiled registry was persisted to cache files |

Each signal carries a relevant payload (e.g. the source file path and the `CacheManager` instance). See [Bridges.md](Bridges.md#corvus) for how to subscribe to these signals.

---

## Security

Cache files are stored as serialised PHP data. The cache directory should be:

- Outside the web root (not publicly accessible)
- Writable only by the web server process
- Excluded from version control

Nexus validates that cache file paths remain within the configured cache directory and will throw `CachePathEscapeException` if a path escapes the root.

---

## Wingman Stasis Bridge

When `wingman/stasis` is installed, Nexus can delegate all cache persistence to a `Cacher` instance instead of its built-in file-based storage. This enables pluggable backends (local filesystem, APCu, Redis, Memcached) and tagged bulk-invalidation, without any change to core routing logic.

### Setup

Construct the bridge `CacheManager` with a configured `Cacher` and pass it to the `Router`:

```php
use Wingman\Stasis\Cacher;
use Wingman\Stasis\Adapters\RedisAdapter;
use Wingman\Nexus\Bridge\Stasis\CacheManager;
use Wingman\Nexus\Router;

$cacher = (new Cacher())->setAdapter(new RedisAdapter($redis));
$router = new Router(cacheManager: new CacheManager($cacher));
```

The `cachingEnabled` flag is not required when the bridge is used; persistence is delegated to the provided `Cacher` instance. `CacheManager::__construct()` still accepts an optional configuration payload (array or `Configuration`) and an optional `TypeRegistry`.

### Cache Keys

| Purpose | Key format |
| --- | --- |
| Registry slot (compiled, definitions, or targets) | `nexus.registry.<type>.<slug>` |
| Per-file fingerprint cache | `nexus.file.<md5>` |

`<type>` is one of `routes`, `redirects`, or `rewrites`. `<slug>` is one of `compiled`, `definitions`, or `targets`. `<md5>` is the MD5 hash of the absolute path of the source JSON file.

### Tags

Every entry the bridge writes is tagged, enabling targeted bulk-invalidation:

| Tag | What it covers |
| --- | --- |
| `nexus` | All Nexus cache entries |
| `nexus.registry` | Compiled registry entries only |
| `nexus.files` | Per-file fingerprint entries only |

### Signals

The bridge emits the same Corvus signals as the built-in `CacheManager`. See [Cache Signals (Corvus Integration)](#cache-signals-corvus-integration) above for the full list.

---

## Exception Reference for Caching

| Exception | Thrown when |
| --- | --- |
| `CacheDirectoryException` | The cache directory does not exist and cannot be created |
| `CachePathEscapeException` | A cache file path resolves outside the cache directory |
| `CacheFileNotFoundException` | A cache file is expected but does not exist |
| `CacheFileReadException` | A cache file cannot be read |
| `InvalidCacheFileException` | A cache file exists but contains invalid or corrupt data |
| `CacheWriteException` | Writing a cache file fails |
