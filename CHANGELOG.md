# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] — Unreleased

Initial release of Wingman Nexus — a standalone, framework-agnostic HTTP routing engine providing pattern-matched route dispatch, URL rewriting, HTTP redirects, reverse URL generation, compiled-route caching, attribute-based route discovery, and optional integration with the Wingman ecosystem.

### Added

**Core router**

- `Router` class providing central HTTP request dispatcher with lazy route compilation
- `RouteCompiler` for converting high-level route definitions into low-level regex patterns
- `TypeRegistry` managing variable type definitions and coercion
- `Helper` utility methods for URL encoding, URI normalisation, and helper functions

**Rule types**

- `Route` for matched HTTP method → callable dispatch
- `Redirect` for HTTP-status redirects with optional headers and query-string preservation
- `Rewrite` for internal URL substitution and forwarding
- `RuleType` enum distinguishing between routes, redirects, and rewrites
- RESTful resource registration via `Router::resource()`

**Pattern system**

- `PatternParser` supporting full URL syntax: path segments, wildcards, query parameters, optional segments, and typed variables
- Variable syntax: `{name:type}`, `[optional]`, `*wildcards`, `name=value`, `[name=default]`
- Verix schema integration for inline type validation (e.g. `{id: int<min=1>}`, `{slug: @UserSlug}`)
- Query parameter binding: `?sort=[dir]`, `?page=[page]`
- Dot notation support for fully qualified class names via `AttributeScanner`

**Target system**

- `RouteTarget`, `RedirectTarget`, `RewriteTarget`, `OptionsTarget`, `AnonymousTarget` for different dispatch strategies
- `TargetMap` consolidating HTTP methods and callables for a single route
- Parsers (`RouteTargetParser`, `RedirectTargetParser`, `RewriteTargetParser`) for converting route syntax to executable targets

**Caching**

- `CacheManager` managing compiled routes, definitions, and target-maps for file-based persistence
- Internal `Cacher` and `Cache` classes handling file I/O and fingerprint validation
- Per-file JSON import cache with automatic invalidation on source modification
- Pluggable backend support via `Bridge\Stasis\CacheManager`

**URL generation**

- Reverse URL generation via `Router::generateUrl()`
- Parameter substitution preserving type coercion
- Wildcard value collection for routing back to multi-segment patterns
- Query-string handling: optional params omitted if matching default, required params always present

**Import system**

- `Router::import()` static route loading from JSON or PHP files
- `Router::lazyImport()` deferred loading on first request
- Format support: associative arrays (PHP) and JSON flat structures with type declarations
- Schema validation via Verix if installed

**Attribute discovery**

- `AttributeScanner` scanning class hierarchies for `#[Route]` attributes
- `Router::scan()` for dynamic attribute-based route registration
- Support for nested namespaces and inheritance chains
- Dot-notation class name support (e.g. `My.App.Controllers.ArticleController`)

**Objects**

- `CompiledRoute` storing parsed patterns, regex, and parameter metadata
- `RouteDefinition` normalising user-supplied route syntax
- `Parameter` representing a single URL or query parameter with type and constraints
- `QuerySegment` describing query-string binding structure
- `ArgumentList` and `ArgumentSet` for managing dispatch arguments
- `RedirectSnapshot` and `RewriteSnapshot` for storing target state
- `GroupedCallable` for method-less callable reference resolution
- `RoutingPath` and `URI` for URL parsing and analysis
- `RouteRegistry`, `RoutingResult`, `RoutingPath` for dispatch state

**Enums**

- `HttpMethod` covering standard REST verbs and wildcard ANY
- `RuleType` distinguishing routes, redirects, rewrites
- `Signal` indicating cache, compilation, and dispatch events
- `ResourceAction` mapping RESTful actions to HTTP methods
- `RouteQueryRequirement` qualifying parameter strictness
- `RouteTargetQueryArgsPlacement` positioning query data in argument lists
- `RoutingError` classifying dispatch failures

**Interfaces**

- `NexusException` contract for all package exceptions
- `Resolver` contract for HTTP method dispatcher implementation
- `Target` contract for dispatch strategy implementations

**Bridge: Cortex**

- `Bridge\Cortex\Configuration` stub and alias supporting `#[Configurable]` attribute hydration
- Short-key mapping via `CacheManager::KEY_MAP` for concise configuration (e.g. `enabled`, `cacheDir` vs dotted equivalents)
- `Configurable` attribute for declaring configuration binding points

**Bridge: Corvus**

- `Bridge\Corvus\Emitter` stub and alias for event dispatch
- Six cache-related signals: `CACHE_FILE_WRITTEN`, `CACHE_INVALIDATED`, `REGISTRY_BUILT`, `REGISTRY_CACHED`, `COMPILE_START`, `COMPILE_COMPLETE`
- Optional event observability for lifecycle hooks

**Bridge: Verix**

- `Bridge\Verix\Validator` for schema-based parameter validation
- Inline type expressions: `int<min=1, max=100>`, `string<minLength=2>`, `@EmailSchema`
- Named schema references via `Schema::register()` and `Validator::validate()`

**Bridge: Aegis**

- `Bridge\Aegis\UrlSigner` for cryptographic URL signing and verification
- `Router::configureUrlSigning()` for symmetric-key setup
- `Router::generateSignedUrl()` and `Router::validateSignedUrl()` for signed dispatch
- HMAC-SHA256 signing with configurable algorithms

**Bridge: Stasis**

- `Bridge\Stasis\CacheManager` delegating file I/O to Wingman\Stasis adapters
- Pluggable backend support (Redis, Memcached, filesystem, in-memory)
- Tag-based cache invalidation: `TAG_ALL`, `TAG_FILES`, `TAG_REGISTRY`

**Exception hierarchy**

- 30 exception classes covering: cache errors, compilation errors, import errors, invalid parameters, malformed rules, missing fields, routing failures, schema violations, URL signing issues, bridge availability checks, and more

**Tests**

- 19 comprehensive test cases ensuring correct pattern parsing, dispatch, caching, imports, attributes, URL generation, and edge-case handling

**Documentation**

- README.md with quick-start and architecture overview
- Bridges.md detailing all optional integrations and configuration
- Caching.md covering cache setup, invalidation, and short-key configuration
- Importing.md for route import syntax and formats
- Patterns.md documenting URL syntax, parameter binding, and target types
- API reference and examples throughout
