# objective-wp CLI

# Installation

```bash
composer global require "objective-wp/cli":dev-master
```
# Usage

## `init`

Clones and composer installs a objective-wp project from github. The whole path may be
given `<author>/<repo>` or just the `<repo>` if `set-org` has been ran. 

If `init` is ran inside a directory with a `wp-content` folder, owp will automatically install it to 
the `themes` directory or the plugins keyword if the repo contains the `plugin` keyword.

```
owp init <author>/<repo>
```

## `set-org` 

Namespace all init commands with an organization or author.

```
owp set-org <organization>
```