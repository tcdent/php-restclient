<?php declare(strict_types=1);

namespace RestClient;

use RestClient\Resource;
use RestClient\Params;
use PHPUnit\Framework\TestCase;

require 'vendor/autoload.php';

class ResourceTest extends TestCase {
    public function test_all() : void {
        $r = new Resource('http://example.com:80/a/index.html?foo=bar&baz=qux');
        $this->assertEquals('http', $r->scheme);
        $this->assertEquals('example.com', $r->host);
        $this->assertEquals(80, $r->port);
        $this->assertEquals('/a/index.html', (string) $r->path);
        $this->assertEquals('foo=bar&baz=qux', (string) $r->query);
        $this->assertEquals('http://example.com:80/a/index.html?foo=bar&baz=qux', (string) $r);
    }
    public function test_scheme() : void {
        $r = new Resource('https://');
        $this->assertEquals('https', $r->scheme);
        $this->assertEquals(NULL, $r->host);
        $this->assertEquals(NULL, $r->port);
        $this->assertEquals('', (string) $r->path);
        $this->assertEquals('', (string) $r->query);
        $this->assertEquals('https://', (string) $r);
    }
    public function test_path() : void {
        $r = new Resource('/foo/bar');
        $this->assertEquals(NULL, $r->scheme);
        $this->assertEquals(NULL, $r->host);
        $this->assertEquals(NULL, $r->port);
        $this->assertEquals('/foo/bar', (string) $r->path);
        $this->assertEquals('', (string) $r->query);
        $this->assertEquals('/foo/bar', (string) $r);
    }
    public function test_relative_path() : void {
        $r = new Resource('foo/bar');
        $this->assertEquals(NULL, $r->scheme);
        $this->assertEquals(NULL, $r->host);
        $this->assertEquals(NULL, $r->port);
        $this->assertEquals('foo/bar', (string) $r->path);
        $this->assertEquals('', (string) $r->query);
        $this->assertEquals('/foo/bar', (string) $r);
    }
    public function test_query() : void {
        $r = new Resource('?foo=bar&baz=qux');
        $this->assertEquals(NULL, $r->scheme);
        $this->assertEquals(NULL, $r->host);
        $this->assertEquals(NULL, $r->port);
        $this->assertEquals('', (string) $r->path);
        $this->assertEquals('foo=bar&baz=qux', (string) $r->query);
        $this->assertEquals('foo=bar&baz=qux', (string) $r);
    }
    public function test_query_array() : void {
        $r = new Resource('?foo[]=bar&foo[]=baz');
        $this->assertEquals(NULL, $r->scheme);
        $this->assertEquals(NULL, $r->host);
        $this->assertEquals(NULL, $r->port);
        $this->assertEquals('', (string) $r->path);
        $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz', (string) $r->query);
        $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz', (string) $r);
    }
    // public function test_query_encoded_array() : void {
    //     $r = new Resource('?foo%5B%5D=bar&foo%5B%5D=baz');
    //     $this->assertEquals(NULL, $r->scheme);
    //     $this->assertEquals(NULL, $r->host);
    //     $this->assertEquals(NULL, $r->port);
    //     $this->assertEquals('', (string) $r->path);
    //     $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz', (string) $r->query);
    //     $this->assertEquals('foo%5B%5D=bar&foo%5B%5D=baz', (string) $r);
    // }
}