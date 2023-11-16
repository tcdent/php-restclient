<?php declare(strict_types=1);

namespace RestClient\Attributes;

use \RestClient\Params;
use \Attribute;
use \BackedEnum;

/**
 * `Param`
 * Represents a parameter to be passed to a RestClient method as an attribute.
 * Added to a method call in client implementations to enforce type and value
 * of query string and POST body parameters. The method must accept a $params
 * argument of type Params|array.
 * Arrays are converted to Params objects before being passed to the method.
 * @example
 * ```php
 * #[Param(string: 'foo', allowed: ['bar', 'baz'])]
 * public function my_method(array|Params $params) { ...
 * ```
 * @param string $key: The key to use for the parameter.
 * @param mixed $type: The type of the parameter. One of: int, float, string, array.
 * @param mixed $allowed: The allowed values for the parameter.
 * @param mixed $default: The default value for the parameter.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Param {
    public string $key;
    public mixed $type;
    public mixed $allowed;
    public mixed $default;
    /**
     * Construct a Param attribute.
     * @param string $int: The key of the parameter if the parameter is an int.
     * @param string $float: The key of the parameter if the parameter is a float.
     * @param string $string: The key of the parameter if the parameter is a string.
     * @param string $array: The key of the parameter if the parameter is an array.
     * @param mixed $default: The default value for the parameter.
     * @param mixed $allowed: The allowed values for the parameter.
     */
    public function __construct(string $int=NULL, string $float=NULL, string $string=NULL, string $array=NULL, mixed $default=NULL, mixed $allowed=NULL){
        switch(true){
            case isset($int): $this->type = 'int'; $this->key = $int; break;
            case isset($float): $this->type = 'float'; $this->key = $float; break;
            case isset($string): $this->type = 'string'; $this->key = $string; break;
            case isset($array): $this->type = 'array'; $this->key = $array; break;
        }
        $this->default = $default;
        // allow passing an Enum : int|string class to define allowed values
        if(is_subclass_of($allowed, BackedEnum::class))
            $allowed = array_map(fn($_) => $_->value, $allowed::cases());
        $this->allowed = $allowed;
    }
    /**
     * `is_allowed`
     * Check if a value is allowed for this parameter.
     * Always returns true if no allowed values are defined.
     * @param mixed $value: The value to check.
     * @return bool
     */
    public function is_allowed($value) {
        if(!isset($this->allowed))
            return true;
        if(is_array($this->allowed))
            return in_array($value, $this->allowed);
        return false;
    }
}
