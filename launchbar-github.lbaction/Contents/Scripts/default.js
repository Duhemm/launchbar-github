var timeout = 5.0;
var cacheTime = 60 * 60 * 1000; // One hour

var issuesKey = "issues";
var directIssuePattern = /#\d+/;
var pullRequestsKey = "pulls";
var branchesKey = "branches";

var hostname = "https://api.github.com/";

function empty(string) {
  return !string || string.length === 0;
}

/**
 * Called when action receives string arguments
 * @param  String string String passed to the action.
 * @return Array         Returns the list of elements matching the query
 */
function runWithString(string) {

  if(empty(string)) {
    return [];
  }

  var parts = string.split(/[\s\/]/).filter(function (str) { return !empty(str); } );

  switch(parts.length) {
    case 1:
      return displayRepos(getRepos(parts[0]));

    case 2:
      var repos = getRepos(parts[0]).filter(function (repo) {
        return repo.name.indexOf(parts[1]) > -1;
      });

      return displayRepos(repos);

    case 3:
      switch(true) {
        case issuesKey.indexOf(parts[2]) > -1:
          return displayIssues(parts[0], parts[1]);

        case parts[2].match(directIssuePattern) != null:
          return displaySingleIssue(parts[0], parts[1], parts[2]);

        case pullRequestsKey.indexOf(parts[2]) > -1:
          return displayPullRequests(parts[0], parts[1]);

        case branchesKey.indexOf(parts[2]) > -1:
          return displayBranches(parts[0], parts[1]);

        default:
          return showActions(getRepo(parts[0], parts[1]));
      }
  }
}

/**
 * Returns the hexadecimal representation of `str`
 * @param  String str String to encode
 * @return String     Hexadecimal equivalent of `str`
 */
function stringToHexadecimal(str) {
  var hex = "";
  for(var i = 0; i < str.length; i++) {
    hex += str.charCodeAt(i).toString(16);
  }
  return hex;
}

/**
 * Downloads and parses JSON
 * @param  String url URL to load
 * @return Array      List of objects that have been downloaded
 */
function get(url) {

  var hashedURL = stringToHexadecimal(url);
  var cachedResultPath = Action.cachePath + "/" + hashedURL;

  if(File.exists(cachedResultPath)) {
    var cachedResult = File.readJSON(cachedResultPath);

    if(((new Date) - new Date(cachedResult.date)) < cacheTime)
      return cachedResult.results;
  }

  var result = HTTP.getJSON(url, timeout);

  if(result.data != undefined) {
    File.writeJSON({
      'date': (new Date).toJSON(),
      'results': result.data
    }, cachedResultPath);

    return result.data;
  }
  else if(result.response.status === 404)
    return [];
  else if(result.error != undefined) {
    LaunchBar.alert("Unable to load url " + url, result.error);
    return [];
  }
}

/**
 * Downloads the list of repositories of `user` on GitHub
 * @param  String user GitHub ID of the user
 * @return Array       List of repositories of `user`
 */
function getRepos(user) {
  return get(hostname + 'users/' + encodeURIComponent(user) + "/repos");
}

/**
 * Gets the information about a single repository
 * @param  String user Owner of the repository
 * @param  String repo Repository we're interested in
 * @return Object      Repository named `repo`
 */
function getRepo(user, repo) {
  var found = getRepos(user).filter(function (rep) {
    return rep.name == repo;
  });

  if(found.length == 0)
    return [];
  else
    return found[0];
}

/**
 * Formats the list of repositories `repos` to display in LaunchBar
 * @param  Array repos List of repos to format
 * @return Array       Formatted list of repos
 */
function displayRepos(repos) {
  return repos.map(function (repo) {
    return {
      'title': repo.full_name,
      'subtitle': repo.description,
      'icon': 'repo.png',
      'action': "runWithString",
      'actionArgument': repo.full_name + " actions",
      'actionReturnsItems': true
    };
  });
}

/**
 * Lists the actions that can be made over a repository
 * @param  Object repo Repository impacted by the action
 * @return Array       Array of actions
 */
function showActions(repo) {
  var actions = [
    {
      'title': "View repository on GitHub",
      'subtitle': repo.html_url,
      'icon': 'github.png',
      'url': repo.html_url
    }
  ];

  if(repo.has_issues) {
    actions.push({
      'title': "Issues",
      'icon': 'issues.png',
      'action': "runWithString",
      'actionArgument': repo.full_name + " " + issuesKey,
      'actionReturnsItems': true
    });
  }

  actions.push({
      'title': "Pull requests",
      'icon': 'pullrequests.png',
      'action': "runWithString",
      'actionArgument': repo.full_name + " " + pullRequestsKey,
      'actionReturnsItems': true
    });

  actions.push({
      'title': "Branches",
      'icon': 'branches.png',
      'action': "runWithString",
      'actionArgument': repo.full_name + " " + branchesKey,
      'actionReturnsItems': true
  })

  actions.push({
      'title': "Pulse",
      'subtitle': repo.html_url + "/pulse",
      'icon': 'pulse.png',
      'url': repo.html_url + "/pulse"
    });

  actions.push({
      'title': "Graphs",
      'subtitle': repo.html_url + "/graphs",
      'icon': 'graphs.png',
      'url': repo.html_url + "/graphs"
    });

  actions.push({
      'title': "Network",
      'subtitle': repo.html_url + "/network",
      'icon': 'merged.png',
      'url': repo.html_url + "/network"
    });

  if(repo.has_wiki) {
    actions.push({
      'title': "Repository wiki",
      'subtitle': repo.html_url + "/wiki",
      'icon': 'wiki.png',
      'url': repo.html_url + "/wiki"
    });
  }

  return actions;
}

/**
 * Downloads the issues from the repo `user/repo`
 * @param  String user Owner of the repo
 * @param  String repo Name of the repo
 * @return Array       List of issues of this repo
 */
function getIssues(user, repo) {
  var encodedPath = encodeURIComponent(user) + "/" + encodeURIComponent(repo);
  return get(hostname + "repos/" + encodedPath + "/issues?state=all", timeout);
}

/**
 * Formats the list of issues
 * @param  Array issues List of issues
 * @return Array        Formatted list of issues
 */
function displayIssues(user, repo) {
  var issues = getIssues(user, repo);

  return issues.map(function (issue) {
    return {
      'title': "#" + issue.number + ": " + issue.title,
      'subtitle': issue.body,
      'icon': issue.state == "open" ? "open.png" : "closed.png",
      'action': "runWithString",
      'actionArgument': user + " " + repo + " #" + issue.number,
      'actionReturnsItems': true
    }
  });
}

/**
 * Downloads the list of pull requests from the repo `user/repo`
 * @param  String user Owner of the repo
 * @param  String repo Name of the repo
 * @return Array       List of pull requests for this repo
 */
function getPullRequests(user, repo) {
  var encodedPath = encodeURIComponent(user) + "/" + encodeURIComponent(repo);
  return get(hostname + "repos/" + encodedPath + "/pulls?state=all", timeout);
}

/**
 * Formats the list of pull requests
 * @param  Array pulls List of pull requests
 * @return Array       Formatted list of pull requests
 */
function displayPullRequests(user, repo) {
  var pulls = getPullRequests(user, repo);

  return pulls.map(function (pull) {
    return {
      'title': "#" + pull.number + ": " + pull.title,
      'subtitle': pull.body,
      'icon': pull.state == "open" ? "not-merged.png" : "merged.png",
      'action': "runWithString",
      'actionArgument': user + " " + repo + " #" + pull.number,
      'actionReturnsItems': true
    }
  })
}

/**
 * Gets the comments on `issue` in `user`/`repo`
 * @param  String user  Owner of the repo
 * @param  String repo  Name of the repo
 * @param  String issue Number of the issue
 * @return Array        List of comments on the issue
 */
function getIssueComments(user, repo, issue) {
  var encodedPath = encodeURIComponent(user) + "/" + encodeURIComponent(repo);
  return get(hostname + "repos/" + encodedPath + "/issues/" + issue + "/comments");
}

/**
 * Gets a single issue
 * @param  String user  Owner of the repo
 * @param  String repo  Name of the repo
 * @param  String issue Number of the issue
 * @return Object       Issue
 */
function getSingleIssue(user, repo, issue) {
  var encodedPath = encodeURIComponent(user) + "/" + encodeURIComponent(repo);
  return get(hostname + "repos/" + encodedPath + "/issues/" + issue)
}

/**
 * Formats a comment for display in LaunchBar
 * @param  Object comment Comment to display
 * @return Object         Formatted comment
 */
function formatComment(comment) {
  return {
    'title': comment.body,
    'subtitle': "By " + comment.user.login + " " + new Date(comment.updated_at),
    'icon': 'comment.png',
    'url': comment.html_url
  };
}

/**
 * Gets and formats an issue for display in LaunchBar
 * @param  String user    Owner of the repo
 * @param  String repo    Name of the repo
 * @param  String issueID Number of the repo
 * @return Array          List whose head is the issue, then the comments.
 */
function displaySingleIssue(user, repo, issueID) {
  issueID = issueID.substr(1); // Strip # sign
  var issue = getSingleIssue(user, repo, issueID);
  var comments = getIssueComments(user, repo, issueID);

  var formattedIssue = {
    'title': "#" + issue.number + ": " + issue.title,
    'subtitle': issue.body,
    'icon': issue.state == "open" ? "open.png" : "closed.png",
    'url': issue.html_url
  }

  return [formattedIssue].concat(comments.map(formatComment));
}

/**
 * Gets the branches from a repo
 * @param  String user Owner of the repo
 * @param  String repo Name of the repo
 * @return Array       List of branches
 */
function getBranches(user, repo) {
  var encodedPath = encodeURIComponent(user) + "/" + encodeURIComponent(repo);
  return get(hostname + "repos/" + encodedPath + "/branches");
}

/**
 * Gets and formats the list of branches from a repo
 * @param  String user Owner of the repo
 * @param  String repo Name of the repo
 * @return Array       List of branches
 */
function displayBranches(user, repo) {
  var branches = getBranches(user, repo);
  var repo = getRepo(user, repo);

  return branches.map(function (branch) {
    return {
      'title': branch.name,
      'subtitle': "Last commit : " + branch.commit.sha,
      'icon': 'not-merged.png',
      'url': repo.html_url + "/tree/" + branch.name
    };
  });
}