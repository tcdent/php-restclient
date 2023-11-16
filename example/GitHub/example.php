<?php declare(strict_types=1);

namespace RestClient\Examples\GitHub;

use RestClient\Examples\GitHub;
use RestClient\Examples\GitHub\Direction;
use RestClient\Examples\GitHub\Issues\{ Filter, State, Sort };

# List issues for the current user.
$issues_api = new GitHub\Issues\Client;
$issues = $issues_api->mine([
    'sort' => Sort::created, 
    'direction' => Direction::desc, 
    'page' => 1, 
    'per_page' => 10
]);
foreach($issues->data as $issue)
    echo "{$issue->number}: {$issue->title}\n";

# List new issues for a repository.
$repo = new GitHub\Repo('tcdent', 'php-restclient');
$issues = $issues_api->list($repo, [
    'filter' => Filter::created, 
    'state' => State::open, 
    'page' => 1, 
    'per_page' => 10
]);
foreach($issues->data as $issue)
    echo "{$issue->number}: {$issue->title}\n";

# Create a new issue.
$response = $issues_api->create($repo, [
    'title' => 'The GitHub example implementation only supports Issues', 
    'assignees' => ['tcdent'], 
]);
if($response->success)
    echo "Created issue {$response->data->number}: {$response->data->title}\n";

