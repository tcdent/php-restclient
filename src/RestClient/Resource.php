<?php declare(strict_types=1);

namespace RestClient;

use RestClient\Log;
use RestClient\Path;
use RestClient\Params;

/**
 * `Resource`
 * Represents a URL (uniform resource locator).
 * @param null|string $scheme: The scheme (protocol) of the URL.
 * @param null|string $host: The host of the URL.
 * @param null|int $port: The port of the URL.
 * @param Path $path: The path of the URL.
 * @param Params $query: The query parameters of the URL.
 */
class Resource {
    public ?string $scheme;
    public ?string $host;
    public ?int $port;
    public Path $path;
    public Params $query;

    /**
     * @param null|string|array $parts: The URL parts to construct the resource from.
     * @param null|bool $QUERY_INDEXES: Whether to use indexes in the query string.
     * @return void
     */
    public function __construct(null|string|array $parts=NULL, ?bool $QUERY_INDEXES=NULL) {
        if(is_string($parts)) {
            if(preg_match('/^(\w+):\/\/$/', $parts, $matches))
                $parts = ['scheme' => $matches[1]];
            else
                $parts = parse_url($parts);
        }
        $parts = $parts ?? [];
        $this->scheme = $parts['scheme'] ?? null;
        $this->host = $parts['host'] ?? null;
        $this->port = $parts['port'] ?? null;
        $this->path = new Path($parts['path'] ?? []);
        $this->query = new Params($parts['query'] ?? [], 
            INDEXES: $QUERY_INDEXES);
    }
    /**
     * Construct a URL from the available parts.
     * @return string
     */
    public function __toString() : string {
        return implode([
            $this->scheme? sprintf("%s://", $this->scheme) : '', 
            $this->host ?? '', 
            $this->port? ':' : '',
            $this->port ?? '',
            count($this->path)? ($this->path->is_absolute()? '' : '/') : '',
            (string) $this->path ?? '', 
            count($this->query) && ($this->host || $this->port || count($this->path))? '?' : '',
            (string) $this->query ?? ''
        ]);
    }
    /**
     * `merge`
     * Merge another resource into this one giving preference to the other resource.
     * Does not to a deep merge of `query` parameters.
     * @param Resource $resource: The resource to merge.
     * @return Resource
     */
    public function merge(Resource $resource) : Resource {
        $merged = new Resource;
        $merged->scheme = $resource->scheme ?? $this->scheme;
        $merged->host = $resource->host ?? $this->host;
        $merged->port = $resource->port ?? $this->port;
        $merged->path = $resource->path ?? $this->path;
        $merged->query = $resource->query ?? $this->query;
        $merged->query->INDEXES = $this->query->INDEXES;
        return $merged;
    }
}
