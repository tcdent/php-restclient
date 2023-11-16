<?php declare(strict_types=1);

namespace RestClient;

use \RestClient;
use RestClient\Headers;
use RestClient\Exception;
use PHPUnit\Framework\TestCase;

require 'vendor/autoload.php';

class HeadersTest extends TestCase {
    public function test_basic() : void {
        $h = new Headers([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
        $this->assertEquals('application/json', (string) $h['Content-Type']);
        $this->assertEquals('application/json', (string) $h['Accept']);
        $this->assertEquals("Content-Type: application/json\nAccept: application/json", (string) $h);
    }
}