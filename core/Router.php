<?php

namespace Nest\Routing;

class Router
{
    private array $environment = [];
    private static array $paths = [
        'GET'  => [],
        'POST' => [],
    ];

    public static function get($path, $callback, $extend = null)
    {
        if ($extend == null)
            self::$paths['GET'][$path] = [
                $callback[0],
                $callback[1],
            ];
        else
            self::$paths['GET'][$path] = [
                $extend[0],
                $extend[1],
                $callback
            ];

        return;
    }

    public static function post($path, $callback, $extend = null)
    {
        if ($extend == null)
            self::$paths['POST'][$path] = [
                $callback[0],
                $callback[1],
            ];
        else
            self::$paths['POST'][$path] = [
                $extend[0],
                $extend[1],
                $callback
            ];
    }

    public static function put($path, $callback, $extend = null)
    {
        if ($extend == null)
            self::$paths['PUT'][$path] = [
                $callback[0],
                $callback[1],
            ];
        else
            self::$paths['PUT'][$path] = [
                $extend[0],
                $extend[1],
                $callback
            ];
    }

    public static function delete($path, $callback, $extend = null)
    {
        if ($extend == null)
            self::$paths['DELETE'][$path] = [
                $callback[0],
                $callback[1],
            ];
        else
            self::$paths['DELETE'][$path] = [
                $extend[0],
                $extend[1],
                $callback
            ];
    }

    public static function match(array $requests, $path, $callback, $extend)
    {
        foreach ($requests as $request) {
            Router::$request($path, $callback, $extend);
        }
    }

    public static function any(string $path, $callback, $extend)
    {
        Router::match(['GET', 'POST', 'PUT', 'DELETE'], $path, $callback, $extend);
    }

    /**
     * TODO: Make routing work for functions as well.
     *
     * @return bool
     */
    private function _functionResolver($callback): bool
    {
        return false;
    }

    /**
     * It requires a [ class, method ].
     *
     * @param array $callback
     *
     * @return bool
     */
    private function _classResolver(array $callback): bool
    {
        if (count($callback) < 2) {
            /**
             * TODO: Write up an error.
             */
            throw new \Exception('');
        }

        $class = $callback[0];
        $method = $callback[1];

        if (isset($callback[2])) {
            $instance = new \App\Http\Kernal;
            $middlewares = $instance->middleware($callback[2]);

            foreach ($middlewares as $middleware) {
                $output = call_user_func_array(
                    [
                        new $middleware,
                        'handle'
                    ], 
                    $this->dependancies($middleware, 'handle')
                );

                if (!$output) die(
                    file_get_contents(__DIR__.'/templates/unauthorized.php')
                );
            }
        }
        
        $arguments = $this->dependancies($class, $method);
        $code = call_user_func_array(
            [
                new $class($this->environment),
                $method,
            ],
            $arguments
        );

        eval(sprintf(' ?> %s <?php ', $code));

        return true;
    }

    private function dependancies(string $class, string $method) {
        $reflection = new \ReflectionClass($class);
        $parameters = $reflection->getMethod($method)->getParameters();
        $arguments = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType()->getName();
            $arguments[] = new $dependency();
        }

        return $arguments;
    }

    /**
     * Does all the routing for you.
     * TODO: fucntionResolver is still left.
     *
     * @return void
     */
    public function execute(array $environment)
    {
        $prefix = str_replace($_SERVER['DOCUMENT_ROOT'], '', APPLICATION_DIRECTORY);

        $this->environment = $environment;
        $request = new Request();

        $path = str_replace($prefix, '', $request->path);
        $method = $request->method;
        $callback = self::$paths[$method][$path];

        if ($callback) {
            $this->_classResolver($callback);
        }
    }
}
