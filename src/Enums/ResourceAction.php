<?php
    /**
     * Project Name:    Wingman Nexus - Resource Action
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
     * Represents a conventional RESTful action used in resource route sets.
     *
     * Each case corresponds to one of the seven standard CRUD actions and carries the
     * HTTP method(s) that REST convention assigns to it. Used by {@see \Wingman\Nexus\Router::addResource()}
     * to generate route entries without hardcoded action-name or method strings.
     * @package Wingman\Nexus\Enums
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    enum ResourceAction : string {
        /**
         * The create action.
         * Serves the form for creating a new resource. Maps to `GET /{base}/create`.
         * @var string
         */
        case CREATE = "create";

        /**
         * The destroy action.
         * Deletes an existing resource. Maps to `DELETE /{base}/{id}`.
         * @var string
         */
        case DESTROY = "destroy";

        /**
         * The edit action.
         * Serves the form for editing an existing resource. Maps to `GET /{base}/{id}/edit`.
         * @var string
         */
        case EDIT = "edit";

        /**
         * The index action.
         * Lists all resources. Maps to `GET /{base}`.
         * @var string
         */
        case INDEX = "index";

        /**
         * The show action.
         * Displays a single resource. Maps to `GET /{base}/{id}`.
         * @var string
         */
        case SHOW = "show";

        /**
         * The store action.
         * Persists a new resource. Maps to `POST /{base}`.
         * @var string
         */
        case STORE = "store";

        /**
         * The update action.
         * Replaces or partially updates an existing resource. Maps to `PUT` and `PATCH /{base}/{id}`.
         * @var string
         */
        case UPDATE = "update";

        /**
         * Returns the HTTP method values associated with this action by REST convention.
         * @return string[] The HTTP method values.
         */
        public function getHttpMethods () : array {
            return match ($this) {
                self::CREATE => [HttpMethod::GET->value],
                self::DESTROY => [HttpMethod::DELETE->value],
                self::EDIT => [HttpMethod::GET->value],
                self::INDEX => [HttpMethod::GET->value],
                self::SHOW => [HttpMethod::GET->value],
                self::STORE => [HttpMethod::POST->value],
                self::UPDATE => [HttpMethod::PATCH->value, HttpMethod::PUT->value],
            };
        }

        /**
         * Resolves a resource action from a string or returns the existing instance.
         * @param static|string $action The action to resolve.
         * @return static The resolved instance.
         */
        public static function resolve (self|string $action) : static {
            return $action instanceof static ? $action : static::from(strtolower($action));
        }
    }
?>