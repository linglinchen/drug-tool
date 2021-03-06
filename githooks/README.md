# Git Hooks

The scripts in this directory can be used to make Git automatically perform tasks after you take actions. These should only be expected to work 100% when run within Linux, MacOS, or Git Bash. In order to activate them, symlink or replace the recommended hooks in **.git/hooks**.

- **build.sh** - This script automatically installs rebuilds and migrates the API.  *For use in the local environment.*  Recommended hooks: **post-checkout**, **post-merge**
- **buildProd.sh** - This script automatically installs rebuilds and migrates the API.  Uses SCL.  *For use in the production environment.*  Recommended hooks: **post-checkout**, **post-merge**

# More Information

If you're unfamiliar with Git hooks, read the documentation.

- [Official githooks Documentation](https://git-scm.com/docs/githooks)
- [Pro Git - Git Hooks](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)