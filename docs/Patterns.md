# Patterns

Nexus routes, rewrites, and redirects are all matched using a URL pattern syntax that supports named parameters, typed parameters, optional segments, and wildcards.

---

## Path Parameters

### Required parameters — `{name}` / `{name: type}`

Declared with curly braces. The parameter must be present in the URL for the pattern to match.

```
/users/{id}          → matches /users/42, /users/abc
/users/{id: int}      → matches /users/42, not /users/abc
/files/{name}.{ext}  → matches /files/report.pdf  (partial segment)
```

### Optional parameters — `[name]` / `[name: type]`

Declared with square brackets. The parameter may be absent; if absent the token (and its leading slash when it occupies a full segment) is removed during matching.

```
/posts/[page: int]    → matches /posts/3 and /posts
/archive/[year]/[month] → matches /archive, /archive/2025, /archive/2025/03
```

---

## Wildcards

### Single-segment wildcard — `*`

Matches exactly one path segment (no slashes).

```
/files/*     → matches /files/report, not /files/reports/2025
```

### Multi-segment wildcard — `**`

Matches one or more path segments, including slashes.

```
/files/**    → matches /files/a, /files/a/b/c
```

### Optional single-segment wildcard — `[*]`

Matches zero or one path segments.

```
/api/[*]     → matches /api and /api/anything
```

### Optional multi-segment wildcard — `[**]`

Matches zero or more path segments.

```
/docs/[**]   → matches /docs and /docs/getting-started/installation
```

---

## Query Segment Parameters

Query parameters use exactly the same placeholder syntax as path parameters — `{name}` for required and `[name]` for optional — but placed in the query portion of the pattern, after `?`.

```
/articles/{articleId: string+}/comments?sort=[sort: string]
  → path param articleId (required, string including slashes)
  → query key "sort" with optional value capture [sort]

/products/{productId: string+}?view=[mode]
  → query key "view" with optional value capture [mode]

/search?q={term}&page=[page: pInt]
  → query key "q" required, key "page" optional integer
```

Query segments are split on `&`. Within each segment the portion before `=` is the key and the portion after is the value — both can contain parameter tokens. Parameters captured from the query are available in the resolved `ArgumentList` alongside path parameters.

When a pattern contains at least one required query parameter, the router will not match requests that lack it entirely. Optional query parameters (`[name]`) are skipped gracefully if the query key is absent.

Appending `!` to the very end of a pattern marks the query string as **forbidden** — the route only matches URLs with no query string at all:

```
/strict/path!
  → matches /strict/path but NOT /strict/path?anything=1
```

---

## Built-in Types

Type annotations restrict which values a parameter will accept. The built-in types and their equivalent regex patterns are:

| Type | Accepts | Example values |
| --- | --- | --- |
| `string` *(default)* | Any non-slash string | `hello`, `foo-bar` |
| `string+` | Any string, including slashes | `a/b/c` |
| `int` | Any integer (negative or positive) | `42`, `-7`, `0` |
| `pInt` | Positive integer (no zero) | `1`, `42` |
| `pInt+` | Non-negative integer (zero included) | `0`, `1`, `42` |
| `nInt` | Negative integer (no zero) | `-1`, `-42` |
| `nInt+` | Non-positive integer (zero included) | `0`, `-1`, `-42` |
| `uInt` | Alias for `pInt+` | `0`, `1`, `42` |
| `float` | Any decimal number | `3.14`, `-0.5`, `1.` |
| `pFloat` | Strictly positive decimal | `.5`, `3.14` |
| `pFloat+` | Non-negative decimal | `0.0`, `3.14` |
| `nFloat` | Strictly negative decimal | `-.5`, `-3.14` |
| `nFloat+` | Non-positive decimal | `0`, `-.5`, `-3.14` |
| `uFloat` | Alias for `pFloat+` | `0.0`, `3.14` |
| `number` | Integer or decimal | `42`, `3.14`, `-1` |
| `bit` | Exactly `0` or `1` | `0`, `1` |
| `bool` | Exactly `true` or `false` | `true`, `false` |
| `date` | ISO 8601 calendar date | `2025-03-19` |
| `time` | 12-hour or 24-hour time | `14:30:00`, `02:30:00 pm` |
| `time24` | 24-hour time with seconds | `14:30:00` |
| `time12` | 12-hour time with seconds + am/pm | `02:30:00 pm` |
| `duration` | `HH:MM` or `HH:MM:SS[.frac]` | `1:30`, `01:30:00.5` |
| `email` | RFC 5321 e-mail address | `user@example.com` |

---

## Verix Schema Types

When `wingman/verix` is installed, a type annotation can be a Verix schema expression instead of a built-in alias. Nexus uses a permissive regex during compilation and then validates the captured value against the schema post-match.

A type is treated as a Verix schema if it contains `<`, `>`, or begins with `@`.

```
/users/{id: int<min=1>}
  → int with a minimum-value constraint (inline named-parameter form)

/articles/{year: int<min=2000, max=2100>}/{slug: string<minLength=2>}
  → multiple inline constraints on the same pattern

/posts/{slug: @BlogSlug}
  → named schema reference registered via Schema::register()
```

> **Note:** Verix schema expressions using struct `{...}` or array `[...]` notation conflict with Nexus's own brace-based parameter tokeniser and cannot be used directly inline. Register complex schemas under a name with `@Ref` instead.

If the value satisfies the pattern regex but fails the Verix schema, a `SchemaValidationException` is thrown. If Verix is not installed, a `VerixNotInstalledException` is thrown at validation time.

See [Bridges.md](Bridges.md#verix) for further details.

---

## Route Targets

After the URL pattern, the target tells Nexus what to do when the pattern matches.

### String command

Dot-separated namespace components followed by a class name, then `::` or `@`, then the method name.

```php
// Explicit class::method
$group->add("users.show", "/users/{id}", "App.Controllers.UserController::show");

// Dot notation is translated to backslash:
// → App\Controllers\UserController::show
```

### Array method map

An associative array where each key is an HTTP method (or `*` to match all methods) and each value is a string command or a per-method options array.

```php
$group->add("posts.resource", "/posts/{id}", [
    "GET" => "App.Controllers.PostController::show",
    "PUT" => "App.Controllers.PostController::update",
    "DELETE" => "App.Controllers.PostController::destroy"
]);

// Wildcard method:
$group->add("catch-all", "/**", [
    "*" => "App.Controllers.FallbackController::handle"
]);
```

### Per-method options array

When registering via an array map, each value can itself be an array with additional route metadata:

```php
$group->add("api.users.show", "/users/{id}", [
    "GET" => [
        "action" => ["show", "App.Controllers.UserController"],
        "middleware" => ["App.Middleware.Auth"],
        "tags" => ["api", "users"],
        "headers" => ["X-Resource" => "user"],
        "contentTypes" => ["application/json"]
    ]
]);
```

### Callable

A closure or any PHP callable. Useful for lightweight routes that do not require a dedicated controller.

```php
$group->add("health", "/health", function () {
    return ["status" => "ok"];
});
```

---

## Redirect Targets

Redirect rule targets specify the destination URL and the optional HTTP status code. They can be expressed as a string command or as an array, both inline (PHP) and in JSON files.

### String command

An optional status code followed by the destination path:

```
/new-path                → redirect to /new-path (defaults to 302)
301 /permanent-path      → 301 Permanent Redirect
302 /temporary-path      → 302 Found
```

### Array / options object

Supports all redirect fields:

| Field | Type | Description |
| --- | --- | --- |
| `path` | `string` | Destination path; may contain parameter references (`{articleId}`) and query parameters |
| `status` | `int` | HTTP redirect status code (default `302`) |
| `headers` | `array` | Extra response headers |
| `preservesQuery` | `bool` | Whether unmatched query parameters are appended to the destination URL |

```php
// PHP inline
[
    "path" => "/articles/{articleId}/comments?sort={sort}",
    "status" => 301,
    "preservesQuery" => true,
    "headers" => ["X-Redirected-By" => "Nexus"]
]
```

```json
// JSON — method-keyed map
{
    "get": {
        "path": "/articles/{articleId: string+}/comments?sort=[sort: string]",
        "status": 301,
        "preservesQuery": true,
        "headers": {
            "X-App-Name": "Wingman/Nexus"
        }
    }
}
```

The destination `path` is a full URL pattern — it is parsed by the same pattern parser, so named path and query parameters captured from the source URL can be referenced by name.

---

## Rewrite Targets

Rewrite rule targets specify the internal path the request is forwarded to before further routing. The rewritten path is resolved again through the full routing pipeline.

### String command

```
/internal/path
```

### Array / options object

| Field | Type | Description |
| --- | --- | --- |
| `path` | `string` | Internal destination path; may contain parameter references and query parameters |
| `preservesQuery` | `bool` | Whether unmatched query parameters are forwarded to the rewritten path |

```php
// PHP inline
[
    "path"           => "/products/{productId}?view={mode}",
    "preservesQuery" => true,
]
```

```json
// JSON — method-keyed map
{
    "get": {
        "path": "/legacy/products/{productId: pInt}/detail?format=[mode: string]",
        "preservesQuery": true
    }
}
```

Chained rewrites are resolved sequentially. If the rewritten path matches another rewrite rule, it is applied again up to the `nexus.maxRewriteDepth` limit (default `10`) before the result is passed to the route resolver.
