<?php declare(strict_types=1);

namespace RestClient\Examples\GitHub\Issues;

use \RestClient\Params;
use \RestClient\Headers;
use \RestClient\Attributes\{ Header, Param };
use \RestClient\Examples\GitHub\BaseClient;
use \RestClient\Examples\GitHub\{ Repo, Direction };

enum Filter: string {
    case assigned = 'assigned';
    case created = 'created';
    case mentioned = 'mentioned';
    case subscribed = 'subscribed';
    case repos = 'repos';
    case all = 'all';
}
enum State: string {
    case open = 'open';
    case closed = 'closed';
    case all = 'all';
}
enum Sort: string {
    case created = 'created';
    case updated = 'updated';
    case comments = 'comments';
}
enum LockReason: string {
    case off_topic = 'off-topic';
    case too_heated = 'too heated';
    case resolved = 'resolved';
    case spam = 'spam';
}

class Client extends BaseClient {
    /**
     * `mine`
     * List issues assigned to the authenticated user across all visible repositories 
     * including owned repositories, member repositories, and organization repositories. 
     * You can use the filter query parameter to fetch issues that are not necessarily 
     * assigned to you.
     * @param Params|array $params
     * @param string $params.filter Filter issues by assigned, created, mentioned, subscribed, all. Default: assigned
     * @param string $params.state Filter issues by open, closed, all. Default: open
     * @param string $params.labels String list of comma separated Label names. Example: bug,ui,@high
     * @param string $params.sort Sort issues by created, updated, comments. Default: created
     * @param string $params.direction Sort issues by asc, desc. Default: desc
     * @param string $params.since Only issues updated at or after this time are returned. This is a timestamp in ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ.
     * @param bool $params.collab Filter issues by those either authored by a member or assigned to a member. Set to true or false. Default: false
     * @param bool $params.orgs Filter issues by those assigned to repositories that the authenticated user has explicit permission (:read, :write, or :admin) to collaborate on. Set to true or false. Default: false
     * @param bool $params.owned Filter issues by those created by the authenticated user. Set to true or false. Default: false
     * @param bool $params.pulls Include pull requests in the results. Set to true or false. Default: true
     * @param int $params.per_page Results per page (max 100). Default: 30
     * @param int $params.page Page number of the results to fetch. Default: 1
     * @return object
     * @see https://docs.github.com/en/rest/issues/issues#list-issues-assigned-to-the-authenticated-user
     */
    #[Param(string: 'filter', allowed: Filter::class)]
    #[Param(string: 'state', allowed: State::class)]
    #[Param(string: 'labels')]
    #[Param(string: 'sort', allowed: Sort::class)]
    #[Param(string: 'direction', allowed: Direction::class)]
    #[Param(string: 'since')]
    #[Param(bool: 'collab')]
    #[Param(bool: 'orgs')]
    #[Param(bool: 'owned')]
    #[Param(bool: 'pulls')]
    #[Param(int: 'per_page')]
    #[Param(int: 'page')]
    public function mine(array|Params $params=[]) : object {
        return $this->GET("/issues", $params);
    }

    /**
     * `list`
     * List issues in a repository. Only open issues will be listed.
     * @param Repo $repo
     * @param Params|array $params
     * @param string $params.milestone
     * If an integer is passed, it should refer to a milestone by its number field.
     * If the string '*' is passed, issues with any milestone are accepted.
     * If the string 'none' is passed, issues without milestones are returned.
     * @param State $params.state
     * Indicates the state of the issues to return. Default: State::open
     * @param string $params.assignee
     * Can be the name of a user. Pass in 'none' for issues with no assigned user, 
     * and '*' for issues assigned to any user.
     * @param string $params.creator
     * The user that created the issue. Example: octocat
     * @param string $params.mentioned
     * A user that's mentioned in the issue.
     * @param string $params.labels
     * A list of comma separated label names. Example: bug,ui,@high
     * @param Sort $params.sort
     * What to sort results by. Default: Sort::created
     * @param Direction $params.direction
     * The direction to sort the results by. Default: Direction::desc
     * @param string $params.since
     * Only show results that were last updated after the given time.
     * This is a timestamp in ISO 8601 format: YYYY-MM-DDTHH:MM:SSZ.
     * @param int $params.per_page
     * The number of results per page. Max: 100. Default: 30
     * @param int $params.page
     * Page number of the results to fetch. Default: 1
     * @return object
     * @see https://docs.github.com/en/rest/issues/issues#list-repository-issues
     */
    #[Param(string: 'milestone')]
    #[Param(string: 'state', allowed: State::class)]
    #[Param(string: 'assignee')]
    #[Param(string: 'creator')]
    #[Param(string: 'mentioned')]
    #[Param(string: 'labels')]
    #[Param(string: 'sort', allowed: Sort::class)]
    #[Param(string: 'direction', allowed: Direction::class)]
    #[Param(string: 'since')]
    #[Param(int: 'per_page')]
    #[Param(int: 'page')]
    public function list(Repo $repo, array|Params $params=[]) : object {
        return $this->GET("/repos/{$repo->path}/issues", $params);
    }

    /**
     * `detail`
     * Get a single issue.
     * @param Repo $repo
     * @param int $issue_number
     * @return object
     */
    public function detail(Repo $repo, int $issue_number) : object {
        return $this->GET("/repos/{$repo->path}/issues/{$issue_number}");
    }

    /**
     * `create`
     * Any user with pull access to a repository can create an issue. 
     * @param Repo $repo
     * $param string|int $title The title of the issue.
     * @param Params|array $params
     * @param string $params.body 
     * The contents of the issue.
     * @param string|null $params.assignee 
     * Login for the user that this issue should be assigned to. 
     * @param null|string|integer $params.milestone 
     * The number of the milestone to associate this issue with. 
     * @param array $params.labels
     * Labels to associate with this issue. 
     * @param array $params.assignees
     * Logins for Users to assign to this issue. 
     * @return object
     */
    #[Param(string: 'title')]
    #[Param(string: 'body')]
    #[Param(string: 'assignee')]
    #[Param(string: 'milestone')]
    #[Param(array: 'labels')]
    #[Param(array: 'assignees')]
    public function create(Repo $repo, array|Params $params) : object {
        assert(isset($params['title']), "Issue title is required.");
        return $this->POST("/repos/{$repo->path}/issues", $params);
    }

    /**
     * `update`
     * Edit an issue.
     * @param Repo $repo
     * @param int $issue_number
     * @param Params|array $params
     * @param string $params.title
     * The title of the issue.
     * @param string $params.body
     * The contents of the issue.
     * @param string|null $params.assignee
     * Login for the user that this issue should be assigned to.
     * @param State $params.state
     * State of the issue. State::open or State::closed
     * @param string $params.state_reason
     * Reason for the state change.
     * @param string|integer $params.milestone
     * The number of the milestone to associate this issue with.
     * @param array $params.labels
     * Labels to associate with this issue.
     * @param array $params.assignees
     * Logins for Users to assign to this issue.
     * @return object
     * @ see https://docs.github.com/en/rest/issues/issues#update-an-issue
     */
    #[Param(string: 'title')]
    #[Param(string: 'body')]
    #[Param(string: 'assignee')]
    #[Param(string: 'state', allowed: [State::open, State::closed])]
    #[Param(string: 'state_reason')]
    #[Param(string: 'milestone')]
    #[Param(array: 'labels')]
    #[Param(array: 'assignees')]
    public function update(Repo $repo, int $issue_number, array|Params $params=[]) : object {
        return $this->PATCH("/repos/{$repo->path}/issues/{$issue_number}", $params);
    }

    /**
     * `lock`
     * Lock an issue.
     * @param Repo $repo
     * @param int $issue_number
     * @param LockReason $reason
     * @return bool
     */
    public function lock(Repo $repo, int $issue_number, LockReason $reason) : bool {
        $response = $this->PUT("/repos/$repo->path}/issues/{$issue_number}/lock", [
            'lock_reason' => $reason
        ]);
        return $response->success;
    }
}
