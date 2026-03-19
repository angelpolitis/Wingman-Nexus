# Bridges

Nexus integrates with four optional Wingman packages through lightweight bridge classes. Each bridge follows a graceful-degradation strategy: when the underlying package is not installed, operations are either silently absorbed (Corvus, Cortex) or throw a descriptive exception (Aegis, Verix), depending on whether the absence can safely be ignored.

---

## Cortex

**Package:** `wingman/cortex` *(optional)*

The Cortex bridge allows Nexus to be configured declaratively via Cortex's `Configuration` object or a plain PHP array with dot-notation keys. When Cortex is not installed, Nexus falls back to its own lightweight array hydrator that reads the same dot-notation keys, so the configuration API is identical regardless of whether Cortex is installed.

### Usage

Pass a `Configuration` object or a plain array to the `Router` constructor:

```php
use Wingman\Cortex\Configuration;
use Wingman\Nexus\Router;

// Via Cortex Configuration:
$config = new Configuration(["cachingEnabled" => true, ...]);
$router = new Router($config);

// Via plain array (works without Cortex):
$router = new Router([
    "cachingEnabled" => true,
    "cacheDir" => "/var/cache/nexus"
]);
```

### All Configuration Keys

**Cache**

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `nexus.cache.enabled` | `bool` | `false` | Whether compiled routes are persisted to disk |
| `nexus.locations.cache` | `string` | `<package>/cache` | Cache directory path |
| `nexus.cache.fileExtension` | `string` | `"cache"` | Cache file extension |
| `nexus.cache.compiledFile` | `string` | `"compiled"` | Base filename for compiled-routes cache |
| `nexus.cache.definitionsFile` | `string` | `"definitions"` | Base filename for route-definitions cache |
| `nexus.cache.targetsFile` | `string` | `"targets"` | Base filename for target-maps cache |

**Locations**

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `nexus.locations.ruleImportRoots` | `string[]` | `[]` | Additional trusted root directories for file imports |

**Type system**

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `nexus.variableDataTypes` | `array` | Built-in types | Custom type name → regex map merged into the type registry |
| `nexus.variableDefaultType` | `string` | `"string"` | Default type used when a parameter has no explicit annotation |
| `nexus.variableRegex` | `string` | *Default regex* | Variable-detection regex used by `CacheManager` when building the cache |
| `nexus.regex.variable` | `string` | *Default regex* | Variable-detection regex used by `Resolver` and `RouteMatcher` at dispatch time |

When overriding the variable regex, set **both** `nexus.variableRegex` and `nexus.regex.variable` to keep cache-build and dispatch consistent.

**Regex**

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `nexus.regex.httpStatus` | `string` | *HTTP status regex* | Regex used to validate HTTP redirect status codes in target commands |

**Symbols**

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `nexus.symbols.classOperators` | `string[]` | `["::", "@"]` | Separators between class name and method name in target command strings |
| `nexus.symbols.deny` | `string` | `"-"` | Target expression that marks a route as explicitly denied |
| `nexus.symbols.commandDelimiters` | `string[]` | `[";"]` | Separators between multiple method-command pairs in a target expression |
| `nexus.symbols.methodDelimiters` | `string[]` | `[",", "|"]` | Separators between HTTP method names in a method key |
| `nexus.symbols.wildcard1` | `string` | `"*"` | Single-segment wildcard token (matches one path segment) |
| `nexus.symbols.wildcardN` | `string` | `"**"` | Multi-segment wildcard token (matches one or more path segments) |

**Limits**

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `nexus.maxRedirectDepth` | `int` | `10` | Maximum redirect hops before aborting with an error |
| `nexus.maxRewriteDepth` | `int` | `10` | Maximum rewrite hops before aborting with an error |

---

## Corvus

**Package:** `wingman/corvus` *(optional)*

The Corvus bridge emits signals on the Corvus event bus at key points in the routing lifecycle. When Corvus is not installed, a null-object stub absorbs all calls silently; no code changes are required.

### Signals

| Signal | Emitted when | Payload keys |
| --- | --- | --- |
| `nexus.routing.started` | `route()` is called | `url`, `method`, `router` |
| `nexus.routing.completed` | `route()` finishes (success or error) | `url`, `method`, `result`, `router` |
| `nexus.route.compiled` | A route pattern is compiled to regex | `name`, `pattern`, `ruleType`, `manager` |
| `nexus.import.completed` | One or more rule files are imported | `ruleType`, `files`, `rules`, `importer` |
| `nexus.cache.file.hit` | A JSON file was resolved from cache | `file`, `manager` |
| `nexus.cache.file.miss` | A JSON file's cache was absent or stale | `file`, `manager` |
| `nexus.cache.file.written` | A JSON file's parse result was cached | `file`, `manager` |
| `nexus.cache.registry.hit` | Registry was loaded from disk cache | `ruleType`, `manager` |
| `nexus.cache.registry.miss` | No valid disk cache for registry | `ruleType`, `manager` |
| `nexus.cache.registry.written` | Registry was persisted to disk cache | `ruleType`, `manager` |

### Subscribing to Signals

Use Corvus `Listener` to subscribe:

```php
use Wingman\Corvus\Listener;

Listener::create()
    ->when("nexus.routing.completed")
    ->do(function ($e) {
        $result = $e->payload[0]["result"];
        if ($result->hasError()) {
            // Log routing errors...
        }
    });

Listener::create()
    ->when("nexus.cache.*")
    ->do(function ($e) {
        // Monitor cache hit/miss ratio...
    });
```

---

## Verix

**Package:** `wingman/verix` *(optional)*

The Verix bridge enables route parameters to use Verix schema expressions as their type annotation. When Verix is present, schema-typed parameters are matched against a permissive regex during compilation (capturing any non-slash value) and then validated against the full schema after a match occurs.

### Identifying a Verix Schema

A type annotation is treated as a Verix schema if it contains `<`, `>`, `{`, `}`, `[`, `]`, or begins with `@` (a named schema reference). Plain aliases such as `int`, `email`, or `string` are handled entirely by the built-in type registry and are never routed through Verix.

```
/users/{id: int<min=1>}              ← inline schema expression
/posts/{slug: @blogSlugSchema}       ← named schema reference
```

### Behaviour

1. During route compilation, schema-typed parameters compile to the permissive regex `[^\\/]*`.
2. When a request matches the route, the captured value is passed to `Verix\Schema::validate()`.
3. If validation fails, a `SchemaValidationException` is thrown.
4. If Verix is not installed, a `VerixNotInstalledException` is thrown at validation time.

### Registering Named Schemas

Named schemas are registered via Verix's own API before routing begins:

```php
use Wingman\Verix\Facades\Schema;

// Register a named schema using Verix DSL before routing begins
Schema::register("BlogSlug", "string<minLength=3, maxLength=100>");

$router->group(function ($group) {
    $group->add("posts.show", "/posts/{slug: @BlogSlug}", "PostController::show");
});
```

---

## Aegis

**Package:** `wingman/aegis` *(optional — required for signed URLs)*

The Aegis bridge provides HMAC-SHA256 signed URL generation and verification via Aegis's `SignedUrlService`. Unlike Corvus, URL signing cannot silently degrade: a missing secret would produce unsigned URLs that appear valid, which is a security hole.

When Aegis is not installed, calling `generateSignedUrl()` or `validateSignedUrl()` throws `AegisNotInstalledException` immediately, making the misconfiguration visible.

### Setup

```php
$router->configureUrlSigning(
    secret: "your-secret-key-at-least-32-characters!",
    defaultTtl: 3600,             // 1 hour (default)
    signatureParam: "signature",  // default
    expiryParam: "expires"        // default
);
```

`configureUrlSigning()` must be called before any `generateSignedUrl()` or `validateSignedUrl()` call; failing to do so throws `UrlSigningNotConfiguredException`.

### Generating Signed URLs

```php
// Per-call TTL override:
$url = $router->generateSignedUrl("password.reset", ["token" => $token], ttl: 900);

// Use default TTL configured above:
$url = $router->generateSignedUrl("invoice.download", ["id" => 42]);
```

The generated URL includes two extra query parameters (using the configured names):

```
/invoices/42/download?expires=1742010000&signature=abcdef1234...
```

### Verifying Signed URLs

```php
if (!$router->validateSignedUrl($request->getFullUrl())) {
    // Returns false for both expired and tampered URLs.
    http_response_code(403);
    exit;
}
```

See [URL-Generation.md](URL-Generation.md) for the complete workflow.

---

## Stasis

Nexus ships with a minimal internal file-based cacher sufficient for single-process deployments. Installing `wingman/stasis` (optional) replaces it with a fully pluggable backend — local filesystem with sharding, APCu, Redis, or Memcached — plus tagged bulk invalidation.

### Setup

Construct a `Wingman\Stasis\Cacher` instance, attach the desired adapter, and pass a `CacheManager` bridge to the `Router`:

```php
use Wingman\Stasis\Cacher;
use Wingman\Nexus\Bridge\Stasis\CacheManager;
use Wingman\Nexus\Router;

$cacher = new Cacher();

$router = new Router(
    config: ["cachingEnabled" => true],
    cacheManager: new CacheManager($cacher)
);
```

Any Nexus configuration accepted by the base `CacheManager` can be passed as the second argument:

```php
$cacheManager = new CacheManager($cacher, [
    "cachingEnabled" => true,
    "cacheDir" => "/var/cache/nexus",
]);
```

### Pluggable Adapters

The bridge delegates every read and write to the `Cacher` instance, so swapping the backend is a one-line change:

```php
use Wingman\Stasis\Adapters\ApcuAdapter;
use Wingman\Stasis\Adapters\RedisAdapter;

// In-memory (single process):
$cacher = (new Cacher())->setAdapter(new ApcuAdapter());

// Distributed (multi-process / multi-server):
$redis = new Redis();
$redis->connect("127.0.0.1", 6379);
$cacher = (new Cacher())->setAdapter(new RedisAdapter($redis));
```

### Cache Keys and Tags

The bridge uses predictable string keys and three tags for selective invalidation:

| Purpose | Key format | Tag |
| --- | --- | --- |
| Registry — compiled routes | `nexus.registry.<type>.compiled` | `nexus.registry` |
| Registry — definitions | `nexus.registry.<type>.definitions` | `nexus.registry` |
| Registry — target maps | `nexus.registry.<type>.targets` | `nexus.registry` |
| Per-file fingerprint cache | `nexus.file.<md5>` | `nexus.files` |

All entries also receive the `nexus` tag. `<type>` is one of `routes`, `redirects`, or `rewrites`.

To invalidate a specific subset:

```php
$cacher->clearByTags("nexus.registry");   // compiled registry only
$cacher->clearByTags("nexus.files");      // per-file parse caches only
$cacher->clearByTags("nexus");            // all Nexus cache entries
```

### Fingerprint validation

The per-file cache (`cache()` / `fetchFromCache()`) stores a SHA-256 fingerprint of each source file's content and modification time alongside the parsed data. On every read the fingerprint is regenerated and compared; a mismatch causes a miss and triggers a reparse, so stale caches are detected automatically without any TTL dependency.
