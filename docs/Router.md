# Router

`Wingman\Nexus\Router`

`Router` is the central entry point for registering rules and dispatching requests. Internally it coordinates a `RouteResolver`, `RewriteResolver`, and `RedirectResolver`, forwarding all public operations to the appropriate subsystem.

---

## Instantiation

```php
use Wingman\Nexus\Router;

$router = new Router();
```

An optional configuration array (dot-notation keys) or a Cortex `Configuration` object can be passed to override defaults. All Nexus configuration keys begin with the `nexus.` prefix.

```php
$router = new Router([
    "cachingEnabled"   => true,
    "cacheDir" => "/var/cache/nexus"
]);
```

See [Bridges.md](Bridges.md#cortex) for the complete list of configurable keys.

---

## Route Registration

### `group(callable $callback) : static`

Opens a route group. The callback receives a pre-configured `RouteGroup` instance. All routes registered inside the callback are finalised when the callback returns.

```php
$router->group(function ($group) {
    $group->withPrefix("/api/v1")
          ->withNamePrefix("api.v1.")
          ->withMiddleware("App.Middleware.Auth");

    $group->add("users.index", "/users", "App.Controllers.UserController::index");
    $group->add("users.show", "/users/{id:int}", "App.Controllers.UserController::show");
});
```

Groups may be nested by calling `$group->group()` inside the callback. The inner group's prefix and name prefix are appended to the outer group's; middleware, tags, and headers are layered on top.

See [Route-Groups.md](Route-Groups.md) for the full `RouteGroup` API.

---

### `import(RuleType $ruleType, string $file, string ...$files) : static`

Loads rules from one or more JSON or PHP files immediately.

```php
use Wingman\Nexus\Enums\RuleType;

$router->import(RuleType::ROUTE, __DIR__ . "/routes/web.json");
$router->import(RuleType::REDIRECT, __DIR__ . "/routes/redirects.json");
$router->import(RuleType::REWRITE, __DIR__ . "/routes/rewrites.json");
```

See [Importing.md](Importing.md) for file formats and security constraints.

---

### `importRoutes (string $file, string ...$files) : static`

Shorthand for `import(RuleType::ROUTE, ...)`.

---

### `importRedirects (string $file, string ...$files) : static`

Shorthand for `import(RuleType::REDIRECT, ...)`.

---

### `importRewrites (string $file, string ...$files) : static`

Shorthand for `import(RuleType::REWRITE, ...)`.

---

### `importLazy (string $prefix, RuleType $ruleType, string $file, string ...$files) : static`

Registers rule files for deferred loading. The files are parsed only when a request URL whose path starts with `$prefix` arrives. Each registered group is loaded at most once.

```php
$router->importLazy("/api/v2", RuleType::ROUTE, __DIR__ . "/routes/api-v2.json");
$router->importLazy("/admin", RuleType::ROUTE, __DIR__ . "/routes/admin.json");
```

Deferred groups are also force-loaded when `generateUrl()` or any `get*()` introspection method is called, so all routes are available for reverse generation.

---

### `scan (string ...$classes) : static`

Scans one or more classes for `#[Route]` attributes and registers the discovered routes.

```php
$router->scan(
    App\Controllers\UserController::class,
    App\Controllers\PostController::class
);
```

See [Attributes.md](Attributes.md) for the attribute API.

---

### `addResource (string $base, string $class, array $only = [], array $except = []) : static`

Generates the standard seven RESTful routes for `$class` under `$base`.

```php
$router->addResource("users", App\Controllers\UserController::class);
```

Generated routes:

| Name | Pattern | Method(s) | Maps to |
| --- | --- | --- | --- |
| `users.index` | `/users` | `GET` | `::index` |
| `users.index` (shared) | `/users` | `POST` | `::store` |
| `users.create` | `/users/create` | `GET` | `::create` |
| `users.show` | `/users/{id}` | `GET` | `::show` |
| `users.show` (shared) | `/users/{id}` | `PUT`, `PATCH` | `::update` |
| `users.show` (shared) | `/users/{id}` | `DELETE` | `::destroy` |
| `users.edit` | `/users/{id}/edit` | `GET` | `::edit` |

Actions sharing the same URL pattern are merged into a single route entry so that Nexus can correctly distinguish `METHOD_NOT_ALLOWED` from `NOT_FOUND`.

Use `$only` or `$except` to include or exclude specific actions:

```php
use Wingman\Nexus\Enums\ResourceAction;

$router->addResource("posts", PostController::class, only: ["index", "show"]);
$router->addResource("tags", TagController::class, except: ["destroy"]);
$router->addResource("comments", CommentController::class, only: [ResourceAction::INDEX, ResourceAction::SHOW]);
```

---

### `addFallback (string $name, string $pattern, mixed $target) : static`

Registers a fallback route that is evaluated after every normal route has been tested and no match found. Multiple fallbacks are tried in registration order.

```php
$router->addFallback("404", "/**", "App\Controllers\ErrorController::notFound");
```

---

## Dispatching

### `route(string $url, string $method = "GET", string $contentType = "") : RoutingResult`

Dispatches a URL through the full resolution pipeline and returns a `RoutingResult`.

```php
$result = $router->route("/users/42", "GET");

if ($result->hasError()) {
    $error = $result->getError();    // RoutingError enum case
}
else {
    $target = $result->getTarget();  // RouteTarget | RedirectTarget | RewriteTarget
    $args = $result->getArgs();      // ArgumentList with named and indexed parameters
}
```

**Resolution order:**

1. Try the rewrite resolver against the original URL.
2. If a rewrite matches, try the redirect and route resolvers against the rewritten URL.
3. If no rewrite matches, try the redirect and route resolvers against the original URL.
4. Fallback routes are tried after all normal routes.

**`$contentType` filtering:** When non-empty, only routes declaring that content type as an accepted type are candidates. Routes with no declared content types are never filtered.

**`RoutingError` cases:**

| Case | Typical HTTP response |
| --- | --- |
| `NOT_FOUND` | 404 |
| `METHOD_NOT_ALLOWED` | 405 |
| `MAX_REDIRECT_DEPTH_EXCEEDED` | 508 |
| `MAX_REWRITE_DEPTH_EXCEEDED` | 508 |
| `REDIRECT_CYCLE_IDENTIFIED` | 508 |
| `REWRITE_CYCLE_IDENTIFIED` | 508 |
| `CYCLE_IDENTIFIED` | 508 |
| `UNKNOWN` | 500 |

---

## URL Generation

### `generateUrl (string $name, array $params = [], bool $preserveQueryExtras = true) : string`

Builds a URL for a named route, substituting parameter tokens with the supplied values. Parameters not matched to any route token are appended as query string entries when `$preserveQueryExtras` is `true`.

```php
$url = $router->generateUrl("users.show",  ["id" => 42]);    // → "/users/42"
$url = $router->generateUrl("users.index", ["page" => 2]);   // → "/users?page=2"
```

Throws `RouteNotFoundException` if no route with the given name exists.

---

### `configureUrlSigning (string $secret, int $defaultTtl = 3600, string $signatureParam = "signature", string $expiryParam = "expires") : static`

Configures the Aegis URL signer. Must be called before `generateSignedUrl()` or `validateSignedUrl()`. Requires the `wingman/aegis` package.

```php
$router->configureUrlSigning(
    secret: "my-secret-key-at-least-32-characters!",
    defaultTtl: 900
);
```

---

### `generateSignedUrl (string $name, array $params = [], ?int $ttl = null, bool $preserveQueryExtras = true) : string`

Generates a URL for a named route and appends an expiry timestamp and HMAC signature.

```php
$url = $router->generateSignedUrl("invoice.download", ["id" => 99], ttl: 600);
// → "/invoices/99/download?expires=1742000000&signature=..."
```

Throws `UrlSigningNotConfiguredException` when `configureUrlSigning()` has not been called.

---

### `validateSignedUrl (string $url) : bool`

Returns `true` if the URL's signature is valid and it has not expired. Returns `false` for both expired and tampered URLs without distinguishing between them.

```php
if (!$router->validateSignedUrl($request->getFullUrl())) {
    http_response_code(403);
    exit;
}
```

Throws `UrlSigningNotConfiguredException` when `configureUrlSigning()` has not been called.

See [URL-Generation.md](URL-Generation.md) for the full signed-URL workflow.

---

## Introspection

### `getRoutes() : RouteSnapshot[]`

### `getRedirects() : RedirectSnapshot[]`

### `getRewrites() : RewriteSnapshot[]`

Return plain value-object snapshots of all registered rules (including fallbacks) in registration order. Force-loads all pending deferred rule groups before collecting.

```php
foreach ($router->getRoutes() as $snapshot) {
    echo $snapshot->name . "\n";      // "users.show"
    echo $snapshot->pattern . "\n";   // "/users/{id:int}"
}
```

---

## State Management

### `reset() : static`

Clears all registered routes, redirects, rewrites, fallbacks, and pending deferred groups, then invalidates every resolver registry. After calling `reset()` the router is in an empty, ready-to-configure state equivalent to a freshly constructed instance.

Use this in long-lived process environments (Swoole, RoadRunner, ReactPHP) when the same `Router` instance must be fully reconfigured between requests.

```php
// At the start of each request cycle:
$router->reset();
$router->importRoutes(__DIR__ . "/routes/web.json");
```
