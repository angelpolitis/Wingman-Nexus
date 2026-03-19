# Attribute-Based Routing

Nexus can discover route definitions directly from PHP 8 attributes placed on class methods, eliminating the need for separate route registration files.

---

## The `#[Route]` Attribute

`Wingman\Nexus\Attributes\Route`

Place `#[Route]` on any public method. The owning class is passed to `Router::scan()` or `RouteGroup::scan()` to register all discovered routes at once. No specific base class or interface is required.

The attribute is **repeatable**: multiple `#[Route]` attributes on the same method register that method under several patterns simultaneously.

```php
use Wingman\Nexus\Attributes\Route;
use Wingman\Nexus\Enums\HttpMethod;

class UserController {
    #[Route(pattern: "/users", name: "users.index", methods: [HttpMethod::GET])]
    public function index () : void { /* ... */ }

    #[Route(pattern: "/users/{id: int}", name: "users.show")]
    public function show (int $id) : void { /* ... */ }

    #[Route(pattern: "/users", name: "users.store", methods: [HttpMethod::POST])]
    #[Route(pattern: "/users/{id: int}", name: "users.update", methods: [HttpMethod::PUT, HttpMethod::PATCH])]
    public function upsert () : void { /* ... */ }
}
```

---

## Attribute Parameters

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `$pattern` | `string` | *required* | URL pattern (see [Patterns.md](Patterns.md)) |
| `$name` | `string\|null` | `null` | Route name for URL generation; auto-derived from class + method when `null` |
| `$methods` | `HttpMethod[]\|string[]` | `[HttpMethod::GET]` | HTTP methods this route responds to |
| `$middleware` | `string[]` | `[]` | Middleware class names applied to this route |
| `$tags` | `string[]` | `[]` | Metadata tags for documentation or classification |
| `$headers` | `array` | `[]` | Response headers injected on match |
| `$contentTypes` | `string[]` | `[]` | Content-type constraints; routes without constraints match all types |
| `$queryArgsPlacement` | `RouteTargetQueryArgsPlacement` | `NONE` | Whether captured query args are passed before or after path args |
| `$preservesQuery` | `bool` | `true` | Whether uncaptured query parameters are passed through |

---

## Scanning Classes

### `Router::scan(string ...$classes) : static`

Class names can be given as native PHP `::class` constants (backslash-separated) or as Wingman dot-notation strings — both are accepted.

```php
// PHP ::class constant (backslash notation)
$router->scan(
    App\Controllers\UserController::class,
    App\Controllers\PostController::class
);

// Wingman dot notation — equivalent
$router->scan(
    "App.Controllers.UserController",
    "App.Controllers.PostController"
);
```

### `RouteGroup::scan(string ...$classes) : static`

When scanned inside a group, the group's prefix, name prefix, middleware, tags, and headers are applied to all discovered routes.

Dot notation is accepted here too.

```php
$router->group(function ($group) {
    $group->withPrefix("/api/v1")
          ->withNamePrefix("api.v1.")
          ->withMiddleware(App\Middleware\Auth::class);

    $group->scan("App.Controllers.Api.UserController");
});
```

---

## Auto-Derived Route Names

When `$name` is `null`, the scanner derives a name from the fully-qualified class name and the method name. The class namespace is lowercased and its separators are replaced with dots, then the method name is appended.

```php
// Class: App\Controllers\UserController
// Method: showProfile
// Auto-derived name: "app.controllers.usercontroller.showProfile"
```

Providing an explicit `$name` is recommended for stable URL generation.

---

## Full Example

```php
use Wingman\Nexus\Attributes\Route;
use Wingman\Nexus\Enums\HttpMethod;
use Wingman\Nexus\Router;

class PostController {
    #[Route(
        pattern: "/posts",
        name: "posts.index",
        methods: [HttpMethod::GET],
        tags: ["blog"],
        middleware: [App\Middleware\Cache::class]
    )]
    public function index () : void {}

    #[Route(
        pattern: "/posts/{slug}",
        name: "posts.show",
        methods: [HttpMethod::GET],
        contentTypes: ["text/html", "application/json"],
        preservesQuery: true
    )]
    public function show (string $slug) : void {}

    #[Route(pattern: "/posts", name: "posts.store", methods: [HttpMethod::POST])]
    #[Route(pattern: "/posts/{slug}", name: "posts.update",  methods: [HttpMethod::PUT, HttpMethod::PATCH])]
    #[Route(pattern: "/posts/{slug}", name: "posts.destroy", methods: [HttpMethod::DELETE])]
    public function mutate () : void {}
}

$router = new Router();
$router->scan(PostController::class);
```
