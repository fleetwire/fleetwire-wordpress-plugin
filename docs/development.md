# Development notes

Developer-only notes moved out of `readme.txt` in 1.1.1. `release.sh` strips `docs/` and `tests/`, so none of this ships to WordPress.org.

## Checks

Run these against a local WordPress install with the plugin active:

- `php tests/blocks.php /path/to/wordpress` — block registration, block and shortcode markup parity, escaping and the editor listings endpoint.
- `php tests/patterns.php /path/to/wordpress` — pattern registration and markup.
- `php tests/site-builder.php /path/to/wordpress` — Build my rental site. Run it on a block theme (Twenty Twenty-Five) and a classic theme; the no-title template and page-list menu checks only apply on block themes.
- `php tests/playground/seed-playground.php /path/to/wordpress` — builds a page showing every shortcode and option rendered live.

The PHP tests must delete only what they create. Snapshot pages, menus and options before and after a run if you change their cleanup.

Run `python3 tests/security-regressions.py` with PHP, Bash, Subversion 1.9+ and svnadmin installed. The tests use dummy credentials, mock every release publication command (`svn`, `git`, `gh`), and use only a local SVN fixture and a temporary localhost HTTP server. They never publish a release or contact GitHub or WordPress.org.

## Releasing

`release.sh` needs `git`, `svn` 1.9+, `gh`, `awk`, `grep`, `sed` and `xargs`, plus a `.env` with `GITHUB_ACCESS_TOKEN`, `PLUGIN_SLUG`, `GITHUB_REPO_OWNER`, `GITHUB_REPO_NAME`, `SVN_USERNAME` and `SVN_PASSWORD`. It commits to WordPress.org SVN first, then creates the GitHub release with `gh release create`, using that version's readme changelog as the notes and the `.env` token as `GH_TOKEN`. A failed GitHub release only prints a warning, since WordPress.org is already released by then.

## Diagnostic logs

Fleetwire keeps diagnostic files outside the WordPress web roots in a site-specific system temporary directory. Unix directory and file permissions are restricted to the PHP worker. System cleanup may remove temporary logs. For persistent storage, define `FLEETWIRE_LOG_DIR` in wp-config.php as an absolute private directory outside all web-served paths, writable only by the PHP worker.

On upgrade, existing `uploads/fleetwire-logs/fleetwire.log` records are copied to private storage before the public file is emptied and removed. If storage or migration is unavailable, Fleetwire reports the problem through the host-managed PHP error log; remove any remaining legacy upload log from public access and configure private storage. Keep the host's PHP error-log destination private as well. Logging level filters and the log-file, size and clear helpers remain available; the file helper returns an empty string when private storage cannot be used.
