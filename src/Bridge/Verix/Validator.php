<?php
    /**
     * Project Name:    Wingman Nexus - Verix Bridge - Validator
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Bridge.Verix namespace.
    namespace Wingman\Nexus\Bridge\Verix;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\SchemaValidationException;
    use Wingman\Nexus\Exceptions\VerixNotInstalledException;
    use Wingman\Verix\Facades\Schema;

    /**
     * Bridges Nexus's parameter type system with the Wingman Verix validation package.
     *
     * When Verix is installed, route parameters whose type expression is a Verix schema are
     * matched against a permissive regex during compilation and validated against the schema
     * after a route match occurs (via {@see Resolver::validateSchemaParameters()}) and before
     * a URL is generated (via {@see UrlGenerator::validateParameter()}).
     *
     * A type expression is considered a Verix schema if it contains any character that cannot
     * appear in a plain PHP class name or built-in type alias: `<`, `>`, `{`, `}`, `[`, `]`,
     * or begins with `@` (a named schema reference registered via {@see Schema::register()}).
     * Plain aliases such as `int`, `email`, or `string` are resolved entirely by
     * {@see TypeRegistry} and are never routed through this class.
     *
     * @package Wingman\Nexus\Bridge\Verix
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Validator {
        /**
         * The permissive regex returned by {@see resolveRegex()} and substituted during route
         * compilation for Verix-typed parameters. It matches any non-slash sequence, allowing
         * the regex engine to capture the value while deferring real type-checking to Verix
         * post-match.
         * @var string
         */
        public const PERMISSIVE_REGEX = "[^\\/]*";

        /**
         * Determines whether the given type expression is a Verix schema.
         * Returns true if the expression contains characters that cannot appear in a plain
         * PHP type alias or class name, or starts with `@` (a named schema reference).
         * @param string $type The type expression to inspect.
         * @return bool Whether the expression is a Verix schema.
         */
        public static function isSchemaExpression (string $type) : bool {
            return (bool) preg_match('/[<>{}\[\]@]/', $type);
        }

        /**
         * Returns the permissive regex used during route compilation for Verix-typed parameters.
         * Since Verix schemas cannot in general be expressed as a single regex, this pattern
         * matches any non-slash character sequence and defers the real type-check to post-match
         * validation via {@see validate()}.
         * @return string A permissive regex pattern (without delimiters or anchors).
         */
        public static function resolveRegex () : string {
            return self::PERMISSIVE_REGEX;
        }

        /**
         * Validates a route parameter value against a Verix schema expression.
         * Throws a {@see VerixNotInstalledException} if Verix is not installed.
         * Throws a {@see SchemaValidationException} if the value does not satisfy the schema.
         * @param mixed $value The captured parameter value to validate.
         * @param string $schema The Verix schema expression.
         * @param string $parameter The parameter name (used in error messages).
         * @throws VerixNotInstalledException If the Wingman Verix package is not installed.
         * @throws SchemaValidationException If the value does not satisfy the schema.
         */
        public static function validate (mixed $value, string $schema, string $parameter) : void {
            if (!class_exists(Schema::class)) {
                throw new VerixNotInstalledException(
                    "The parameter '{$parameter}' uses the Verix schema '{$schema}' but the Wingman " .
                    "Verix package is not installed. Install Verix to use schema-based parameter " .
                    "type enforcement."
                );
            }

            $result = (new Schema($schema))->validate($value);

            if ($result->valid) return;

            $messages = array_map(fn ($e) => $e->getMessage(), $result->errors);
            $detail = implode("; ", $messages);

            throw new SchemaValidationException(
                "The parameter '{$parameter}' does not satisfy the schema '{$schema}': {$detail}"
            );
        }
    }
?>