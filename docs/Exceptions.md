# Exceptions

All exceptions thrown by Nexus implement the `Wingman\Nexus\Interfaces\NexusException` marker interface, which extends `Throwable`. This allows callers to catch any Nexus exception with a single catch clause:

```php
use Wingman\Nexus\Interfaces\NexusException;

try {
    $router->route($url, $method);
}
catch (NexusException $e) {
    // Handle any Nexus-specific exception.
}
```

Every exception class lives in the `Wingman\Nexus\Exceptions` namespace.

---

## Exception Hierarchy

```
Throwable
└── NexusException (interface)
    ├── RuntimeException
    │   ├── AegisNotInstalledException
    │   ├── CacheDirectoryException
    │   ├── CacheFileNotFoundException
    │   ├── CacheFileReadException
    │   ├── CachePathEscapeException
    │   ├── CacheWriteException
    │   ├── ImportFileNotFoundException
    │   ├── ImportFileReadException
    │   ├── ImportPathEscapeException
    │   ├── InvalidCacheFileException
    │   ├── InvalidImportContentException
    │   ├── InvalidImportFormatException
    │   ├── InvalidParameterValueException
    │   ├── MissingParameterException
    │   ├── RouteNotFoundException
    │   ├── SchemaValidationException
    │   ├── UnsupportedRuleFileTypeException
    │   ├── VerixNotInstalledException
    │   └── WildcardValueCountException
    ├── InvalidArgumentException
    │   ├── DuplicateParameterException
    │   ├── DuplicateRouteException
    │   ├── EmptyRuleCommandException
    │   ├── ImporterNotConfiguredException
    │   ├── InvalidRewriteCommandException
    │   ├── InvalidRuleFormatException
    │   ├── InvalidStatusCodeException
    │   ├── InvocationArgumentException
    │   ├── MissingRuleFieldException
    │   └── TargetSizeMismatchException
    └── LogicException
        └── UrlSigningNotConfiguredException
```

---

## The `NexusException` Interface

`Wingman\Nexus\Interfaces\NexusException`

A marker interface that extends `Throwable`. All exception classes in Nexus implement this interface. It carries no additional methods.

---

## Caching Exceptions

These exceptions are thrown by `CacheManager` and `Cacher` when cache I/O fails.

### `CacheDirectoryException`

Thrown when the configured cache directory does not exist and cannot be created.

### `CachePathEscapeException`

Thrown when a cache file path resolves to a location outside the cache root directory. Prevents directory traversal in cache operations.

### `CacheFileNotFoundException`

Thrown when a cache file is expected to exist but is absent.

### `CacheFileReadException`

Thrown when a cache file exists but cannot be opened or read.

### `InvalidCacheFileException`

Thrown when a cache file exists and is readable but contains corrupt or invalid data.

### `CacheWriteException`

Thrown when writing a cache file fails (e.g. due to a full filesystem or bad permissions).

---

## Import Exceptions

These exceptions are thrown by `RuleImporter` when loading rule files.

### `ImportPathEscapeException`

Thrown when a rule file path resolves outside all trusted import roots. Prevents directory-traversal attacks during file-based rule loading.

### `ImportFileNotFoundException`

Thrown when a specified rule file does not exist.

### `ImportFileReadException`

Thrown when a rule file exists but cannot be read.

### `InvalidImportFormatException`

Thrown when a JSON rule file cannot be decoded (syntax error, encoding issue).

### `InvalidImportContentException`

Thrown when a JSON file's root value is not an array, or a PHP rule file does not return an iterable.

### `UnsupportedRuleFileTypeException`

Thrown when the rule file has an extension other than `.json` or `.php`.

---

## Routing / Resolution Exceptions

These exceptions are thrown by the resolver pipeline.

### `RouteNotFoundException`

Thrown by `generateUrl()` when no route with the given name is registered.

### `DuplicateRouteException`

Thrown when two routes are registered with the same name.

### `InvalidParameterValueException`

Thrown during URL generation when a supplied parameter value does not satisfy the parameter's type constraint.

### `MissingParameterException`

Thrown during URL generation when a required route parameter has no supplied value.

### `WildcardValueCountException`

Thrown during URL generation when an array is supplied for a wildcard parameter but the number of array elements does not match the number of wildcard occurrences in the pattern.

### `SchemaValidationException`

Thrown post-match (or during URL generation) when a route parameter's Verix schema validation fails. Requires `wingman/verix`.

---

## Rule Format Exceptions

These exceptions are thrown by the target parsers when rule definitions contain invalid data.

### `EmptyRuleCommandException`

Thrown when a redirect or rewrite command string is empty.

### `InvalidRuleFormatException`

Thrown when a rule value that should be an array or string is in an unexpected format.

### `InvalidRewriteCommandException`

Thrown when a rewrite command string contains more than one whitespace-delimited token (rewrite commands must specify only the target path).

### `InvalidStatusCodeException`

Thrown when a redirect target command provides a first token that is not a valid three-digit HTTP status code.

### `MissingRuleFieldException`

Thrown when a required field (e.g. `action` in a route map rule, `path` in a redirect/rewrite map rule) is absent.

### `TargetSizeMismatchException`

Thrown when the number of targets parsed from a map does not match the expected count.

### `DuplicateParameterException`

Thrown when a route pattern declares the same named parameter more than once.

### `InvocationArgumentException`

Thrown when the argument list passed to a target invocation is incompatible with the handler's signature.

### `ImporterNotConfiguredException`

Thrown by `RouteGroup::import()` when no `RuleImporter` has been registered on the group via `withImporter()`. Under normal usage (routes registered through `Router::group()`) this does not occur, as the router always configures the importer automatically.

---

## Bridge Exceptions

These exceptions are thrown by bridge classes when an optional dependency is absent.

### `AegisNotInstalledException`

Thrown by `UrlSigner::sign()` or `UrlSigner::verify()` when the `wingman/aegis` package is not installed.

### `VerixNotInstalledException`

Thrown by the Verix bridge validator when a schema-typed route parameter is encountered but `wingman/verix` is not installed.

### `UrlSigningNotConfiguredException`

Thrown by `Router::generateSignedUrl()` or `Router::validateSignedUrl()` when `configureUrlSigning()` has not been called beforehand.
