# URL Generation

Nexus supports reverse routing — building URLs from named routes and a set of parameter values — as well as HMAC-signed URLs for tamper-proof, expiry-validated links.

---

## Generating URLs

### `Router::generateUrl (string $name, array $params = [], bool $preserveQueryExtras = true) : string`

Builds a URL for a named route. Named parameter tokens in the pattern are replaced with the corresponding values from `$params`. Parameters not matched to any route token are appended as query string entries when `$preserveQueryExtras` is `true`.

```php
// Route registered as:
//   "users.show" → "/users/{id:int}"

$url = $router->generateUrl("users.show", ["id" => 42]);
// → "/users/42"

// Extra parameters become query string entries:
$url = $router->generateUrl("users.index", ["page" => 2, "per_page" => 15]);
// → "/users?page=2&per_page=15"

// Suppress extra parameters:
$url = $router->generateUrl("users.index", ["page" => 2], preserveQueryExtras: false);
// → "/users"
```

All deferred rule groups are force-loaded before generation, so routes registered via `importLazy()` are always available.

Throws `RouteNotFoundException` if no route with the given name is registered.

---

## Parameter Rules

### Named parameters

Parameters matching a `{name}` or `[name]` token in the pattern are substituted by key.

```php
// Pattern: "/posts/{year:int}/{slug}"
$url = $router->generateUrl("posts.show", ["year" => 2025, "slug" => "hello-world"]);
// → "/posts/2025/hello-world"
```

### Optional parameters

Optional tokens (`[name]`) are omitted from the generated URL (along with their preceding slash) when no value is supplied.

```php
// Pattern: "/archive/[year:int]/[month:int]"
$router->generateUrl("archive", []);                                 // → "/archive"
$router->generateUrl("archive", ["year" => 2025]);                   // → "/archive/2025"
$router->generateUrl("archive", ["year" => 2025, "month" => 3]);     // → "/archive/2025/3"
```

### Wildcard parameters

Single-segment wildcards (`*`) take a scalar value; multi-segment wildcards (`**`) accept a scalar or an indexed array of path segments.

```php
// Pattern: "/files/**"
$url = $router->generateUrl("files.serve", ["**" => ["reports", "2025", "q1.pdf"]]);
// → "/files/reports/2025/q1.pdf"

$url = $router->generateUrl("files.serve", ["**" => "readme.txt"]);
// → "/files/readme.txt"
```

When supplying an array for a wildcard, the number of array elements must equal the number of wildcard occurrences in the pattern; otherwise a `WildcardValueCountException` is thrown.

### Type validation

Parameter values are validated against the type annotation before substitution. A value that does not match the type's regex causes an `InvalidParameterValueException`. A required parameter with no supplied value causes a `MissingParameterException`.

---

## Signed URLs

Signed URLs embed an expiry timestamp and an HMAC-SHA256 signature in the query string. They are useful for one-time links, password-reset tokens, invoice downloads, or any URL that must not be guessable or reusable.

Signed URLs require the `wingman/aegis` package. See [Bridges.md](Bridges.md#aegis) for installation details.

---

### Setup

Call `configureUrlSigning()` once during application bootstrap, before any signed URL is generated or verified.

```php
$router->configureUrlSigning(
    secret: "your-secret-key-at-least-32-characters!",
    defaultTtl: 3600,                   // 1 hour (default)
    signatureParam: "signature",        // query parameter name (default)
    expiryParam: "expires"              // query parameter name (default)
);
```

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `$secret` | `string` | *required* | HMAC key; must be ≥ 32 characters |
| `$defaultTtl` | `int` | `3600` | Default URL lifetime in seconds |
| `$signatureParam` | `string` | `"signature"` | Query parameter name for the HMAC value |
| `$expiryParam` | `string` | `"expires"` | Query parameter name for the expiry timestamp |

---

### `generateSignedUrl (string $name, array $params = [], ?int $ttl = null, bool $preserveQueryExtras = true) : string`

Generates the URL for a named route and appends the expiry timestamp and HMAC signature.

```php
$url = $router->generateSignedUrl("invoice.download", ["id" => 99]);
// → "/invoices/99/download?expires=1742000000&signature=abc123..."

// Override TTL per-link:
$url = $router->generateSignedUrl("password.reset", ["token" => $token], ttl: 900);
```

Throws `UrlSigningNotConfiguredException` when `configureUrlSigning()` has not been called.

---

### `validateSignedUrl (string $url) : bool`

Verifies that the URL's signature is correct and that it has not expired. Returns `false` for both expired links and tampered links without distinguishing between them (intentionally, to prevent timing-based enumeration of expiry state).

```php
if (!$router->validateSignedUrl($request->getFullUrl())) {
    http_response_code(403);
    exit;
}
```

Throws `UrlSigningNotConfiguredException` when `configureUrlSigning()` has not been called.

---

## Complete Signed URL Workflow

```php
// 1. Configure once (typically in a service provider or bootstrap file).
$router->configureUrlSigning(secret: getenv("APP_KEY"), defaultTtl: 3600);

// 2. Generate a signed link (e.g. in a controller or mail template).
$link = $router->generateSignedUrl("download.invoice", ["id" => $invoiceId], ttl: 600);

// 3. Verify when the link is accessed.
if (!$router->validateSignedUrl($currentUrl)) {
    http_response_code(403);
    exit("Link expired or invalid.");
}
// Proceed with the download...
```
