<?php
    /**
     * Project Name:    Wingman Nexus - Cache
     * Created by:      Angel Politis
     * Creation Date:   Nov 24 2025
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Caching namespace.
    namespace Wingman\Nexus\Caching;

    # Import the following classes to the current scope.
    use DateTimeImmutable;
    use DateTimeInterface;

    /**
     * Represents a cache file.
     * @package Wingman\Nexus\Caching
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Cache {
        /**
         * The location of a cache file.
         * @var string
         */
        protected string $location;

        /**
         * The content of a cache file.
         * @var mixed
         */
        protected mixed $content;

        /**
         * The metadata of a cache file.
         * @var array
         */
        protected array $metadata;

        /**
         * The time-to-live in seconds of a cache file.
         * @var int
         */
        protected int $ttl;

        /**
         * The creation date of a cache file.
         * @var DateTimeInterface
         */
        protected DateTimeInterface $creationDate;

        /**
         * Creates a new cache file representation.
         * @param string $location The location of the file.
         * @param mixed $content The content of the file.
         * @param int $ttl The time-to-live of the file in seconds.
         * @param DateTimeInterface|null The creation date of the file; the current date will be used, if `null`.
         */
        public function __construct (string $location, mixed $content, int $ttl, array $metadata = [], ?DateTimeInterface $creationDate = null) {
            $this->location = $location;
            $this->content = $content;
            $this->ttl = $ttl;
            $this->metadata = $metadata;
            $this->creationDate = $creationDate ?? new DateTimeImmutable();
        }

        /**
         * Gets the content of a cache file.
         * @return mixed The content.
         */
        public function getContent () : mixed {
            return $this->content;
        }

        /**
         * Gets the creation date of a cache file.
         * @return DateTimeInterface The creation date.
         */
        public function getCreationDate () : DateTimeInterface {
            return $this->creationDate;
        }

        /**
         * Gets the location of a cache file.
         * @return string The location.
         */
        public function getLocation () : string {
            return $this->location;
        }

        /**
         * Gets the metadata of a cache file.
         * @return array The metadata.
         */
        public function getMetadata () : array {
            return $this->metadata;
        }

        /**
         * Gets the time-to-live in seconds of a cache file.
         * @return int The time-to-live.
         */
        public function getTTL () : int {
            return $this->ttl;
        }

        /**
         * Gets whether a cache file is fresh.
         * @param DateTimeInterface|string|null A date to use as a reference point.
         * @return bool Whether the file is fresh.
         */
        public function isFresh (DateTimeInterface|string|null $date = null) : bool {
            $date = $date instanceof DateTimeInterface ? $date : new DateTimeImmutable($date ?? "now");
            $age = $date->getTimestamp() - $this->creationDate->getTimestamp();
            return $age < $this->ttl;
        }
    }
?>