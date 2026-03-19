<?php
    /**
     * Project Name:    Wingman Nexus - Type Registry
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Bridge\Verix\Validator;

    /**
     * Represents a type registry.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class TypeRegistry {
        /**
         * The default variable type name.
         * @var string
         */
        public const DEFAULT_TYPE = "string";

        /**
         * The built-in variable data-type definitions used when no custom registry is provided.
         * @var array<string, string>
         */
        public const DEFAULT_TYPES = [
            "bit"      => "0|1",
            "bool"     => "true|false",
            "date"     => "(((((1[26]|2[048])00)|[12]\\d([2468][048]|[13579][26]|0[48]))-((((0[13578]|1[02])-(0[1-9]|[12]\\d|3[01]))|((0[469]|11)-(0[1-9]|[12]\\d|30)))|(02-(0[1-9]|[12]\\d))))|((([12]\\d([02468][1235679]|[13579][01345789]))|((1[1345789]|2[1235679])00))-((((0[13578]|1[02])-(0[1-9]|[12]\\d|3[01]))|((0[469]|11)-(0[1-9]|[12]\\d|30)))|(02-(0[1-9]|1\\d|2[0-8]))))))",
            "duration" => "(?:\\d+):[0-5]\\d(?::[0-5]\\d(?:\\.\\d+)?)?",
            "email"    => "(?:[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+(?:\\.[a-z0-9!#$%&'*+\\/=?^_`{|}~-]+)*|\"(?:[\x{0001}-\b\x{000b}\f\x{000e}-\x{001f}!#-[]-\x{007f}]|\\\\[\x{0001}-\t\x{000b}\f\x{000e}-\x{007f}])*\")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\\\\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x{0001}-\b\x{000b}\f\x{000e}-\x{001f}!-ZS-\x{007f}]|\\\\[\x{0001}-\t\x{000b}\f\x{000e}-\x{007f}])+)\\\\])",
            "float"    => "-?(?:\\d+\\.\\d*|\\.\\d+|\\d+)",
            "int"      => "-?(?:0|[1-9][0-9]*)",
            "nFloat"   => "-(?:\\.[1-9][0-9]*|[1-9]\\.[0-9]+)",
            "nFloat+"  => "0|\\.0|-[0-9]*\\.[0-9]+",
            "nInt"     => "-[1-9][0-9]*",
            "nInt+"    => "0|{nInt}",
            "number"   => "-?(?:0|[1-9][0-9]*)(?:\\.[0-9]+)?",
            "pFloat"   => "\\.[1-9][0-9]*|[1-9]\\.[0-9]+",
            "pFloat+"  => "[0-9]*\\.[0-9]+",
            "pInt"     => "[1-9][0-9]*",
            "pInt+"    => "0|[1-9][0-9]*",
            "time"     => "{time12}|{time24}",
            "time12"   => "(?:0\\d|1[0-2]):[0-5]\\d:[0-5]\\d(?:\\.\\d+)?\\s+(?:a|p)m",
            "time24"   => "(?:[01]\\d|2[0-3]):[0-5]\\d:[0-5]\\d(?:\\.\\d+)?",
            "uFloat"   => "{pFloat+}",
            "uInt"     => "{pInt+}",
            "string"   => "[^\\/]*",
            "string+"  => ".*",
        ];

        /**
         * The types supported by a registry.
         * @var iterable
         */
        protected iterable $types;

        /**
         * The default type.
         * @var string
         */
        protected readonly string $defaultType;

        /**
         * Creates a new type registry.
         * @param iterable $types The types (`name` => `regex`).
         * @param string $defaultType The default type.
         */
        public function __construct (iterable $types = self::DEFAULT_TYPES, string $defaultType = self::DEFAULT_TYPE) {
            $this->types = $types;
            $this->defaultType = $defaultType;
        }

        /**
         * Expands a type in case it contains other types.
         * @param string $type The type.
         * @return string The expanded type.
         */
        protected function expand (string $type) : string {
            # If the type is not known, fallback to the default type.
            $pattern = $this->types[$type] ?? $this->types[$this->defaultType];
    
            # Expand the {alias} references recursively.
            $pattern = preg_replace_callback('/\{([^}]+)\}/', function ($m) {
                return isset($this->types[$m[1]]) ? $this->expand($m[1]) : $m[0];
            }, $pattern);

            # Convert Unicode escape sequences to hexadecimal escape sequences.
            $pattern = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($m) {
                return '\x{' . $m[1] . '}';
            }, $pattern);

            # Cache the expanded type for future use.
            $this->types[$type] = $pattern;

            return $pattern;
        }
    
        /**
         * Resolves a type.
         * @param string $type The type.
         * @return string The resolved type.
         */
        public function resolve (string $type) : string {
            if (Validator::isSchemaExpression($type)) return Validator::resolveRegex();
            return $this->expand($type);
        }
    }
?>