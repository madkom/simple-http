<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

class Response
{
    protected const OK  = 200;
    protected const CREATED = 201;
    protected const NO_CONTENT = 204;
    protected const BAD_REQUEST = 400;
    protected const NOT_FOUND = 404;
    protected const INTERNAL_SERVER_ERROR = 500;
    protected const STATUS = [
        self::OK => 'OK',
        self::CREATED => 'Created',
        self::NO_CONTENT => 'No Content',
        self::BAD_REQUEST => 'Bad Request',
        self::NOT_FOUND => 'Not Found',
        self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
    ];
    protected $status = self::OK;
    protected $headers = [];
    protected $content;
    protected $raw = '';

    public function __construct(int $status, $content = null, array $headers = [])
    {
        $this->status = $status;
        $this->content = $content;
        $this->headers = $headers;
        if ($status !== 204) {
            $this->headers['Content-Type'] = $this->headers['Content-Type'] ?? 'text/plain';
            $this->headers['Content-Length'] = \is_string($content) ? \strlen($this->content) : 0;
        } else {
            $this->content = null;
            unset($this->headers['Content-Type'], $this->headers['Content-Length']);
        }
        $this->build();
    }

    public function getStatus() : int
    {
        return $this->status;
    }

    public function getHeaders() : array
    {
        return $this->headers;
    }

    public function getContent() : ?string
    {
        return $this->content;
    }

    protected function build() : void
    {
        $this->raw = \sprintf(
            "HTTP/1.1 {$this->status} %s\r\n%s\r\n\r\n%s",
            self::STATUS[$this->status],
            $this->stringifyHeaders(),
            $this->content
        );
    }

    public function __toString() : string
    {
        return $this->raw;
    }

    protected function stringifyHeaders() : string
    {
        $result = '';
        foreach ($this->headers as $name => $value) {
            $result .= \sprintf(
                "%s: %s\r\n",
                \str_replace(' ', '-', \ucfirst(\str_replace(['-', '_'], ' ', $name))),
                $value
            );
        }

        return rtrim($result, "\r\n");
    }

    public static function createFromString(string $raw) : self
    {
        $lines = \explode("\r\n", $raw);
        $count = \count($lines);
        $headers = [];
        $status = 200;
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];
            if (0 === $i) {
                list($status, $message) = \sscanf($line, '%d');
                continue;
            }
            if (empty($line) && $i + 1 < $count) {
                $content = '';
                for ($i++; $i < $count; $i++) {
                    $content .= $lines[$i];
                }
                return new self($status, $content, $headers);
            }
            if (!empty($line)) {
                list($name, $value) = \explode(': ', $line);
                $headers[\strtolower($name)] = $value;
            }
        }
        if ($i > 0) {
            return new self($status, null, $headers);
        }

        throw new \RuntimeException('Malformed response');
    }
}
