# objective-wp CLI

# Installation

```bash
composer global require "objective-wp/cli":dev-master
```
# Usage

## `init`

Clones and composer installs a objective-wp project from github. The whole path may be
given <author>/<repo>. 

```
owp init <author>/<repo>
```

## `set-org` 

Namespace all init commands with an organization or author.

```
owp set-org <organization>
```