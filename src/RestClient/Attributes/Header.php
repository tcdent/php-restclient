<?php declare(strict_types=1);

namespace RestClient\Attributes;

use \Attribute;

/**
 * `Header`
 * Represents a header to be passed to a RestClient method as an attribute.
 * Added to a method call in client implementations to enforce type and value
 * of headers. The method must accept a $headers argument of type Headers|array.
 * Arrays are converted to Headers objects before being passed to the method.
 * @example
 * ```php
 * #[Header('Content-Type', 'application/json')]
 * public function my_method(array|Headers $headers) { ...
 * ```
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Header {
    public string $key;
    public string $value;
    /**
     * Construct a Header attribute.
     * @param string $key: The key of the header.
     * @param string $value: The value of the header.
     */
    public function __construct(string $key, string $value){
        $this->key = $key;
        $this->value = $value;
    }
}
