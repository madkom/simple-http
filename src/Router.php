<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

final class Router
{
    /**
     * @var Route[]
     */
    private $routes = [];
    /**
     * @var callable
     */
    private $fallback;

    public function __construct(array $routes = [])
    {
        $this->routes = $routes;
    }

    public function add(Route $route)
    {
        $this->routes[] = $route;
    }

    public function fallback(callable $callback)
    {
        $this->fallback = $callback;
    }

    public function dispatch(Request $request) : Response
    {
        if (count($this->routes)) {
            foreach ($this->routes as $route) {
                if ($route->getMethod() === $request->getMethod() && ($params = $route->match($request->getPath()))) {
                    $callback = $route->getCallback();
                    $arguments = [];
                    foreach ((new \ReflectionFunction($callback))->getParameters() as $reflectionParameter) {
                        $name = $reflectionParameter->getName();
                        $class = $reflectionParameter->getClass();
                        if ($class && $class->getName() === Request::class) {
                            $arguments[] = $request;
                            continue;
                        }
                        if (\array_key_exists($name, $params)) {
                            $arguments[] = $params[$name];
                            continue;
                        }
                    }
                    return call_user_func_array($callback, $arguments);
                }
            }
        }
        if ($this->fallback) {
            $arguments = [];
            foreach ((new \ReflectionFunction($this->fallback))->getParameters() as $reflectionParameter) {
                $name = $reflectionParameter->getName();
                $class = $reflectionParameter->getClass();
                if ($class && $class->getName() === Request::class) {
                    $arguments[] = $request;
                    continue;
                }
                if ('path' === $name) {
                    $arguments[] = $request->getPath();
                    continue;
                }
            }
            return call_user_func_array($this->fallback, $arguments);
        }

        return new Response(404);
    }

    /**
     * @return Route[]
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }
}
