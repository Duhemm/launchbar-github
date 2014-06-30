# GitHub action for LaunchBar 6

**Authentication is now supported !** (See below)

![Overview](http://s24.postimg.org/z49peix7p/Screen_Shot_2014_06_14_at_00_06_20.png)

## How to install ?

 1. Clone this repository or downloads its content
 2. Place `launchbar-github.lbaction` in `~/Library/Application Support/LaunchBar/Actions`
 3. Type `github` in LaunchBar

## How to authenticate ?

 1. Create a [personnal access token](https://github.com/settings/applications). Name it "GitHub for LaunchBar" and leave the default permissions.
 2. Copy the personnal access token in your clipboard, and type in the GitHub action for LaunchBar : `!login <paste-your-token>`
 3. You're authenticated ! If everything went well, you should see all your repos by typing `my` in GitHub action for LaunchBar.

To log out, simply type `!logout`.

## What's supported ?

 * Authentication !

 * Get the repositories of a user  
   ![Get the repositories](http://s28.postimg.org/6qyjk2zzx/Screen_Shot_2014_06_14_at_00_05_42.png)

 * List the issues from a repository  
   ![List the issues](http://s27.postimg.org/dldccgg6b/Screen_Shot_2014_06_14_at_00_06_32.png)

 * Display the discussion about an issue or a pull request  
   ![Discussion about an issue](http://s27.postimg.org/9sttwq0o3/Screen_Shot_2014_06_14_at_00_09_27.png)

 * Show the pull requests for a repository  
   ![Pull requests](http://s17.postimg.org/61hyx9y9r/Screen_Shot_2014_06_14_at_00_10_18.png)

 * Show the branches of a repository  
   ![Branches](http://s28.postimg.org/n4rpupuwt/Screen_Shot_2014_06_14_at_00_11_29.png)

## Comprehensive list of supported inputs :

Please note that `username repo` and `username/repo` are equivalent.

 * `!login <your-access-token>` will register the token so that it is used for all queries.
 * `!logout` will remove the token. All subsequent queries won't use it.
 * `!empty` will empty the cache.
 * `my` will list your repositories.
 * `my repo` will list all your repositories whose name contain `repo` or actions related to `repo`.
 * `username` will display all repositories from `username`.
 * `username repo` will list all repostories from `username` whose name contain `repo` or actions related to `repo`.
 * `username repo i[ssues]` will display issues from `username/repo` (can be shortened).
 * `username repo p[ulls]` will display pull requests from `username/repo` (can be shortened).
 * `username repo b[ranches]` will display branches from `username/repo` (can be shortened).
 * `username repo #XYZ` will display the discussion about issue `#XYZ`.

## Thanks to...

Thanks to [Stephen Hutchings](http://typicons.com) for the very nice icons !

