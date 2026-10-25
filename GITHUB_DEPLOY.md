# GitHub and FPP deployment

This archive is a **complete repository tree** for `loyfamilylights/trickortreat`, not a one-file installer. Its `daemon/`, `lib/`, `scripts/`, and `static/` folders must be committed to the root of the GitHub repository.

## On a Windows computer, with Git installed

```powershell
git clone https://github.com/loyfamilylights/trickortreat.git
cd trickortreat
# Extract the contents of the GitHub-ready ZIP into this folder (same level as README.md).
# Do NOT delete .git or any local runtime config/ directory.
git status --short
git add README.md about.php content.php door.php lib daemon scripts static tests tools status.php menu.inc pluginInfo.json .gitignore GITHUB_DEPLOY.md
git commit -m "Restore complete Trick-or-Treat FPP backend and repairs"
git push origin main
```

Or apply `trickortreat-github-ready.patch` from the clone root with `git apply --check <path-to-patch>` followed by `git apply <path-to-patch>`, then commit and push. The patch was built against the *uploaded original root-level source*, so `git apply --check` may fail if the online repository differs.

## Verify GitHub contains the backend

The following paths **must be visible** on the main branch:

- `daemon/requirements.txt`
- `daemon/tot_daemon.py`
- `lib/config.php`
- `scripts/fpp_install.sh`
- `static/silhouettes/pumpkin.png`

Avoid committing `config/settings.json`, `config/local_queue.json`, `config/uploads/`, `venv/`, or any credentials and visitor photos.

## FPP upgrade

Back up the *installed* plugin `config/` directory and FPP overlays/playlists first. After pushing to main, use FPP Plugin Manager to **update the plugin**, not uninstall it. Verify `daemon/requirements.txt` and `lib/config.php` exist. Run the FPP-provided upgrade lifecycle or `sudo bash scripts/fpp_upgrade.sh` from the plugin directory if necessary. In the plugin setup page, run zone provisioning **dry run** first; do not choose Full reset. Use **Restart FPPD (full)** to start the daemon. Check `pgrep -af tot_daemon.py` and the managed plugin log.

## Tests and limitations

Python/PHP/shell syntax checks and packaged regression tests can be run on a compatible development system. This package has **not** been end-to-end tested on your physical FPP 10.3 controller; live playlist and overlay operation still needs verification.
