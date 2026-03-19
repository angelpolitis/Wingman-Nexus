<?php
    /**
     * Project Name:    Wingman Nexus - URI
     * Created by:      Angel Politis
     * Creation Date:   Nov 06 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * Represents a URI (Uniform Resource Identifier) with all its components being typed as strings to support
     * routing parameters.
     * `scheme:[//[user:password@]host[:port]]path[?query][#fragment]`
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class URI {
        /**
         * The scheme of a URI.
         * @var string
         */
        public readonly ?string $scheme;

        /**
         * The username of a URI.
         * @var string|null
         */
        public readonly ?string $username;

        /**
         * The password of a URI.
         * @var string|null
         */
        public readonly ?string $password;

        /**
         * The host of a URI.
         * @var string|null
         */
        public readonly ?string $host;
        
        /**
         * The port of a URI.
         * @var string|null
         */
        public readonly ?string $port;
        
        /**
         * The path of a URI.
         * @var string|null
         */
        public readonly ?string $path;
        
        /**
         * The query of a URI.
         * @var string|null
         */
        public readonly ?string $query;

        /**
         * The fragment of a URI.
         * @var string|null
         */
        public readonly ?string $fragment;

        /**
         * Creates a new URI.
         * @param ?string $scheme The scheme.
         * @param ?string $host The host.
         * @param ?string $path The path.
         * @param ?string $port The port.
         * @param ?string $query The query.
         * @param ?string $fragment The fragment.
         * @param ?string $username The username.
         * @param ?string $password The password.
         */
        public function __construct (
            ?string $scheme = null,
            ?string $host = null,
            ?string $path = null,
            ?string $query = null,
            ?string $port = null,
            ?string $fragment = null,
            ?string $username = null,
            ?string $password = null,
        ) {
            $this->scheme = $scheme ? strtolower($scheme) : null;
            $this->host = $host ? strtolower($host) : null;
            $this->path = $path ?: null;
            $this->query = $query ?: null;
            $this->port = $port ?: null;
            $this->fragment = $fragment ?: null;
            $this->username = $username ?: null;
            $this->password = $password ?: null;
        }

        /**
         * Gets a full URI as a string.
         * @return string The full URI as a string.
         */
        public function __toString () : string {
            $uri = "";

            if ($this->scheme !== null && $this->scheme !== "") {
                $uri .= $this->scheme . ':';
            }

            $authority = $this->getAuthority();
            if ($authority !== null && $authority !== "") {
                $uri .= "//" . $authority;
            }

            $path = $this->path ?? "";
            if ($authority !== null && $authority !== "" && $path !== "") {
                if (strpos($path, '/') !== 0) {
                    $path = '/' . $path;
                }
            }
            
            $uri .= $path;

            if ($this->query !== null && $this->query !== "") {
                $uri .= '?' . ltrim($this->query, '?');
            }

            if ($this->fragment !== null && $this->fragment !== "") {
                $uri .= '#' . ltrim($this->fragment, '#');
            }

            return $uri;
        }

        /**
         * Builds a URI from a string.
         * @param string $uri A URI.
         * @return static The URI.
         */
        public static function from (string $uri) : static {
            $parts = parse_url($uri);
            return new static(
                $parts["scheme"] ?? null,
                $parts["host"] ?? null,
                $parts["path"] ?? null,
                $parts["query"] ?? null,
                $parts["port"] ?? null,
                $parts["fragment"] ?? null,
                $parts["user"] ?? null,
                $parts["pass"] ?? null
            );
        }

        /**
         * Gets all components of a URI as an associative array.
         * @return array The components of a URI.
         */
        public function getArray () : array {
            return [
                "scheme" => $this->scheme,
                "username" => $this->username,
                "password" => $this->password,
                "host" => $this->host,
                "port" => $this->port,
                "path" => $this->path,
                "query" => $this->query,
                "fragment" => $this->fragment
            ];
        }

        /**
         * Gets the authority portion: `[userInfo@]host[:port]` of a URI.
         * @return string|null The authority of a URI.
         */
        public function getAuthority () : ?string {
            $authority = $this->host;
            if ($this->username && $this->password) {
                $authority = $this->username . ':' . $this->password . '@' . $authority;
            }
            if ($this->port !== null) {
                $authority .= ':' . $this->port;
            }
            return $authority;
        }

        /**
         * Gets the fragment of a URI.
         * @return string|null The fragment of a URI.
         */
        public function getFragment () : ?string {
            return $this->fragment;
        }

        /**
         * Gets the host of a URI.
         * @return string|null The host of a URI.
         */
        public function getHost () : ?string {
            return $this->host;
        }

        /**
         * Gets the password of a URI.
         * @return string|null The password of a URI.
         */
        public function getPassword () : ?string {
            return $this->password;
        }

        /**
         * Gets the path of a URI.
         * @return string|null The path of a URI.
         */
        public function getPath () : ?string {
            return $this->path;
        }

        /**
         * Gets the path and query of a URI.
         * @return string The path and query of a URI.
         */
        public function getPathWithQuery () : string {
            return $this->path . ($this->query !== "" ? '?' . $this->query : "");
        }

        /**
         * Gets the port of a URI.
         * @return string|null The port of a URI.
         */
        public function getPort () : ?string {
            return $this->port;
        }

        /**
         * Gets the query of a URI.
         * @return string|null The query of a URI.
         */
        public function getQuery () : ?string {
            return $this->query;
        }

        /**
         * Gets the scheme of a URI.
         * @return string|null The scheme of a URI.
         */
        public function getScheme () : ?string {
            return $this->scheme;
        }

        /**
         * Gets the username of a URI.
         * @return string|null The username of a URI.
         */
        public function getUsername () : ?string {
            return $this->username;
        }

        /**
         * Creates a new instance of a URI with a modified query to include a specified parameter.
         * @param string $parameter The parameter key.
         * @param mixed $value The parameter value.
         * @return static The URI.
         */
        public function withParam (string $parameter, mixed $value) : static {
            $params = [];
            parse_str($this->query, $params);
            $params[$parameter] = $value;
            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI with multiple modified query parameters.
         * @param array $map Key-value parameter map.
         * @return static The URI.
         */
        public function withParams (array $map) : static {
            $params = [];
            parse_str($this->query, $params);
            foreach ($map as $key => $value) {
                $params[$key] = $value;
            }
            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI without the given parameter.
         * @param string $parameter The parameter to remove.
         * @return static The URI.
         */
        public function withoutParam (string $parameter) : static {
            $params = [];
            parse_str($this->query, $params);
            unset($params[$parameter]);
            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI without the given parameters.
         * @param string $parameter The first parameter to remove.
         * @param string ...$parameters Additional parameters to remove.
         * @return static The URI.
         */
        public function withoutParams (string $parameter, string ...$parameters) : static {
            $params = [];
            parse_str($this->query, $params);
            unset($params[$parameter]);
            foreach ($parameters as $p) {
                unset($params[$p]);
            }
            return $this->withQuery(http_build_query($params));
        }

        /**
         * Creates a new instance of a URI with a modified host.
         * @param string $host The host.
         * @return static The URI.
         */
        public function withHost (string $host) : static {
            return new static($this->scheme, $host, $this->path, $this->query, $this->port, $this->fragment, $this->username, $this->password);
        }

        /**
         * Creates a new instance of a URI with a modified path.
         * @param string $path The path.
         * @return static The URI.
         */
        public function withPath (string $path) : static {
            return new static($this->scheme, $this->host, $path, $this->query, $this->port, $this->fragment, $this->username, $this->password);
        }

        /**
         * Creates a new instance of a URI with a modified query.
         * @param string $query The query.
         * @return static The URI.
         */
        public function withQuery (string $query) : static {
            return new static($this->scheme, $this->host, $this->path, $query, $this->port, $this->fragment, $this->username, $this->password);
        }

        /**
         * Creates a new instance of a URI with a modified fragment.
         * @param string $fragment The fragment.
         * @return static The URI.
         */
        public function withFragment (?string $fragment) : static {
            return new static($this->scheme, $this->host, $this->path, $this->query, $this->port, $fragment, $this->username, $this->password);
        }

        /**
         * Creates a new instance of a URI with a modified scheme.
         * @param string $scheme The scheme.
         * @return static The URI.
         */
        public function withScheme (string $scheme) : static {
            return new static($scheme, $this->host, $this->path, $this->query, $this->port, $this->fragment, $this->username, $this->password);
        }
    }
?>