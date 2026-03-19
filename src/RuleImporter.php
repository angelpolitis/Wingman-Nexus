<?php
    /**
     * Project Name:    Wingman Nexus - Rule Importer
     * Created by:      Angel Politis
     * Creation Date:   Dec 02 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus namespace.
    namespace Wingman\Nexus;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Bridge\Cortex\Attributes\Configurable;
    use Wingman\Nexus\Bridge\Cortex\Configuration;
    use Wingman\Nexus\Bridge\Corvus\Emitter;
    use Wingman\Nexus\Exceptions\ImportFileNotFoundException;
    use Wingman\Nexus\Exceptions\ImportFileReadException;
    use Wingman\Nexus\Exceptions\ImportPathEscapeException;
    use Wingman\Nexus\Exceptions\InvalidImportContentException;
    use Wingman\Nexus\Exceptions\InvalidImportFormatException;
    use Wingman\Nexus\Exceptions\UnsupportedRuleFileTypeException;
    use Wingman\Nexus\Caching\CacheManager;
    use Wingman\Nexus\Enums\RuleType;
    use Wingman\Nexus\Enums\Signal;
    use Wingman\Nexus\Rules\Redirect;
    use Wingman\Nexus\Rules\Rewrite;
    use Wingman\Nexus\Rules\Route;

    /**
     * Responsible for importing rules.
     * @package Wingman\Nexus
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class RuleImporter {
        /**
         * Maps short-form keys to their full dotted-notation equivalents for importer hydration.
         * @var array<string, string>
         */
        public const array KEY_MAP = [
            "ruleImportRoots" => "nexus.locations.ruleImportRoots",
        ];

        /**
         * The cache manager of a rule importer.
         * @var CacheManager
         */
        protected CacheManager $cacheManager;

        /**
         * The root path used to constrain rule-file imports.
         * @var string
         */
        protected string $projectRoot;

        /**
         * Extra trusted roots from configuration.
         * @var string[]
         */
        #[Configurable("nexus.locations.ruleImportRoots")]
        protected array $trustedImportRoots = [];

        /**
         * The final list of trusted roots used for import confinement.
         * @var string[]
         */
        protected array $allowedImportRoots = [];

        /**
         * Creates a new rule importer.
         * @param CacheManager $cacheManager A cache manager.
         * @param array|Configuration $config The configuration, if any.
         */
        public function __construct (CacheManager $cacheManager, array|Configuration $config = []) {
            $this->cacheManager = $cacheManager;
            Configuration::hydrate($this, $config, static::KEY_MAP);
            $this->projectRoot = rtrim(realpath(dirname(__DIR__)) ?: dirname(__DIR__), DIRECTORY_SEPARATOR);

            $roots = [$this->projectRoot];

            $workingDirectory = getcwd();

            if ($workingDirectory !== false && $workingDirectory !== "") {
                $roots[] = $workingDirectory;
            }

            array_push($roots, ...$this->trustedImportRoots);

            $this->allowedImportRoots = $this->normaliseRoots($roots);
        }

        /**
         * Determines whether a path is located within a root boundary.
         * @param string $path The candidate path.
         * @param string $root The root boundary.
         * @return bool Whether the path is within the root boundary.
         */
        protected function isPathWithinRoot (string $path, string $root) : bool {
            if ($path === $root) return true;

            $rootWithSeparator = $root . DIRECTORY_SEPARATOR;

            return str_starts_with($path, $rootWithSeparator);
        }

        /**
         * Normalises a list of root paths.
         * @param string[] $roots The root paths.
         * @return string[] The normalised roots.
         */
        protected function normaliseRoots (array $roots) : array {
            $normalised = [];

            foreach ($roots as $root) {
                if (!is_string($root) || trim($root) === "") continue;

                $resolved = realpath($root) ?: $root;
                $resolved = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $resolved), DIRECTORY_SEPARATOR);

                if ($resolved === "") continue;

                $normalised[$resolved] = $resolved;
            }

            return array_values($normalised);
        }

        /**
         * Asserts that an import file is located within the trusted root directories.
         * @param string $file The file to verify.
         * @throws ImportPathEscapeException If the file is outside trusted roots.
         */
        protected function assertFileIsWithinTrustedRoots (string $file) : void {
            $absolute = realpath($file) ?: $file;
            $absolute = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolute), DIRECTORY_SEPARATOR);

            foreach ($this->allowedImportRoots as $root) {
                if ($this->isPathWithinRoot($absolute, $root)) return;
            }

            throw new ImportPathEscapeException("Rule import file '$file' is outside trusted import roots.");
        }

        /**
         * Imports a JSON file.
         * @param string $file The file to import.
         * @return array The imported data.
         * @throws ImportFileReadException If the file cannot be read.
         * @throws InvalidImportFormatException If the JSON cannot be decoded.
         * @throws InvalidImportContentException If the decoded value is not an array.
         */
        protected function importJSON (string $file, RuleType $ruleType) : array {
            $data = $this->cacheManager->fetchFromCache($file);

            if (!is_null($data)) return $data;

            $content = file_get_contents($file);

            if ($content === false) {
                throw new ImportFileReadException("Failed to read rule file: $file");
            }

            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidImportFormatException("Failed to parse JSON in rule file '$file': " . json_last_error_msg());
            }

            if (!is_array($decoded)) {
                throw new InvalidImportContentException("Imported JSON files must contain an array.");
            }

            $rule = match ($ruleType) {
                RuleType::REDIRECT => Redirect::class,
                RuleType::REWRITE => Rewrite::class,
                RuleType::ROUTE => Route::class
            };

            $data = array_map(
                fn ($item) => new $rule($item["name"], $item["pattern"], $item["map"]),
                $decoded
            );

            $this->cacheManager->cache($file, $data);

            return $data;
        }

        /**
         * Imports a PHP file.
         * @param string $file The file to import.
         * @return array The imported data.
         * @throws InvalidImportContentException If the PHP file doesn't return an iterable.
         */
        protected function importPHP (string $file) : array {
            $data = require $file;

            if (!is_iterable($data)) {
                throw new InvalidImportContentException("Imported PHP files must return an iterable.");
            }

            return $data;
        }

        /**
         * Imports specified files of a certain type of rule.
         * @param string ...$files The files to import.
         * @return RuleType[] The rules.
         * @throws ImportFileNotFoundException If a file doesn't exist.
         * @throws ImportPathEscapeException If a file is outside trusted roots.
         * @throws UnsupportedRuleFileTypeException If a file extension is not supported.
         */
        public function import (RuleType $ruleType, string $file, string ...$files) : array {
            $files = [$file, ...$files];

            $rules = [];

            foreach ($files as $file) {
                if (!is_file($file)) {
                    throw new ImportFileNotFoundException("File '$file' not found.");
                }

                $this->assertFileIsWithinTrustedRoots($file);

                $info = pathinfo($file);
                $extension = strtoupper($info["extension"]);

                switch ($extension) {
                    case "PHP":
                        $data = $this->importPHP($file);
                        break;

                    case "JSON":
                        $data = $this->importJSON($file, $ruleType);
                        break;

                    default:
                        throw new UnsupportedRuleFileTypeException("Unsupported rule file extension '.$extension' in file '$file'.");
                }

                array_push($rules, ...$data);
            }

            Emitter::create()->with(ruleType: $ruleType, files: $files, rules: $rules, importer: $this)->emit(Signal::RULES_IMPORTED);

            return $rules;
        }
    }
?>