<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

final class Route
{
    private $method;
    private $path;
    /**
     * @var callable
     */
    private $callback;

    public function __construct(string $method, string $path, callable $callback)
    {
        $this->method = \strtoupper($method);
        $this->path = $path;
        $this->callback = $callback;
    }

    public function getMethod() : string
    {
        return $this->method;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function match(string $path) : array
    {
        if (false !== \strpos($this->path, '{') && false !== \strpos($this->path, '}')) {
            $regex = \preg_replace('/(\{([^\}]+)\})/', '(?<\\2>[^\\/]+)', \str_replace('/', '\\/', $this->path));
            if (\preg_match("/{$regex}/", $path, $matches)) {
                foreach($matches as $key => $value) {
                    if (\is_numeric($key)) unset($matches[$key]);
                }
                return \array_merge(['path' => $path], $matches);
            }
            return [];
        }
        return $path === $this->path ? ['path' => $path] : [];
    }

    public function getCallback() : callable
    {
        return $this->callback;
    }

    public function __toString() : string
    {
        return "{$this->getMethod()} {$this->getPath()}";
    }
}
