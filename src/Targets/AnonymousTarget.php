<?php
    /**
     * Project Name:    Wingman Nexus - Anonymous Target
     * Created by:      Angel Politis
     * Creation Date:   Nov 22 2025
     * Last Modified:   Mar 19 2026
     * 
     * Copyright (c) 2025-2026 Angel Politis <info@angelpolitis.com>
     * This Source Code Form is subject to the terms of the Mozilla Public License, v. 2.0.
     * If a copy of the MPL was not distributed with this file, You can obtain one at http://mozilla.org/MPL/2.0/.
     */

    # Use the Nexus.Targets namespace.
    namespace Wingman\Nexus\Targets;

    # Import the following classes to the current scope.
    use Closure;
    use ReflectionFunction;
    use Wingman\Nexus\Exceptions\InvocationArgumentException;
    use Wingman\Nexus\Interfaces\Target;

    /**
     * Represents an anonymous target.
     * @package Wingman\Nexus\Targets
     * @author Angel Politis <info@angelpolitis.com>
     * @since 1.0
     */
    class AnonymousTarget implements Target {
        /**
         * The callable of an anonymous target.
         * @var callable
         */
        protected $callable;

        /**
         * The response headers of an anonymous target, inherited from a route group.
         * @var array
         */
        protected array $headers;

        /**
         * The HTTP method of an anonymous target.
         * @var string
         */
        protected string $method;

        /**
         * The middleware of an anonymous target, inherited from a route group.
         * @var string[]
         */
        protected array $middleware;

        /**
         * The number of parameters required by the callable, cached at construction.
         * @var int
         */
        protected int $parameterCount;

        /**
         * The tags of an anonymous target, inherited from a route group.
         * @var string[]
         */
        protected array $tags;

        /**
         * Creates a new anonymous target.
         * @param callable $callable The callable of an anonymous target.
         * @param string $method The HTTP method of an anonymous target.
         * @param string[] $middleware Middleware class names inherited from a route group.
         * @param string[] $tags Tag names inherited from a route group.
         * @param array $headers Response headers inherited from a route group.
         */
        public function __construct (callable $callable, string $method, array $middleware = [], array $tags = [], array $headers = []) {
            $this->callable = $callable;
            $this->method = $method;
            $this->middleware = $middleware;
            $this->tags = $tags;
            $this->headers = $headers;
            $this->parameterCount = (new ReflectionFunction(Closure::fromCallable($callable)))->getNumberOfParameters();
        }

        /**
         * Runs an anonymous target as a function.
         * @param mixed ...$arguments The arguments to pass to the target.
         * @return mixed The return value of the target.
         */
        public function __invoke (mixed ...$arguments) : mixed {
            return $this->run(...$arguments);
        }

        /**
         * Gets the callable of an anonymous target.
         * @return callable The callable of the target.
         */
        public function getCallable () : callable {
            return $this->callable;
        }

        /**
         * Gets the response headers of an anonymous target.
         * @return array The response headers.
         */
        public function getHeaders () : array {
            return $this->headers;
        }

        /**
         * Gets the method of an anonymous target.
         * @return string The method of the target.
         */
        public function getMethod () : string {
            return $this->method;
        }

        /**
         * Gets the middleware of an anonymous target.
         * @return string[] The middleware class names.
         */
        public function getMiddleware () : array {
            return $this->middleware;
        }

        /**
         * Gets the tags of an anonymous target.
         * @return string[] The tag names.
         */
        public function getTags () : array {
            return $this->tags;
        }

        /**
         * Runs the callable of an anonymous target.
         * @param mixed... $arguments The arguments of an anonymous target.
         * @return mixed The return value of the target.
         * @throws InvocationArgumentException When the argument count does not match the callable's parameter count.
         */
        public function run (mixed ...$arguments) : mixed {
            if ($this->parameterCount > count($arguments)) {
                throw new InvocationArgumentException("Argument count mismatch when invoking anonymous target.");
            }
            return ($this->callable)(...$arguments);
        }
    }
?>