<?php
    /**
     * Project Name:    Wingman Nexus - Cacher
     * Created by:      Angel Politis
     * Creation Date:   Nov 11 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Caching namespace.
    namespace Wingman\Nexus\Caching;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\CacheDirectoryException;
    use Wingman\Nexus\Exceptions\CacheFileNotFoundException;
    use Wingman\Nexus\Exceptions\CacheFileReadException;
    use Wingman\Nexus\Exceptions\CachePathEscapeException;
    use Wingman\Nexus\Exceptions\CacheWriteException;
    use Wingman\Nexus\Exceptions\InvalidCacheFileException;

    /**
     * A class responsible for caching.
     * @package Wingman\Nexus\Caching
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class Cacher {
        /**
         * The root directory of a cacher.
         * @var string
         */
        protected string $rootDir = '/';

        /**
         * The permission used by a cacher to create files.
         * @var string
         */
        protected int $permission = 0755;

        /**
         * The hasing algorithm used by a cacher to create files.
         * @var string
         */
        protected string $hashingAlgorithm = "sha256";

        /**
         * The time-to-live in seconds of a cache file created by a cacher.
         * @var int
         */
        protected int $ttl = 86400;

        /**
         * Creates a new cacher.
         * @param string|null $rootDir The root directory of the cacher.
         * @param int|null $permission The permission used by the cacher to create files.
         * @param string|null $hashingAlgorithm The hasing algorithm used by the cacher to create files.
         */
        public function __construct (?string $rootDir = null, ?int $permission = null, ?string $hashingAlgorithm = null) {
            if (isset($rootDir)) $this->rootDir = $rootDir;
            if (isset($permission)) $this->permission = $permission;
            if (isset($hashingAlgorithm)) $this->hashingAlgorithm = $hashingAlgorithm;

            $this->rootDir = rtrim(realpath($this->rootDir) ?: $this->rootDir, DIRECTORY_SEPARATOR);
        }

        /**
         * Determines whether a normalised absolute path is within the provided root boundary.
         * @param string $path The path to test.
         * @param string $root The root boundary.
         * @return bool Whether the path is within root.
         */
        protected function isPathWithinRoot (string $path, string $root) : bool {
            if ($path === $root) return true;

            $rootWithSeparator = $root . DIRECTORY_SEPARATOR;

            return str_starts_with($path, $rootWithSeparator);
        }

        /**
         * Resolve a path against the root directory and ensure it doesn't escape it.
         * @param string $path The path.
         * @param bool $strict Whether the resolution will happen in strict mode.
         * @return string The resolved path.
         */
        protected function resolvePath (string $path, bool $strict = true) : string {
            # Determine whether the path is absolute:
            # - Starts with "/" (Unix)
            # - Starts with a drive letter like "C:\" (Windows)
            # - UNC path: "\\server\share"
            $isAbsolute = ($path !== "" && $path[0] === DIRECTORY_SEPARATOR) ||
                preg_match('/^[A-Za-z]:[\/\\\\]/', $path) ||
                str_starts_with($path, '\\\\');

            # Normalise the path, if absolute.
            if ($isAbsolute) $absolute = realpath($path) ?: $path;

            # Bind the relative path to the root.
            else {
                $absolute = $this->rootDir . DIRECTORY_SEPARATOR . $path;
                $absolute = realpath($absolute) ?: $absolute;
            }

            # Normalise the root and path to the same format.
            $root = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir), DIRECTORY_SEPARATOR);
            $absolute = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolute), DIRECTORY_SEPARATOR);

            # Reject the path if it's out of scope in strict mode.
            if ($strict && !$this->isPathWithinRoot($absolute, $root)) {
                if ($path === $absolute) $message = $path;
                else $message = "$path → $absolute";
                throw new CachePathEscapeException("Path '$message' escapes the cacher root: {$this->rootDir}");
            }

            return $absolute;
        }

        /**
         * Dumps a specified file or directory.
         * @param string $path The file or directory.
         * @return static The cacher.
         */
        public function dump (string $path) : static {
            $path = $this->resolvePath($path);

            if (is_file($path)) unlink($path);

            elseif (is_dir($path)) array_map(fn ($f) => $this->dump($f), glob("$path/*"));

            return $this;
        }

        /**
         * Generates a fingerprint for a set of files.
         * @param string|string[] $files The paths to the files.
         * @param string|null $hashingAlgorithm The hashing algorith to use.
         * @return string A SHA-256 hash.
         */
        public function generateFingerprint (string|array $files, ?string $hashingAlgorithm = null) : string {
            $hashingAlgorithm ??= $this->hashingAlgorithm;

            $data = [];

            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                $file = $this->resolvePath($file, false);

                $mtime = filemtime($file) ?: 0;
                $hash = hash_file($hashingAlgorithm, $file) ?: '';
                $data[] = "$file:$mtime:$hash";
            }

            return hash($hashingAlgorithm, implode('|', $data));
        }

        /**
         * Reads a cache file.
         * @param string $file The location of the file.
         * @return Cache The cache file.
         * @throws CacheFileNotFoundException If the file doesn't exist or isn't a file.
         * @throws CacheFileReadException If the file cannot be read.
         * @throws InvalidCacheFileException If the file isn't a valid cache file.
         */
        public function read (string $file) : Cache {
            $file = $this->resolvePath($file);

            if (!file_exists($file)) {
                throw new CacheFileNotFoundException("Failed to locate file: $file");
            }

            if (!is_file($file)) {
                throw new CacheFileNotFoundException("Path doesn't point to a file: $file");
            }

            $contents = file_get_contents($file);

            if ($contents === false) {
                throw new CacheFileReadException("Failed to read cache file: $file");
            }

            $cache = @unserialize($contents, ["allowed_classes" => [Cache::class]]);

            if (!($cache instanceof Cache)) {
                throw new InvalidCacheFileException("The file '$file' isn't a cache file.");
            }

            return $cache;
        }

        /**
         * Checks whether a cache file exists.
         * @param string $path The path relative to the cache root.
         * @return bool Whether the file exists.
         */
        public function exists (string $path) : bool {
            $path = $this->resolvePath($path);
            return file_exists($path);
        }

        /**
         * Writes content into a file.
         * @param string $file The file.
         * @param mixed $content The content.
         * @param array $metadata The metadata to include in the cache.
         * @param int The duration in seconds for which the file will be considered valid.
         * @param int The permission of the file.
         * @return static The cacher.
         * @throws CacheDirectoryException If a directory cannot be created.
         * @throws CacheWriteException If a file cannot be written or atomically moved into place.
         */
        public function write (string $file, mixed $content, array $metadata = [], int $ttl = 3600, int $permission = 0755) : static {
            $file = $this->resolvePath($file);

            $dir = dirname($file);

            # Create the directory recursively if it doesn't exist.
            if (!is_dir($dir)) {
                if (!mkdir($dir, $permission, true) && !is_dir($dir)) {
                    throw new CacheDirectoryException("Failed to create cache directory: $dir");
                }
            }

            $cache = new Cache($file, $content, $ttl, $metadata);

            $tempFile = $file . '.' . uniqid("tmp", true);

            $result = file_put_contents($tempFile, serialize($cache), LOCK_EX);

            if (!$result) {
                @unlink($tempFile);
                throw new CacheWriteException("Failed to write cache file: $file");
            }

            if (!rename($tempFile, $file)) {
                @unlink($tempFile);
                throw new CacheWriteException("Failed to atomically write cache file: $file");
            }

            return $this;
        }
    }
?>