<?php declare(strict_types=1);

namespace RestClient;

use RestClient\Params;
use PHPUnit\Framework\TestCase;

require 'vendor/autoload.php';

class ParamsTest extends TestCase {
    public function test_params() : void {
        $p = new Params(['foo' => 'bar']);
        $this->assertEquals('foo=bar', (string) $p);
    }
    public function test_params_with_array() : void {
        $p = new Params(['foo' => ['bar', 'baz']]);
        $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz', (string) $p);
    }
    public function test_params_with_array_and_index() : void {
        $p = new Params(['foo' => [0 => 'bar', 1 => 'baz']]);
        $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz', (string) $p);
    }
    public function test_params_with_empty() : void {
        $p = new Params(['foo' => [0 => 'bar', 1 => 'baz', 2 => '']]);
        $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz&foo%5B%5D=', (string) $p);
    }
    public function test_params_nested() : void {
        $p = new Params(['foo' => ['bar' => ['baz' => 'qux']]]);
        $this->assertEquals('foo%5B%5D%5B%5D=qux', (string) $p);
    }
}