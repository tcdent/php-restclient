<?php declare(strict_types=1);

namespace RestClient;

use \RestClient;
use RestClient\Exception;
use RestClient\Headers;
use RestClient\Params;
use RestClient\Resource;

/**
 * `Request`
 * Represents a request to be made.
 * @param string $method: The HTTP method of the request.
 * @param Resource $url: The URL of the request.
 * @param string|Params $params: The parameters of the request.
 * @param Headers $headers: The headers of the request.
 */
class Request {
    public function __construct(
        public string $method, 
        public Resource $url, 
        public string|Params $params, 
        public Headers $headers
    ) {}
}
