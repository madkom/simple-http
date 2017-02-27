<?php declare(strict_types = 1);
namespace Madkom\SimpleHTTP;
require_once __DIR__ . '/vendor/autoload.php';

$data = [];
$cache = [];

$server = new Server('0.0.0.0', $argc > 1 ? (int)$argv[1] : 80, $argc > 2 ? (bool)($argv[2] === '-v') : false);
$server
    ->get('/config', function (Request $request) use (&$data, &$cache) : Response {
        if (\array_key_exists('config', $cache)) {
            return $cache['config'];
        }
        return $cache['config'] = new JsonResponse(200, \array_keys($data));
    })
    ->get('/{name}', function (Request $request, string $name) use (&$data, &$cache) : Response {
        if (\array_key_exists($name, $cache)) {
            return $cache[$name];
        }
        if (false === \array_key_exists($name, $data)) {
            return new Response(404);
        }
        return $cache[$name] = new JsonResponse(200, $data[$name]);
    })
    ->put('/{name}', function (Request $request, string $name) use (&$data, &$cache) : Response {
        $json = \json_decode($request->getContent(), true);
        if (empty($json) && \json_last_error()) {
            return new Response(400, 'Malformed request: ' . \json_last_error_msg());
        }
        $data[$name] = \array_key_exists($name, $data) ? \array_merge($data[$name], (array)$json) : $json;
        unset($cache[$name], $cache['status']);
        return new Response(204);
    })
    ->post('/{name}', function (Request $request, string $name) use (&$data, &$cache) : Response {
        $json = \json_decode($request->getContent(), true);
        if (empty($json) && \json_last_error()) {
            return new Response(400, 'Malformed request: ' . \json_last_error_msg());
        }
        $data[$name] = \array_key_exists($name, $data) ? \array_merge($data[$name], (array)$json) : $json;
        unset($cache['status']);
        return $cache[$name] = new JsonResponse(200, $data[$name]);
    })
    ->run(function (Request $request, string $path) use (&$cache) : Response {
        if ($path === '/') {
            if (\array_key_exists('', $cache)) {
                return $cache[''];
            }
            $routes = \implode(\PHP_EOL, \array_map(function (Route $route) : string {
                return (string)$route;
            }, $this->router->getRoutes()));
            return $cache[''] = new Response(200, "<pre>{$routes}</pre>", [
                'Content-Type' => 'text/html',
            ]);
        }
        $this->log("Bad request({$request->getMethod()} {$request->getPath()})", "\033[0;33m");
        return new Response(400);
    });
