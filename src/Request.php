<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

final class Request
{
    private $method;
    private $path;
    private $http = 1.0;
    private $content = '';
    private $headers = [];

    public function __construct(string $method, string $path = '/', float $http = 1.0, ?string $content = null, array $headers = [])
    {
        $this->method = \strtoupper($method);
        $this->path = $path;
        $this->http = $http;
        $this->content = $content;
        $this->headers = $headers;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function isPut() : bool
    {
        return 'PUT' === $this->method;
    }

    public function isPost() : bool
    {
        return 'POST' === $this->method;
    }

    public function isGet() : bool
    {
        return 'GET' === $this->method;
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
}
