<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

final class Request
{
    private $method;
    private $path;
    private $http = 1.0;
    private $content = '';
    private $headers = [];
    private $payload;

    public function __construct(string $method, string $path = '/', float $http = 1.0, ?string $content = null, array $headers = [])
    {
        $this->method = \strtoupper($method);
        $this->path = $path;
        $this->http = $http;
        $this->content = $content;
        $this->headers = $headers;
        $type = null;
        $charset = '';
        if (true === \array_key_exists('content-type', $this->headers)) {
            @list($type, $charset) = \explode(';', $this->headers['content-type']);
        }
        switch ($type ?? null) {
            case 'application/x-www-form-urlencoded':
                $charset = \str_replace([' ', 'charset='], '', $charset);
                \parse_str(\iconv($charset, 'utf8', \urldecode($content)), $this->payload);
                break;
            case 'application/json':
                $this->payload = \json_decode($content, true);
                if (empty($content) && \json_last_error()) {
                    throw new \RuntimeException('Malformed request: ' . \json_last_error_msg());
                }
                break;
            default:
                $this->payload = $content;
        }
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function isGet() : bool
    {
        return 'GET' === $this->method;
    }

    public function isHead() : bool
    {
        return 'HEAD' === $this->method;
    }

    public function isPost() : bool
    {
        return 'POST' === $this->method;
    }

    public function isPut() : bool
    {
        return 'PUT' === $this->method;
    }

    public function isPatch(): bool
    {
        return 'PATCH' === $this->method;
    }

    public function isDelete(): bool
    {
        return 'DELETE' === $this->method;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getHttp() : float
    {
        return $this->http;
    }

    public function getContent() : ?string
    {
        return $this->content;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function __toString() : string
    {
        $headers = '';
        foreach ($this->headers as $headerName => $headerValue) {
            $headers .= "{$headerName}: {$headerValue}\r\n";
        }

        return "{$this->method} {$this->path} HTTP/{$this->http}\r\n{$headers}\r\n\r\n{$this->content}";
    }

    public static function createFromString(string $raw) : self
    {
        $lines = \explode("\r\n", $raw);
        $count = \count($lines);
        $headers = [];
        $method = 'GET';
        $path = '/';
        $http = 1.0;
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];
            if (0 === $i) {
                list($method, $path, $http) = \sscanf($line, '%s %s HTTP/%f');
                continue;
            }
            if (empty($line) && $i + 1 < $count) {
                $line = $lines[$i + 1];
                return new self($method, $path, $http, $line, $headers);
            }
            if (!empty($line)) {
                list($name, $value) = \explode(': ', $line);
                $headers[\strtolower($name)] = $value;
            }
        }
        if ($i > 0) {
            if (!\is_string($method)) {
                die(print_r(get_defined_vars(), true));
            }
            return new self($method, $path, $http, null, $headers);
        }

        throw new \RuntimeException('Malformed request');
    }
}
