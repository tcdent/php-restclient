<?php declare(strict_types=1);

namespace RestClient;
use RestClient\Log;
use \RestClient\Exception;

/**
 * `Headers`
 * Represents HTTP headers.
 */
class Headers extends \ArrayObject {
    /**
     * Construct a Headers object from an array of headers.
     * @param array $headers: The headers to construct the object from.
     */
    public function __construct(array $headers=[]) {
        foreach($headers as $key => $value)
            $this[$key] = $value;
    }
    /**
     * Create a string representation of the headers.
     */
    public function __toString() : string {
        return implode("\n", array_map(function($k, $v) {
            return sprintf("%s: %s", $k, $v);
        }, array_keys($this->getArrayCopy()), $this->getArrayCopy()));
    }
    /**
     * `merge`
     * Merge two Headers objects.
     * @param Headers $headers: The headers to merge. Precedence is given to the
     *  passed headers.
     * @return Headers
     */
    public function merge(Headers $headers) : Headers {
        return new self(array_merge($this->getArrayCopy(), $headers->getArrayCopy()));
    }
    /**
     * `has_key`
     * Whether the headers have a given key.
     * @param string $key: The key to check for.
     * @return bool
     */
    public function has_key(string $key) : bool {
        return array_key_exists($key, $this->getArrayCopy());
    }
}
