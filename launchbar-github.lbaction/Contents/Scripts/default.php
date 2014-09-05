<?php

require_once('User.php');

function notEmpty($str) {
	return !empty($str);
}

/**
 * Returns the repository whose name is exactly $name
 * @param  Array      $repos List of repository
 * @param  String     $name  Name of the repo we're looking for
 * @return Repository        Repository whose name is exactly $name
 */
function getMatchingRepo($repos, $name) {
	foreach($repos as $repo) {
		if($repo->getName() == $name) return $repo;
	}

	return NULL;
}

/**
 * Strips # from an issue number if present
 * @param  String $number #-prefixed issue number
 * @return String         Issue number
 */
function issueNumber($number) {
	if($number[0] == '#')
		return substr($number, 1);
	else
		return $number;
}

/**
 * Performs the action described by $arg
 * @param  StdClass $arg Object describing what to do : repo, user, action, ...
 */
function run($arg) {
	switch($arg->act) {
		case 'showRepos':
			$formattedRepos = array();
			foreach($arg->repos as $repo) {
				$formattedRepos[] = $repo->display();
			}

			echo json_encode($formattedRepos);
			break;

		case 'showActions':
			if(is_string($arg->repo)) {
				if(is_string($arg->user)) {
					$arg->user = new User($arg->user);
				}
				$arg->repo = $arg->user->getRepo($arg->repo);
			}

			echo json_encode($arg->repo->showActions());
			break;

		case 'showIssue':
			if(is_scalar($arg->issue)) {
				if(is_string($arg->repo)) {
					if(is_string($arg->user)) {
						$arg->user = new User($arg->user);
					}
					$repos = $arg->user->getReposNamed($arg->repo);
					if(count($repos) == 1) $arg->repo = $repos[0];
					else $arg->repo = getMatchingRepo($repos, $arg->repo);
				}
				$arg->issue = $arg->repo->getIssue(issueNumber($arg->issue));
			}

			$output = array($arg->issue->display(true));
			foreach ($arg->issue->getComments() as $comment) {
				$output[] = $comment->display();
			}

			echo json_encode($output);
			break;

		case 'issues':
			if(is_string($arg->repo)) {
				if(is_string($arg->user)) {
					$arg->user = new User($arg->user);
				}
				$repos = $arg->user->getReposNamed($arg->repo);
				if(count($repos) == 1) $arg->repo = $repos[0];
				else $arg->repo = getMatchingRepo($repos, $arg->repo);
			}
			echo json_encode($arg->repo->showIssues());
			break;

		case 'pulls':
			if(is_string($arg->repo)) {
				if(is_string($arg->user)) {
					$arg->user = new User($arg->user);
				}
				$repos = $arg->user->getReposNamed($arg->repo);
				if(count($repos) == 1) $arg->repo = $repos[0];
				else $arg->repo = getMatchingRepo($repos, $arg->repo);
			}
			echo json_encode($arg->repo->showPullRequests());
			break;

		case 'branches':
			if(is_string($arg->repo)) {
				if(is_string($arg->user)) {
					$arg->user = new User($arg->user);
				}
				$repos = $arg->user->getReposNamed($arg->repo);
				if(count($repos) == 1) $arg->repo = $repos[0];
				else $arg->repo = getMatchingRepo($repos, $arg->repo);
			}
			echo json_encode($arg->repo->showBranches());
			break;

		case 'noaction':
			$output = array(
				'title' => 'Go to GitHub',
				'subtitle' => 'http://github.com',
				'icon' => 'github.png',
				'url' => 'http://github.com'
			);

			echo json_encode($output);
			break;

		default:
			$existingActions = array(
				"issues",
				"pulls",
				"branches"
			);

			$matching = array();

			foreach($existingActions as $action) {
				if(stripos($action, $arg->act) !== false) $matching[] = $action;
			}

			if(count($matching) == 1) {
				$arg->act = $matching[0];
				run($arg);
			} else {
				$output = array();

				$output[] = array(
					'title' => "Did you mean..."
				);

				foreach($matching as $match) {
					$output[] = array(
						'title' => $match,
						'icon' => $match . '.png',
						'action' => 'default.php',
						'user' => $arg->user->getName(),
						'repo' => $arg->repo->getName(),
						'act' => $match,
						'actionReturnsItems' => true
					);
				}

				echo json_encode($output);
			}

			break;
	}
}

/**
 * Step 1 : Parse the input
 */
// The first element of $argv is the path to this script, we can drop this.
array_shift($argv);
$arg = json_decode($argv[0]);

// If the received argument was not JSON, construct an object describing the action
if($arg == NULL || !isset($arg->act)) {
	$parts = array_filter(array_map("trim", preg_split("#[/\s]+#", $argv[0])), "notEmpty"); // Split at slashes and spaces
	$arg = new stdClass;
	switch(count($parts)) {
		// Input is either an !action or a github user name
		case 1:
			if($parts[0][0] == '!') {
				switch(substr($parts[0], 1)) {
					case 'logout':
						HttpClient::getInstance()->updateToken("");

						echo json_encode(array(
							'title' => 'The token has been erased'
						));
						break;

					case 'empty':
						HttpClient::getInstance()->emptyCache();

						echo json_encode(array(
							'title' => 'The cache has been erased'
						));
						break;

					case 'update':
						HttpClient::getInstance()->updateDB();

						echo json_encode(array(
							'title' => 'The database has been updated.'
						));
						break;

					default:
						echo json_encode(array(
							'title' => 'Unknown action'
						));
						break;
				}

				exit(0);
			} else {
				$arg->user = new User($parts[0]);
				$arg->repos = $arg->user->getRepos();
				$arg->act = "showRepos";
			}
			break;

		// Input is either !login token or username reponame
		case 2:
			if($parts[0] == "!login") {

				HTTPClient::getInstance()->updateToken($parts[1]);

				echo json_encode(array(
					'title' => 'The authentication token has been registered !'
				));

				exit(0);

			} else {

				$arg->user = new User($parts[0]);
				$foundRepos = $arg->user->getReposNamed($parts[1]);

				switch(count($foundRepos)) {
					case 0:
						echo json_encode(array(
							'title' => 'No such repository !',
						));
						$arg->act = "noaction";
						break;

					case 1:
						$arg->repo = $foundRepos[0];
						$arg->act = "showActions";
						break;

					default:
						$arg->repos = $foundRepos;
						$arg->act = "showRepos";
						break;
				}
			}

			break;

		// Input is either username reponame action or username reponame #issue
		case 3:
			$arg->user = new User($parts[0]);
			$arg->repo = $arg->user->getRepo($parts[1]);

			if($parts[2][0] == "#") {
				$arg->issue = $arg->repo->getIssue(issueNumber($parts[2]));
				$arg->act = "showIssue";
			} else {
				$arg->act = $parts[2];
			}
			break;

		case 0:
		default:
			$arg->act = "noaction";
			break;
	}
}

/**
 * Step 2 : Run this !
 */
run($arg);

?>