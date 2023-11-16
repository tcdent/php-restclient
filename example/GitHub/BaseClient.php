<?php declare(strict_types=1);

namespace RestClient\Examples\GitHub;

use \RestClient;
use \RestClient\Response;
use \RestClient\Params;
use \RestClient\Headers;
use \RestClient\Attributes\{ Header, Param };

enum Direction: string {
    case asc = 'asc';
    case desc = 'desc';
}
class Repo {
    public string $path;
    public function __construct(
        public readonly string $owner,
        public readonly string $repo
    ) {
        $this->path = "{$this->owner}/{$this->repo}";
    }
}

abstract class BaseClient extends RestClient {
    //public $allowed_verbs = ['GET'];
    public $base_url = 'https://api.example.com';
    public $user_agent = 'PHP RestClient example script (github.com/tcdent/php-restclient/example/GitHubClient.php)';
    public $headers = [
        'X-GitHub-Api-Version' => '2022-11-28', 
        'Accept' => 'application/vnd.github+json'
    ];
    public $decoders = [
        'ld+json' => 'json_decode'
    ];
    public function __construct(...$args) {
        parent::__construct(...$args);
        $this->headers['Authorization'] = "Bearer: ".getenv('GITHUB_TOKEN');
    }
}
