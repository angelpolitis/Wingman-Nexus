# Route Groups

`Wingman\Nexus\RouteGroup`

A `RouteGroup` is a scoped sub-registration that applies shared attributes — URL prefix, name prefix, middleware, tags, and response headers — to every route registered within a `Router::group()` callback.

The group acts as a mini sub-router: it exposes `add()`, `import()`, `scan()`, and `group()` methods that mirror their `Router` counterparts. All routes are collected internally and finalised with the group's settings applied when the callback returns.

---

## Opening a Group

```php
$router->group(function ($group) {
    $group->withPrefix("/api")
          ->withNamePrefix("api.");

    $group->add("users.index", "/users",        "App\Controllers\UserController::index");
    $group->add("users.show",  "/users/{id:int}", "App\Controllers\UserController::show");
});
// Routes are now registered as:
//   "api.users.index" → "/api/users"
//   "api.users.show"  → "/api/users/{id:int}"
```

---

## Fluent Configuration

All `with*` methods return the group instance, allowing them to be chained.

### `withPrefix(string $prefix) : static`

Sets the URL path prefix prepended to every route pattern registered in the group.

```php
$group->withPrefix("/api/v2");
```

When groups are nested, the inner prefix is appended to the outer prefix.

---

### `withNamePrefix(string $namePrefix) : static`

Sets the route name prefix prepended to every route name registered in the group. A trailing dot is recommended for clean dot-notation names.

```php
$group->withNamePrefix("api.v2.");
// A route named "users.show" becomes "api.v2.users.show".
```

---

### `withMiddleware(string ...$middleware) : static`

Sets the middleware class names prepended to every route's middleware stack in the group.

```php
$group->withMiddleware(
    App\Middleware\Authenticate::class,
    App\Middleware\VerifyCsrfToken::class
);
```

When groups are nested, the outer group's middleware is prepended before the inner group's.

---

### `withTags(string ...$tags) : static`

Sets metadata tags merged into every route in the group.

```php
$group->withTags("internal", "v2");
```

---

### `withHeaders(array $headers) : static`

Sets response headers merged into every route in the group.

```php
$group->withHeaders([
    "X-API-Version" => "2",
    "Cache-Control" => "no-store"
]);
```

---

## Registering Routes

### `add(string $name, string $pattern, mixed $target) : static`

Registers a single route directly into the group.

```php
$group->add("posts.show", "/posts/{slug}", "App\Controllers\PostController::show");
```

The target accepts the same forms documented in [Patterns.md](Patterns.md) — a string command, an array method map, or a callable.

---

### `import(RuleType $ruleType, string $file, string ...$files) : static`

Imports rules from files into the group. Requires that `withImporter()` has been called (it is called automatically when the group is created by `Router::group()`).

```php
use Wingman\Nexus\Enums\RuleType;

$group->import(RuleType::ROUTE, __DIR__ . "/routes/api.json");
```

Throws `ImporterNotConfiguredException` if no importer has been configured.

---

### `scan(string ...$classes) : static`

Scans one or more classes for `#[Route]` attributes and adds the discovered routes to the group.

```php
$group->scan(App\Controllers\UserController::class);
```

All routes discovered by `scan()` have the group's prefix, name prefix, middleware, tags, and headers applied.

---

## Nested Groups

Call `group()` on the `RouteGroup` instance to open a nested group. The inner group's URL prefix and name prefix are appended after the outer group's; middleware, tags, and headers from the inner group are layered on top of those from the outer group.

```php
$router->group(function ($outer) {
    $outer->withPrefix("/api")
          ->withNamePrefix("api.")
          ->withMiddleware(App\Middleware\Auth::class);

    $outer->group(function ($inner) {
        $inner->withPrefix("/admin")
              ->withNamePrefix("admin.")
              ->withMiddleware(App\Middleware\RequireAdmin::class);

        // Final route: pattern "/api/admin/users", name "api.admin.users.index"
        // Middleware: [Auth, RequireAdmin]
        $inner->add("users.index", "/users", "App\Controllers\Admin\UserController::index");
    });
});
```

---

## How Group Settings Are Applied

When `buildRules()` is called internally after the callback returns, each collected route is updated as follows:

| Attribute | Applied as |
| --- | --- |
| `prefix` | Prepended to the route pattern |
| `namePrefix` | Prepended to the route name |
| `middleware` | Prepended to the route's middleware array |
| `tags` | Merged (group tags first) into the route's tag array |
| `headers` | Merged (group headers first) into the route's header array |

If a route's target is a plain callable and the group has any middleware, tags, or headers set, the callable is wrapped in a `GroupedCallable` value object so the metadata can be carried through the resolver pipeline.
