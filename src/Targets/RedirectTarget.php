<?php
    /**
     * Project Name:    Wingman Nexus - Redirect Target
     * Created by:      Angel Politis
     * Creation Date:   Nov 22 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Targets namespace.
    namespace Wingman\Nexus\Targets;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Interfaces\Target;

    /**
     * Represents a redirect target.
     * @package Wingman\Nexus\Targets
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RedirectTarget implements Target {
        /**
         * The status of a redirect target.
         * @var int|null
         */
        public readonly ?int $status;

        /**
         * The status of a redirect target.
         * @var string
         */
        public readonly string $path;

        /**
         * The headers of a redirect target.
         * @var array<string, string>
         */
        public readonly array $headers;

        /**
         * Whether a redirect preserves the original query.
         * @var bool
         */
        public readonly bool $preservesQuery;

        /**
         * The URL of a redirect target.
         * @var ?string
         */
        public readonly ?string $url;

        /**
         * Creates a new redirect target.
         * @param string $path The path of a redirect target.
         * @param int|null $status The status of a redirect target.
         * @param array<string, string> $headers The headers of a redirect target.
         * @param bool $preservesQuery Whether the redirect should preserve the original query.
         */
        public function __construct (string $path, ?int $status = null, array $headers = [], bool $preservesQuery = true) {
            $this->path = $path;
            $this->status ??= $status;
            $this->headers = $headers;
            $this->preservesQuery = $preservesQuery;
        }

        /**
         * Serialises a redirect target.
         * @return array The serialised target.
         */
        public function __serialize () : array {
            return $this->getArray();
        }

        /**
         * Unserialises a redirect target.
         * @param array $data The data.
         */
        public function __unserialize (array $data) : void {
            $this->path = $data["path"];
            $this->status = $data["status"];
            $this->headers = $data["headers"];
            $this->preservesQuery = $data["preservesQuery"];
        }

        /**
         * Creates a new redirect target (for var_export).
         * @param array $properties The properties used to create a new redirect target.
         */
        public static function __set_state (array $properties) : static {
            return new static($properties["path"], $properties["status"], $properties["headers"], $properties["preservesQuery"]);
        }

        /**
         * Creates a new redirect target.
         * @param string $target The target of the redirect.
         * @param int $status The target of the redirect.
         * @param array $headers The headers of the redirect.
         * @param bool $preservesQuery Whether the redirect should preserve the original query.
         * @return static The new redirect.
         */
        public static function from (string $target, ?int $status = null, array $headers = [], bool $preservesQuery = true) : static {
            return new static($target, $status, $headers, $preservesQuery);
        }

        /**
         * Gets a redirect definition as an array.
         * @return array The information of a redirect definition as an array.
         */
        public function getArray () : array {
            return [
                "path" => $this->path,
                "status" => $this->status,
                "headers" => $this->headers,
                "preservesQuery" => $this->preservesQuery
            ];
        }

        /**
         * Gets the headers of a redirect target.
         * @return array The headers of the redirect target.
         */
        public function getHeaders () : array {
            return $this->headers;
        }

        /**
         * Gets the path of a redirect target.
         * @return string The path of the redirect target.
         */
        public function getPath () : string {
            return $this->path;
        }

        /**
         * Gets the status of a redirect target.
         * @return int|null The status of the redirect target.
         */
        public function getStatus () : ?int {
            return $this->status;
        }

        /**
         * Gets the URL of a redirect target.
         * @return string|null The URL of the redirect target.
         */
        public function getUrl () : ?string {
            return $this->url;
        }

        /**
         * Gets whether a redirect should preserve the original query.
         * @return bool Whether the redirect should preserve the original query.
         */
        public function preservesQuery () : bool {
            return $this->preservesQuery;
        }

        /**
         * Creates a new target with a specified headers.
         * @param array $headers The headers.
         * @return static The redirect.
         */
        public function withHeaders (array $headers) : static {
            return new static($this->path, $this->status, $headers, $this->preservesQuery);
        }

        /**
         * Creates a new target with the specified status.
         * @param int $status The status.
         * @return static The redirect.
         */
        public function withStatus (int $status) : static {
            return new static($this->path, $status, $this->headers, $this->preservesQuery);
        }

        /**
         * Creates a new target with the specified URL.
         * @param string $url The URL.
         * @return static The redirect.
         */
        public function withUrl (string $url) : static {
            $target = clone $this;
            $target->url = $url;
            return $target;
        }
    }
?>