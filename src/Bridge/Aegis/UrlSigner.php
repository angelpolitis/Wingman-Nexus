<?php
    /**
     * Project Name:    Wingman Nexus - Aegis Bridge - URL Signer
     * Created by:      Angel Politis
     * Creation Date:   Mar 18 2026
     * Last Modified:   Mar 18 2026
     * 
     * Copyright (c) 2026-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Bridge.Aegis namespace.
    namespace Wingman\Nexus\Bridge\Aegis;

    # Import the following classes to the current scope.
    use Wingman\Nexus\Exceptions\AegisNotInstalledException;
    use Wingman\Aegis\Services\SignedUrlService;

    /**
     * Bridges Nexus's signed-URL API with the Wingman Aegis security package.
     *
     * When `wingman/aegis` is installed, this class delegates all signing and
     * verification work to {@see SignedUrlService}, which
     * provides HMAC-SHA256 signatures and expiry-timestamp validation.
     *
     * When Aegis is absent, every call to {@see sign()} or {@see verify()} throws
     * a {@see AegisNotInstalledException}. Unlike the Corvus bridge's null-object stub, URL
     * signing cannot be silently absorbed — a missing secret would produce unsigned
     * URLs that appear valid, which is a security hole.
     *
     * ### Typical usage (via the Router)
     *
     * ```php
     * $router->configureUrlSigning(secret: "your-secret", defaultTtl: 3600);
     *
     * // In a controller or view:
     * $url = $router->generateSignedUrl("invoice.download", ['id' => 42], ttl: 900);
     *
     * // When the URL is accessed:
     * if (!$router->validateSignedUrl($request->getUri())) {
     *     // 403 / 410 – expired or tampered URL
     * }
     * ```
     *
     * @package Wingman\Nexus\Bridge\Aegis
     * @author  Angel Politis <info@angelpolitis.com>
     * @since   1.0
     */
    class UrlSigner {
        /**
         * The default query parameter name for the expiry timestamp.
         * Mirrors {@see SignedUrlService::DEFAULT_EXPIRY_PARAM}.
         * @var string
         */
        public const string DEFAULT_EXPIRY_PARAM = "expires";

        /**
         * The default query parameter name for the HMAC signature.
         * Mirrors {@see SignedUrlService::DEFAULT_SIGNATURE_PARAM}.
         * @var string
         */
        public const string DEFAULT_SIGNATURE_PARAM = "signature";

        /**
         * The default URL lifetime in seconds.
         * Mirrors {@see SignedUrlService::DEFAULT_TTL}.
         * @var int
         */
        public const int DEFAULT_TTL = 3600;

        /**
         * The Aegis SignedUrlService instance, or null when Aegis is not installed.
         * @var mixed
         */
        private mixed $service;

        /**
         * Creates a new URL signer bridge.
         *
         * If `wingman/aegis` is installed a {@see SignedUrlService}
         * is instantiated and stored; otherwise {@see $service} is null and every call to
         * {@see sign()} or {@see verify()} will throw a {@see AegisNotInstalledException}.
         * @param string $secret The HMAC secret. Must be ≥ 32 characters when Aegis is present.
         * @param int $defaultTtl The default URL lifetime in seconds.
         * @param string $signatureParam The query parameter name for the signature value.
         * @param string $expiryParam The query parameter name for the expiry timestamp.
         */
        public function __construct (
            string $secret,
            int $defaultTtl = self::DEFAULT_TTL,
            string $signatureParam = self::DEFAULT_SIGNATURE_PARAM,
            string $expiryParam = self::DEFAULT_EXPIRY_PARAM
        ) {
            $this->service = class_exists(SignedUrlService::class)
                ? new SignedUrlService($secret, $defaultTtl, $signatureParam, $expiryParam)
                : null;
        }

        /**
         * Asserts that Aegis is installed, throwing a descriptive {@see AegisNotInstalledException} if not.
         * @throws AegisNotInstalledException When the Wingman Aegis package is not installed.
         */
        private function assertServiceAvailable () : void {
            if ($this->service !== null) return;

            throw new AegisNotInstalledException(
                "Signed URL generation requires the Wingman Aegis package. "
                . "Install wingman/aegis and call Router::configureUrlSigning() "
                . "before generating or validating signed URLs."
            );
        }

        /**
         * Appends an expiry timestamp and an HMAC signature to the given URL.
         *
         * Delegates to {@see SignedUrlService::sign()}.
         * @param string $url The base URL, with or without existing query parameters.
         * @param int|null $ttl The lifetime in seconds. Defaults to the configured TTL when null.
         * @return string The signed URL.
         * @throws AegisNotInstalledException When the Wingman Aegis package is not installed.
         */
        public function sign (string $url, ?int $ttl = null) : string {
            $this->assertServiceAvailable();
            return $this->service->sign($url, $ttl);
        }

        /**
         * Verifies the signature and expiry of a signed URL.
         *
         * Delegates to {@see SignedUrlService::verify()}.
         * @param string $url The signed URL to verify, as received in an incoming request.
         * @return bool Whether the URL is unexpired and its signature is valid.
         * @throws AegisNotInstalledException When the Wingman Aegis package is not installed.
         */
        public function verify (string $url) : bool {
            $this->assertServiceAvailable();
            return $this->service->verify($url);
        }
    }
?>