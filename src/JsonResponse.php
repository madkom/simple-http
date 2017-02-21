<?php declare(strict_types=1);
namespace Madkom\SimpleHTTP;

final class JsonResponse extends Response
{
    public function __construct(int $status, $content = '', array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        parent::__construct($status, @\json_encode($content), $headers);
    }
}
