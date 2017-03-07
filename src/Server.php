<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

final class Server
{
    private $socket;
    private $cache = [];
    /**
     * @var Router
     */
    private $router;
    private $debug = false;
    private $terminate = false;

    public function __construct(string $host, int $port, bool $debug)
    {
        \pcntl_async_signals(true);
        \pcntl_signal(SIGTERM,  function() {
            $this->terminate = true;
            $this->log('HTTP Server gracefully terminating by SIGTERM');
        });
        $this->socket = @\stream_socket_server("tcp://{$host}:{$port}", $errNo, $errStr);
        if (false === $this->socket) {
            $this->error($errStr);
        }
        \stream_set_blocking($this->socket, true);
        $pid = \posix_getpid();
        $this->log("HTTP Server started on {$host}:{$port} [PID:{$pid}]");
        $this->router = new Router();
        $this->debug = $debug;
    }

    public function get(string $path, callable $callback) : self
    {
        $callback = \Closure::bind($callback, $this, self::class);
        $this->router->add(new Route('GET', $path, $callback));

        return $this;
    }

    public function head(string $path, callable $callback) : self
    {
        $callback = \Closure::bind($callback, $this, self::class);
        $this->router->add(new Route('HEAD', $path, $callback));

        return $this;
    }

    public function put(string $path, callable $callback) : self
    {
        $callback = \Closure::bind($callback, $this, self::class);
        $this->router->add(new Route('PUT', $path, $callback));

        return $this;
    }

    public function post(string $path, callable $callback) : self
    {
        $callback = \Closure::bind($callback, $this, self::class);
        $this->router->add(new Route('POST', $path, $callback));

        return $this;
    }

    public function patch(string $path, callable $callback): self
    {
        $callback = \Closure::bind($callback, $this, self::class);
        $this->router->add(new Route('PATCH', $path, $callback));

        return $this;
    }

    public function delete(string $path, callable $callback): self
    {
        $callback = \Closure::bind($callback, $this, self::class);
        $this->router->add(new Route('DELETE', $path, $callback));

        return $this;
    }

    public function run(callable $callback)
    {
        $this->router->fallback(\Closure::bind($callback, $this, self::class));
        $this->debug && $this->log('Accepting connections');
        while (true) {
            try {
                if ($this->terminate) {
                    break;
                }
                if (!$clientSocket = @\stream_socket_accept($this->socket)) {
                    continue;
                }
                $request = $this->decode(\fread($clientSocket, 8192));
                try {
                    $response = $this->router->dispatch($request);
                } catch (\Exception $exception) {
                    $response = new Response(500, "Error: {$exception->getMessage()}");
                }
                \fwrite($clientSocket, (string)$response);
                \fclose($clientSocket);
                if ($response->getStatus() >= 300) {
                    $this->log(
                        "Failed request({$request->getMethod()} {$request->getPath()} {$response->getStatus()}) "
                        . $response->getContent(),
                        "\033[0;33m"
                    );

                } else {
                    $this->debug && $this->log("Handled request({$request->getMethod()} {$request->getPath()} {$response->getStatus()})");
                }
            } catch (\Throwable $error) {
                $this->log("Internal error: {$error->getMessage()} on {$error->getLine()}", "\033[0;33m");
            } catch (\Exception $exception) {
                $this->log("Internal exception: {$exception->getMessage()}", "\033[0;33m");
            }
        }
    }

    private function decode(string $raw) : Request
    {
        if (\array_key_exists($raw, $this->cache)) {
            return $this->cache[$raw];
        }

        return $this->cache[$raw] = Request::createFromString($raw);
    }

    protected function log(string $msg, string $color = "\033[0;32m")
    {
        \printf("%s[%s] %s\033[0m\n", $color, \date('Y-m-d H:i:s'), $msg);
    }

    protected function error(string $msg)
    {
        $this->log($msg, "\033[0;31m");
        exit(1);
    }
}
