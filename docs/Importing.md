# Importing

Nexus can load routing rules from external JSON or PHP files rather than registering everything inline. This is useful for splitting large route definitions across multiple files, for configuration-driven setups, or for deferring route loading until needed.

---

## Supported File Formats

### JSON

A JSON file must contain a top-level array of rule objects. Each object must have three fields:

| Field | Type | Description |
| --- | --- | --- |
| `name` | `string` | Unique route name |
| `pattern` | `string` | URL pattern (any syntax valid inline — including full scheme/host, path parameters, and query parameters) |
| `map` | `string\|object` | A target command string, or a method-keyed object whose values are command strings or per-method option objects |

#### Simple method map

Each method key maps to a target command string (`"Class.Name::method"` or `"Class.Name@method"`):

```json
[
    {
        "name": "users.index",
        "pattern": "/users",
        "map": {
            "GET": "App.Controllers.UserController::index",
            "POST": "App.Controllers.UserController::store"
        }
    },
    {
        "name": "users.show",
        "pattern": "/users/{id: int}",
        "map": {
            "GET": "App.Controllers.UserController::show",
            "PUT": "App.Controllers.UserController::update",
            "DELETE": "App.Controllers.UserController::destroy"
        }
    }
]
```

#### Per-method options object

When a method value is an object instead of a command string, the following fields are recognised:

| Field | Type | Description |
| --- | --- | --- |
| `class` | `string` | Fully-qualified class name; dot or backslash notation |
| `action` | `string\|array` | Method name string, or `["methodName", "ClassName"]` array shorthand |
| `arguments` | `object` | Named extra arguments passed to the handler |
| `middleware` | `string[]` | Middleware class names applied to this method only |
| `tags` | `string[]` | Tags for this method |
| `headers` | `object` | Response headers (`"Header-Name": "value"`) |
| `contentTypes` | `string[]` | Accepted request content types |
| `includeQueryArgs` | `bool\|"append"\|"prepend"` | Whether captured query arguments are forwarded to the handler |
| `preservesQuery` | `bool` | Whether unmatched query parameters are kept after dispatch |

```json
[
    {
        "name": "articles.comments",
        "pattern": "{scheme}://example.com/articles/{articleId: string+}/comments?sort=[sort: string]",
        "map": {
            "DELETE": {
                "action": "Articles::deleteComment",
                "middleware": ["AuthMiddleware"],
                "tags": ["comments", "user"],
                "contentTypes": ["application/json"],
                "headers": {
                    "X-App-Version": "1.0"
                }
            },
            "PUT": {
                "action": ["updateComment", "Articles"],
                "middleware": ["AuthMiddleware"]
            },
            "GET": {
                "class": "App.Controllers.ArticlesController",
                "action": "listComments",
                "arguments": {
                    "env": "production"
                },
                "includeQueryArgs": true,
                "tags": ["comments", "readonly"],
                "contentTypes": ["application/json"],
                "headers": {
                    "Accept": "application/json"
                }
            }
        }
    }
]
```

When the `action` field is an array, index `0` is the method name and index `1` is the class name.

#### Single-target shorthand

When all methods share the same target, `map` can be a plain command string:

```json
[
    {
        "name": "health",
        "pattern": "/health",
        "map": "App.Controllers.HealthController::check"
    }
]
```

---

### JSON — Redirects

Each rule object follows the same `name` / `pattern` / `map` structure. The `map` value (or per-method object) uses redirect-specific fields:

| Field | Type | Description |
| --- | --- | --- |
| `path` | `string` | Destination path; may contain parameter references |
| `status` | `int` | HTTP redirect status code (default `302`) |
| `headers` | `object` | Extra response headers |
| `preservesQuery` | `bool` | Whether unmatched query parameters are appended to the destination URL |

```json
[
    {
        "name": "articles.legacy",
        "pattern": "/v1/articles/{articleId: string+}?category=[category]",
        "map": {
            "GET": {
                "path": "/articles/{articleId: string+}/list?filter=[category: string]",
                "status": 301,
                "preservesQuery": true,
                "headers": {
                    "X-App-Name": "Wingman/Nexus"
                }
            }
        }
    }
]
```

---

### JSON — Rewrites

Rewrite rule objects use the same `name` / `pattern` / `map` structure. Per-method rewrite entries support:

| Field | Type | Description |
| --- | --- | --- |
| `path` | `string` | Internal path to rewrite to; may contain parameter references and query parameters |
| `preservesQuery` | `bool` | Whether unmatched query parameters are forwarded to the rewritten path |

```json
[
    {
        "name": "products.legacy",
        "pattern": "/legacy/products/{productId: pInt}/detail",
        "map": {
            "GET": {
                "path": "/products/{productId}?view=[mode]",
                "preservesQuery": true
            }
        }
    },
    {
        "name": "products.short",
        "pattern": "/p/{productId}?view=[mode]",
        "map": {
            "GET": {
                "path": "/legacy/products/{productId: pInt}/detail?format=[mode: string]",
                "preservesQuery": true
            }
        }
    }
]
```

---

### PHP

A PHP file must `return` an iterable of `Rule` objects (instances of `Route`, `Redirect`, or `Rewrite`).

```php
use Wingman\Nexus\Rules\Route;

return [
    Route::from("users.index", "/users", [
        "GET"  => "App.Controllers.UserController::index",
        "POST" => "App.Controllers.UserController::store"
    ]),

    Route::from("users.show", "/users/{id:int}", [
        "GET" => "App.Controllers.UserController::show",
        "PUT" => "App.Controllers.UserController::update",
        "DELETE" => "App.Controllers.UserController::destroy"
    ]),
];
```

PHP files are not cached via the fingerprint mechanism (only JSON files are). PHP files are executed on every load; use the compiled-route cache ([Caching.md](Caching.md)) to avoid recompilation across requests.

---

## Importing Immediately

### `Router::import(RuleType $ruleType, string $file, string ...$files) : static`

Loads one or more files and registers their rules immediately.

```php
use Wingman\Nexus\Enums\RuleType;

$router->import(RuleType::ROUTE,    __DIR__ . "/routes/web.json");
$router->import(RuleType::REDIRECT, __DIR__ . "/routes/redirects.json");
$router->import(RuleType::REWRITE,  __DIR__ . "/routes/rewrites.json");
```

Convenience shorthands:

```php
$router->importRoutes(__DIR__ . "/routes/web.json");
$router->importRedirects(__DIR__ . "/routes/redirects.json");
$router->importRewrites(__DIR__ . "/routes/rewrites.json");
```

---

## Lazy (Deferred) Importing

### `Router::importLazy(string $prefix, RuleType $ruleType, string $file, string ...$files) : static`

Registers rule files for deferred loading. The files are not parsed until a request URL whose path starts with `$prefix` arrives. Each registered group is loaded at most once; subsequent requests do not reload it.

```php
$router->importLazy("/api/v2", RuleType::ROUTE, __DIR__ . "/routes/api-v2.json");
$router->importLazy("/admin", RuleType::ROUTE, __DIR__ . "/routes/admin.json");
$router->importLazy("/webhooks", RuleType::ROUTE, __DIR__ . "/routes/webhooks.json");
```

Deferred groups are also force-loaded when `generateUrl()` or any `getRoutes()` / `getRedirects()` / `getRewrites()` call is made, so reverse URL generation always has access to all registered routes.

---

## Importing Inside a Group

Files can be imported directly into a `RouteGroup`. All group settings (prefix, name prefix, middleware, tags, headers) are applied to the imported rules.

```php
$router->group(function ($group) {
    $group->withPrefix("/api")
          ->withNamePrefix("api.")
          ->withMiddleware(App\Middleware\Auth::class);

    $group->import(RuleType::ROUTE, __DIR__ . "/routes/api.json");
});
```

---

## Security — Path Confinement

To prevent directory-traversal or arbitrary file inclusion attacks, Nexus restricts rule file imports to a set of trusted root directories. By default the allowed roots are:

1. The package root directory (`dirname(__DIR__)` relative to `RuleImporter.php`)
2. The PHP process working directory (`getcwd()`)

Additional roots can be added via configuration:

```php
$router = new Router([
    "ruleImportRoots" => [
        "/var/www/myapp/config/routes"
    ],
]);
```

If an import path resolves outside every trusted root, an `ImportPathEscapeException` is thrown before the file is read.

---

## Exception Reference for Imports

| Exception | Thrown when |
| --- | --- |
| `ImportPathEscapeException` | The resolved file path is outside all trusted roots |
| `ImportFileNotFoundException` | The file does not exist |
| `ImportFileReadException` | The file exists but cannot be read |
| `InvalidImportFormatException` | JSON decode fails |
| `InvalidImportContentException` | JSON root is not an array, or PHP file does not return an iterable |
| `UnsupportedRuleFileTypeException` | The file extension is not `.json` or `.php` |
