# Simple HTTP Server

Simple HTTP Server w/o concurrency in PHP

## Install

With _Composer_

```bash
composer require madkom/simple-http
```

## Usage

Simple usage:

```php
namespace Madkom\SimpleHTTP;

$server = new Server('0.0.0.0', $argc > 1 ? (int)$argv[1] : 80);
$server->run(function (Request $request) : Response {
    return new Response(200, "Requested path: {$request->getPath()}");
});
```

Advanced:

```php
namespace Madkom\SimpleHTTP;
$data = [];
$cache = [];

$server = new Server('0.0.0.0', $argc > 1 ? (int)$argv[1] : 80);
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
```

## License

MIT License

Copyright (c) 2017 Madkom

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
