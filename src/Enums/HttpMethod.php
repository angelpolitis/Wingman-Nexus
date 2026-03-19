<?php
    /**
     * Project Name:    Wingman Nexus - HTTP Method
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     *
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Enums namespace.
    namespace Wingman\Nexus\Enums;

    /**
     * Represents a standard HTTP method as defined in RFC 7231 and RFC 5789.
     *
     * Backed by the canonical uppercase string value of each method so that enum cases
     * can be compared directly against normalised method strings throughout the router.
     * @package Wingman\Nexus\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum HttpMethod : string {
        /**
         * The CONNECT method.
         * Establishes a tunnel to the server identified by the target resource, typically used for
         * HTTPS connections through an HTTP proxy.
         * @var string
         */
        case CONNECT = "CONNECT";

        /**
         * The DELETE method.
         * Requests that the server delete the resource identified by the request URI.
         * @var string
         */
        case DELETE = "DELETE";

        /**
         * The GET method.
         * Requests a representation of the specified resource. Requests using GET should only retrieve data.
         * @var string
         */
        case GET = "GET";

        /**
         * The HEAD method.
         * Identical to GET but the server does not send the response body. Used to retrieve metadata.
         * @var string
         */
        case HEAD = "HEAD";

        /**
         * The OPTIONS method.
         * Describes the communication options for the target resource, used to determine allowed methods.
         * @var string
         */
        case OPTIONS = "OPTIONS";

        /**
         * The PATCH method.
         * Applies partial modifications to a resource, as opposed to PUT which replaces it entirely.
         * @var string
         */
        case PATCH = "PATCH";

        /**
         * The POST method.
         * Submits an entity to the specified resource, often causing a change in state or side effects.
         * @var string
         */
        case POST = "POST";

        /**
         * The PUT method.
         * Replaces all current representations of the target resource with the request payload.
         * @var string
         */
        case PUT = "PUT";

        /**
         * The TRACE method.
         * Performs a message loop-back test along the path to the target resource, used for diagnostics.
         * @var string
         */
        case TRACE = "TRACE";

        /**
         * Gets the string values of all HTTP methods that application routes can handle.
         *
         * Excludes {@see HttpMethod::OPTIONS}, which is resolved automatically by the router
         * and should not appear as a user-registered route method.
         * @return string[] All routable HTTP method values.
         */
        public static function getRoutable () : array {
            return array_column(
                array_filter(self::cases(), fn (self $m) => $m !== self::OPTIONS),
                "value"
            );
        }

        /**
         * Gets the string values of all HTTP methods.
         * @return string[] All HTTP method values.
         */
        public static function getValues () : array {
            return array_column(self::cases(), "value");
        }

        /**
         * Resolves an HTTP method from a string or returns the existing instance.
         * @param static|string $method The method to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|string $method) : static {
            return $method instanceof static ? $method : static::from(strtoupper($method));
        }
    }
?>