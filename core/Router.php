<?php

namespace Nest\Routing;

class Router
{
    private array $environment = [];
    private static array $paths = [
        'GET'  => [],
        'POST' => [],
    ];
    
    public static function get($path, $callback)
    {
        self::$paths['GET'][$path] = [
            $callback[0],
            $callback[1],
        ];

        return ;
    }

    public static function post($path, $callback)
    {
        self::$paths['POST'][$path] = [
            $callback[0],
            $callback[1],
        ];
    }

    public static function put($path, $callback)
    {
        self::$paths['PUT'][$path] = [
            $callback[0],
            $callback[1],
        ];
    }

    public static function delete($path, $callback)
    {
        self::$paths['DELETE'][$path] = [
            $callback[0],
            $callback[1],
        ];
    }

    public static function match(array $requests, $path, $callback) {
        foreach ($requests as $request) {
            Router::$request($path, $callback);
        }
    }

    public static function url(string $path, string $method) {
        return ;
    }

    public static function any(string $path, $callback) {
        Router::match(['GET', 'POST', 'PUT', 'DELETE'], $path, $callback);
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

        $reflection = new \ReflectionClass($class);
        $parameters = $reflection->getMethod($method)->getParameters();
        $arguments = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getType()->getName();
            $arguments[] = new $dependency();
        }

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
