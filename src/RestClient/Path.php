<?php declare(strict_types=1);

namespace RestClient;
use \Countable;

/**
 * `Path`
 * Represents a URL path.
 * @param null|string|array $path: The path to construct the object from.
 * Array and string paths are exploded on '/'. NULL is an empty path.
 */
class Path implements Countable {
    private array $_parts;
    /**
     * Construct a path from the available parts.
     * @param null|string|array $path: The path to construct the object from.
     * String paths are exploded on '/'. NULL is an empty path.
     */
    public function __construct(null|string|array $path=NULL) {
        $this->_parts = [];
        if($path) $this->append($path);
    }
    /**
     * Construct a path from the available parts.
     * @return string
     */
    public function __toString() : string {
        return implode('/', $this->_parts);
    }
    /**
     * Count the parts of the path.
     * @return int
     */
    public function count() : int {
        return count($this->_parts);
    }
    /**
     * `append`
     * Append a path to the current path.
     * @param null|string|array|Path $path: The path to append.
     * String paths are exploded on '/'. NULL is an empty path.
     * Path objects are merged with the current path.
     * @return Path
     */
    public function append(null|string|array|Path $path) : Path {
        if($path instanceof Path)
            $this->_parts = array_merge($this->_parts, $path->_parts);
        if(is_string($path))
            $path = explode('/', $path);
        if(is_array($path))
            $this->_parts = array_merge($this->_parts, $path);
        return $this;
    }
    /**
     * `is_absolute`
     * Whether the path is absolute. Paths are absolute if they are a string that
     * starts with '/' or an array that starts with ''.
     * @return bool
     */
    public function is_absolute() : bool {
        return count($this->_parts) && $this->_parts[0] === '';
    }
    /**
     * `bool`
     * Whether the path is empty.
     * @return bool
     */
    public function bool() : bool {
        return count($this->_parts) > 0;
    }
}
