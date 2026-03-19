# Wingman — Nexus

A standalone, framework-agnostic HTTP routing package for PHP 8.2+. Part of the [Wingman](https://github.com/angelpolitis/wingman) framework, but fully usable as a standalone library.

Nexus dispatches incoming requests to handlers using pattern-matched route dispatch, internal URL rewrites, and HTTP redirects. It compiles and caches route definitions for production performance, generates URLs from named routes, and signs them for tamper-proof delivery. Bridge packages add declarative configuration, lifecycle events, schema-validated parameters, and HMAC-signed URLs when installed.

---

## Requirements

- PHP 8.2 or higher

### Optional Integrations

| Package | Purpose |
| --- | --- |
| `wingman/cortex` | Declarative dot-notation configuration via `#[Configurable]` annotations |
| `wingman/corvus` | Routing lifecycle events (routing started/completed, cache hits/misses) |
| `wingman/verix` | Schema expressions as route parameter types |
| `wingman/aegis` | HMAC-SHA256 signed and expiry-validated URLs |

---

## Installation

```bash
composer require wingman/nexus
```

---

## Quick Start

```php
use Wingman\Nexus\Router;
use Wingman\Nexus\Enums\RoutingError;

$router = new Router();

// Register routes inside a group.
$router->group(function ($group) {
    $group->add("home", '/', fn () => "Welcome!");
    $group->add("users.show", "/users/{id:int}", "App\Controllers\UserController::show");
});

// Dispatch an incoming request.
$result = $router->route($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"]);

if ($result->hasError()) {
    $code = match ($result->getError()) {
        RoutingError::NOT_FOUND => 404,
        RoutingError::METHOD_NOT_ALLOWED => 405,
        default => 500
    };
    http_response_code($code);
}
else {
    $target = $result->getTarget(); // RouteTarget, RedirectTarget, or RewriteTarget
    $args = $result->getArgs();
    // Dispatch $target...
}
```

---

## Core Concepts

| Concept | Description |
| --- | --- |
| **Route** | A named URL pattern mapped to a callable handler. |
| **Rewrite** | An internal URL transformation applied transparently before route matching. |
| **Redirect** | An external HTTP 3xx redirect issued by the router. |
| **Pattern** | A URL template with named parameters, optional segments, and wildcards. |
| **RoutingResult** | Value object returned by `route()`, containing a `Target` or a `RoutingError`. |
| **RouteGroup** | A scoped sub-registration that applies a shared prefix, middleware, tags, and headers to a batch of routes. |
| **RuleType** | Enum (`ROUTE`, `REWRITE`, `REDIRECT`) that discriminates rule kinds when importing files. |

---

## Registration Methods

| Method | Description |
| --- | --- |
| `group($callback)` | Open a group and register routes inside a callable. |
| `import(RuleType, ...$files)` | Load rules from JSON or PHP files. |
| `importRoutes(...$files)` | Shorthand for `import(RuleType::ROUTE, ...)`. |
| `importRedirects(...$files)` | Shorthand for `import(RuleType::REDIRECT, ...)`. |
| `importRewrites(...$files)` | Shorthand for `import(RuleType::REWRITE, ...)`. |
| `importLazy($prefix, RuleType, ...$files)` | Defer file loading until a URL with the given prefix arrives. |
| `scan(...$classes)` | Discover `#[Route]` attributes on class methods. |
| `addResource($base, $class)` | Generate RESTful CRUD routes for a class. |
| `addFallback($name, $pattern, $target)` | Register a catch-all route evaluated after all other rules. |

---

## Dispatching

```php
$result = $router->route("/users/42", "GET");

if ($result->hasError()) {
    $error = $result->getError(); // RoutingError enum
}
else {
    $target = $result->getTarget();
    $args = $result->getArgs();
}
```

Nexus evaluates rules in order: rewrites → redirects → routes. When a rewrite matches its output URL is re-evaluated through all subsequent resolvers, making the full chain transparent to the caller.

---

## Documentation

| Topic | File |
| --- | --- |
| Router API reference | [docs/Router.md](docs/Router.md) |
| Route groups | [docs/Route-Groups.md](docs/Route-Groups.md) |
| URL pattern syntax | [docs/Patterns.md](docs/Patterns.md) |
| File-based imports | [docs/Importing.md](docs/Importing.md) |
| Attribute-based routing | [docs/Attributes.md](docs/Attributes.md) |
| URL generation and signed URLs | [docs/URL-Generation.md](docs/URL-Generation.md) |
| Compiled-route caching | [docs/Caching.md](docs/Caching.md) |
| Bridge integrations | [docs/Bridges.md](docs/Bridges.md) |
| Exception reference | [docs/Exceptions.md](docs/Exceptions.md) |

---

## Licence

This project is licensed under the **Mozilla Public License 2.0 (MPL 2.0)**.

Wingman Nexus is part of the **Wingman Framework**, Copyright (c) 2019–2026 Angel Politis.

For the full licence text, please see the [LICENSE](LICENSE) file.
