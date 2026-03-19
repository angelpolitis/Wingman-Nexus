<?php
    /**
     * Project Name:    Wingman Nexus - Helper
     * Created by:      Angel Politis
     * Creation Date:   Nov 22 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use JsonException;

    /**
     * Represents a wrapper of a utility, static methods.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    final class Helper {
        /**
         * Coerces a value automatically to the most appropriate PHP type.
         * @param mixed $value The value.
         * @return mixed The adjusted value.
         */
        public static function coerce (mixed $value) : mixed {
            if (!is_string($value)) return $value;
            
            $value = urldecode($value);

            try {
                $decodedPart = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if ($decodedPart === null) return $value;
                return !is_scalar($decodedPart) || strval($decodedPart) === $value ? $decodedPart : $value;
            }
            catch (JsonException $e) {
                return $value;
            }
        }

        /**
         * Check if a value is an indexed array. An indexed array is an array where the keys are sequential numeric values starting from 0.
         * @param mixed $array The value to check.
         * @return bool Returns true if the array is empty or if its keys form a full, uninterrupted numeric sequence
         *              from 0 to its length - 1. Otherwise, returns false.
         */
        public static function isIndexedArray (mixed $array) : bool {
            if (!is_array($array)) return false;

            if (function_exists("array_is_list")) return array_is_list($array);

            # Return whether the array is empty or the its keys are a full, uninterrupted numeric sequence from 0 to its length - 1.
            return empty($array) || array_keys($array) === range(0, sizeof($array) - 1);
        }

        /**
         * Normalises an HTTP method.
         * @param string $method An HTTP method.
         * @return string The normalised method.
         */
        public static function normaliseMethod (string $method) : string {
            return strtoupper($method);
        }

        /**
         * Normalises a URL.
         * @param string $url A URL.
         * @return string The normalised url.
         */
        public static function normaliseUrl (string $url) : string {
            return trim($url, '/');
        }
    }
?>