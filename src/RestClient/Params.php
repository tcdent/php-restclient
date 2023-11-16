<?php declare(strict_types=1);

namespace RestClient;
use RestClient\Log;
use \Countable;
use \JsonSerializable;

/**
 * `Params`
 * Represents a URL query string or POST body.
 * @param null|bool $INDEXES: Whether to use indexes in the query string.
 *   Example: `foo[0]=bar&foo[1]=baz` vs `foo[]=bar&foo[]=baz`
 * @param null|bool $ENCODE_KEYS: Whether to encode the keys in the query string.
 *  Example: `foo[]=bar&baz[]=bat` vs `foo%5B%5D=bar&baz%5B%5D=bat`
 * @return void
 */
class Params implements Countable, JsonSerializable {
    public bool $INDEXES = FALSE;
    public bool $ENCODE_KEYS = TRUE;
    private array $_params;
    /**
     * @param array|string $params: The parameters to construct the object from.
     * String parameters are parsed into an array.
     * @param null|bool $INDEXES: Whether to use indexes in the query string.
     * @param null|bool $ENCODE_KEYS: Whether to encode the keys in the query string.
     */
    public function __construct(array|string $params, ?bool $INDEXES=NULL, ?bool $ENCODE_KEYS=NULL) {
        $this->INDEXES = $INDEXES ?? $this->INDEXES;
        $this->ENCODE_KEYS = $ENCODE_KEYS ?? $this->ENCODE_KEYS;
        if(is_string($params))
            $params = self::_parse_query($params);
        $this->_params = $params;
    }
    /**
     * Construct a query string from the available parts.
     * @return string
     */
    public function __toString() : string {
        return self::_build_query(array_keys($this->_params), array_values($this->_params));
    }
    /**
     * 'jsonSerialize'
     * Make the object JSON serializable with `json_encode`.
     * Implements: `\JsonSerializable`
     * @return array
     */
    public function jsonSerialize() : array {
        return $this->_params;
    }
    /**
     * `merge`
     * Merge two Params objects.
     * @param Params $params: The parameters to merge. Precedence is given to the
     *  passed parameters. Merges are recursive.
     * @return Params
     */
    public function merge(Params $params) : Params {
        $merged = clone $this;
        $merged->_params = array_merge_recursive($this->_params, $params->_params);
        return $merged;
    }
    /**
     * `count`
     * Count the parameters.
     * Implements: `\Countable`
     * @return int
     */
    public function count() : int {
        return count($this->_params);
    }
    /**
     * @private `_parse_query`
     * Parse a query string into an array. Stacks array values sequentially or by key.
     * @param string $query: The query string to parse.
     * @return array
     */
    private function _parse_query(string $query) : array {
        return array_reduce(explode('&', $query), function ($params, $pair) {
            [$key, $value] = explode('=', $pair, 2);
            if(preg_match('/(.+)\[(\d*)\]$/', $key, $matches)) {
                [$_, $key, $index] = $matches;
                if($index === '')
                    $params[$key][] = $value;
                else
                    $params[$key][$index] = $value;
            } else
                $params[$key] = $value;
            return $params;
        }, []);
    }
    /**
     * @private `_build_query`
     * Build a query string from an array.
     * @param array $keys: The keys of the query string.
     * @param array $values: The values of the query string.
     * @return string
     */
    private function _build_query(array $keys, array $values): string {
        $pairs = array_map(function($key, $value) {
            return $this->_build_pair($key, $value);
        }, $keys, $values);
        return implode('&', $pairs);
    }
    /**
     * @private `_build_pair`
     * Build a query string pair from a key and value.
     * @param mixed $key: The key of the query string pair.
     * @param mixed $value: The value of the query string pair.
     * @return string
     */
    private function _build_pair(mixed $key, mixed $value) : string {
        if(is_array($value)){
            $keys = array_map(function($i) use ($key) {
                return $this->INDEXES? "{$key}[{$i}]" : "{$key}[]";
            }, array_keys($value));
            return $this->_build_query($keys, $value);
        }
        return sprintf("%s=%s", 
            $this->ENCODE_KEYS? urlencode($key) : $key, 
            is_string($value)? urlencode($value) : $value);
    }
}
