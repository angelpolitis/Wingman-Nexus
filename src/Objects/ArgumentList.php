<?php
    /**
     * Project Name:    Wingman Nexus - Argument List
     * Created by:      Angel Politis
     * Creation Date:   Feb 03 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Objects namespace.
    namespace Wingman\Nexus\Objects;

    /**
     * Represents a list of argument sets for different URL components.
     * @package Wingman\Nexus\Objects
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class ArgumentList {
        /**
         * The argument set for the scheme component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $scheme;

        /**
         * The argument set for the username component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $username;
        
        /**
         * The argument set for the password component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $password;

        /**
         * The argument set for the host component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $host;

        /**
         * The argument set for the port component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $port;

        /**
         * The argument set for the path component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $path;

        /**
         * The argument set for the query component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $query;

        /**
         * The argument set for the fragment component.
         * @var ArgumentSet
         */
        public readonly ArgumentSet $fragment;
    
        /**
         * Creates a new argument list.
         * @param array $sets An associative array of ArgumentSet objects for each URL component.
         */
        public function __construct (array $sets = []) {
            $this->scheme = $sets["scheme"] ?? new ArgumentSet();
            $this->username = $sets["username"] ?? new ArgumentSet();
            $this->password = $sets["password"] ?? new ArgumentSet();
            $this->host = $sets["host"] ?? new ArgumentSet();
            $this->port = $sets["port"] ?? new ArgumentSet();
            $this->path = $sets["path"] ?? new ArgumentSet();
            $this->query = $sets["query"] ?? new ArgumentSet();
            $this->fragment = $sets["fragment"] ?? new ArgumentSet();
        }
    
        /**
         * Aggregates all named parameters into one array.
         * @return array The aggregated named parameters.
         */
        public function getAllNamed () : array {
            return array_merge(
                $this->scheme->named,
                $this->username->named,
                $this->password->named,
                $this->host->named,
                $this->port->named,
                $this->path->named,
                $this->query->named,
                $this->fragment->named
            );
        }

        /**
         * Aggregates all indexed parameters into one array.
         * @return array The aggregated indexed parameters.
         */
        public function getAllIndexed () : array {
            return array_merge(
                $this->scheme->indexed,
                $this->username->indexed,
                $this->password->indexed,
                $this->host->indexed,
                $this->port->indexed,
                $this->path->indexed,
                $this->query->indexed,
                $this->fragment->indexed
            );
        }

        /**
         * Aggregates all unnamed parameters into one array.
         * @return array The aggregated unnamed parameters.
         */
        public function getAllUnnamed () : array {
            return array_merge(
                $this->scheme->unnamed,
                $this->username->unnamed,
                $this->password->unnamed,
                $this->host->unnamed,
                $this->port->unnamed,
                $this->path->unnamed,
                $this->query->unnamed,
                $this->fragment->unnamed
            );
        }

        /**
         * Gets the argument set for the fragment component.
         * @return ArgumentSet The argument set for the fragment component.
         */
        public function getFragmentSet () : ArgumentSet {
            return $this->fragment;
        }

        /**
         * Gets the argument set for the host component.
         * @return ArgumentSet The argument set for the host component.
         */
        public function getHostSet () : ArgumentSet {
            return $this->host;
        }

        /**
         * Gets the argument set for the password component.
         * @return ArgumentSet The argument set for the password component.
         */
        public function getPasswordSet () : ArgumentSet {
            return $this->password;
        }

        /**
         * Gets the argument set for the path component.
         * @return ArgumentSet The argument set for the path component.
         */
        public function getPathSet () : ArgumentSet {
            return $this->path;
        }

        /**
         * Gets the argument set for the port component.
         * @return ArgumentSet The argument set for the port component.
         */
        public function getPortSet () : ArgumentSet {
            return $this->port;
        }

        /**
         * Gets the argument set for the query component.
         * @return ArgumentSet The argument set for the query component.
         */
        public function getQuerySet () : ArgumentSet {
            return $this->query;
        }

        /**
         * Gets the argument set for the scheme component.
         * @return ArgumentSet The argument set for the scheme component.
         */
        public function getSchemeSet () : ArgumentSet {
            return $this->scheme;
        }

        /**
         * Gets the argument set for the username component.
         * @return ArgumentSet The argument set for the username component.
         */
        public function getUsernameSet () : ArgumentSet {
            return $this->username;
        }
    }
?>