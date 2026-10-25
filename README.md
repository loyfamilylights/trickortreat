# fpp-plugin-trickortreat — Trick-or-Treat News 🎃

A Halloween fork of [fpp-plugin-naughtynice](https://github.com/jjarboe01/fpp-plugin-naughtynice).
Instead of "BREAKING NEWS: Emma is on Santa's NICE list", your matrix runs a
**SPOOKY NEWS** broadcast:

- a dripping-slime **SPOOKY NEWS** header (text is configurable)
- the trick-or-treater's photo, **or** a costume scene when there's no photo
  (ghost, jack-o'-lantern, witch, bat, skull, black cat)
- a purple name bar with an orange **TREAT!** or slime-green **TRICK!** badge
- a scrolling ticker: *"BREAKING BOOS: Emma the Witch has earned a full bag of TREATS!"*
  (several wordings for each verdict, picked at random)

## The big difference: no cloud needed

The NaughtyNice plugin gets its submissions from the NaughtyNice Cloud
service. This one has its own **Door page**: a phone-friendly form served by
the FPP box itself. Whoever is handing out candy opens it on a phone on the
same Wi-Fi, types a name, optionally taps a costume or snaps a photo, and hits
**TREAT** or **TRICK**. It's on the matrix a couple of seconds later.

- Entries go into a local queue (`config/local_queue.json`) and play one at a
  time. Each holds the screen for the "hold" time (default 20 s) before the
  next one starts, so a burst of kids doesn't cut each other off.
- The queue is capped at 25 so a runaway phone can't back the show up all night.
- Photos are shrunk to 480 px in the phone's browser before upload (tiny files,
  no PHP upload-size problems), then deleted once they've been shown.

**Cloud is optional.** If you have a submissions service that speaks the
NaughtyNice Cloud `/api/v1` protocol, enter its URL and token under *Cloud
service (optional)* and the daemon polls it too. It accepts `treat`/`trick`,
and maps `nice`→TREAT and `naughty`→TRICK. An optional `costume` field on each
item is used for the art and ticker text. Without one, the original `gender`
field just picks a costume scene.

## Install

1. Publish this directory as the root of your `fpp-plugin-trickortreat` repository and verify the three URLs in `pluginInfo.json` point to your repository.
2. FPP → Content Setup → Plugin Manager → paste the **raw** pluginInfo.json URL:
   `https://raw.githubusercontent.com/loyfamilylights/trickortreat/main/pluginInfo.json`
   (The plain `.git` URL doesn't work. See the original plugin's README for why.)
   *Or* copy the folder to `/home/fpp/media/plugins/fpp-plugin-trickortreat`
   over SSH and run `sudo scripts/fpp_install.sh` from inside it.
3. Go to **Content Setup → Trick-or-Treat - Setup** and click
   **Restart FPPD (full)**. Use that button, not FPP's own Restart: FPP's own
   button does a *quick* restart that never starts plugin daemons.
4. Click **Send to matrix** under *Test fire*. You should see "Test Ghoul the
   Witch" come up.
5. Open the **Door page** link shown at the top of the setup page on your
   phone and bookmark it / add it to the home screen.

Install automatically creates (or reuses) the `PhotoZone`/`TickerZone` Pixel
Overlay Models and a `spooky_news` playlist. **If you already run the
NaughtyNice plugin**, both plugins share the same PhotoZone/TickerZone, so the
Halloween plugin just reuses your existing zones. Each plugin has its own
playlist, display script and image file, so they won't clobber each other.
Disable whichever one is out of season (*Plugin enabled* checkbox).

Make the show your own: add a spooky sound effect or a lead-in sequence to the
`spooky_news` playlist. Just keep the `tot_display_image.py` script entry,
which is what puts the picture up. You can also replace any of the costume
scenes in `static/silhouettes/*.png` with your own 192×140 art.

## What carried over unchanged from NaughtyNice

All of the hard-won FPP plumbing is kept as-is, with only names and paths changed:

- `fpp_provision.py`: zone auto-sizing, the **GET-full-list → POST-full-list**
  model update (POST `/api/models` replaces the whole file, which once wiped a
  real matrix model), `--dry-run`, and `--force-recreate`
- display via a **script** playlist entry (FPP 9.5.3's native image entry hangs)
- the display script uses **ffmpeg, not Pillow**, because FPP runs playlist
  `.py` scripts under bare system python3 with no venv
- `break_in_and_sync` so the ticker doesn't start before the picture
- venv install, `chown fpp:fpp` after root-run install, and the PID-file lifecycle

## What's new or changed

| | |
|---|---|
| `door.php` | Phone page (menu: *Trick-or-Treat - Door*, served bare with `nopage=1`) |
| `daemon/halloween_art.py` | Procedural costume scenes and slime drips |
| `daemon/tot_daemon.py` | Drains the local queue first, then polls the cloud (if set) on its own interval. Waits out each display before starting the next |
| `daemon/config.py` / `lib/config.php` | Local queue with `flock()` shared by PHP and Python. Cloud URLs default to blank |
| `content.php` | Test fire, queue view/clear, header text, hold time, ticker-only option |
| `tools/make_silhouettes.py` | Re-exports the default costume PNGs |

## Files it creates outside its own folder

Left in place on uninstall on purpose, same as NaughtyNice:
`PhotoZone`/`TickerZone` models (possibly shared), the `spooky_news` playlist,
and `/home/fpp/media/scripts/tot_display_image.py`.

## Testing so far

Tested in a dev container against mock FPP and mock cloud HTTP servers:

- zone provisioning (dry run and real, existing model preserved)
- door-page form submit with a photo in headless Chromium at phone size
- PHP→Python queue hand-off, local and cloud delivery, spacing between items
- nice/naughty mapping, ack, telemetry
- the display script's ffmpeg crop
- PHP lint and a clean SIGTERM shutdown

**Not yet run on a real FPP box.** Do a test fire before Halloween night.


## Installation, upgrades and safe cleanup

- Install and upgrade use the same idempotent dependency verification path. Python virtual environment and required imports must succeed; failures stop installation with a clear error.
- Existing `config/` settings, saved queue, uploads, user-created FPP playlists and overlay models are never intentionally reset or deleted. Zone provisioning is an explicit action from the plugin setup page.
- Photo-zone rendering requires a working `ffmpeg` executable. When FFmpeg is absent the daemon falls back to ticker-only delivery without overwriting the saved photo preference.
- The shared FPP script `tot_display_image.py` is only overwritten when unchanged and plugin-owned (tracked by SHA-256 in `config/shared-script.sha256`). Uninstall only removes the unmodified, owned shared script; user edits are preserved.
- The daemon is only stopped when a PID file points to a process running `tot_daemon.py` from this plugin's own daemon working directory.
- Runtime logs use FPP's managed `plugin-fpp-plugin-trickortreat.log` under `LOGDIR` (default `/home/fpp/media/logs`). FPP handles rotation.
- Backup FPP and plugin settings before upgrades or removal. FPP Plugin Manager may remove the plugin directory on uninstall, including its private `config/` state.


## Visitor greeting flow

On the local **Door** page, a visitor enters their first name, chooses a costume
(or types one), optionally snaps/uploads a photo (if photo display is enabled),
and presses **SHOW MY HALLOWEEN GREETING!**. The entry is queued for the FPP
matrix ticker, which displays `WELCOME EMILY! HAPPY HALLOWEEN!` for a visitor
named Emily. Costume artwork and photo handling still use the existing display
settings. Visitors must have network access to the FPP web interface; avoid
exposing the controller directly to the public internet. Get guardian consent
before taking or displaying children's photographs.


## Repair release notes (2026-10-25)

- Separate TREAT and TRICK on door page; the submitted verdict is validated server-side.
- Post/redirect/get prevents accidental duplicate browser-refresh submissions.
- Sanitize HTML-like text before queueing and FPP ticker/image rendering.
- Fail closed on malformed FPP model-list replies and preserve the complete existing model list in every model POST.
- Preserve original six uploaded costume PNGs without regeneration.
- Count only successful displayed items in statistics and do not start a stale photo playlist in ticker-only mode.
- Bound remote cloud photo transfers to 2 MB; check daemon child after startup.
- Includes local test suite: `python3 -m unittest discover -s tests -v`.

**Deployment:** Back up your existing plugin `config/`, FPP playlists and pixel-overlay models first. Replace plugin source with this release, retain existing `config/`, install/upgrade using FPP Plugin Manager or `scripts/fpp_upgrade.sh`, then use the plugin setup page to run a **dry-run** of zone provisioning. Perform any actual zone writes only after inspecting the proposed model list. Restart FPPD (full) and test one TREAT and one TRICK. The automated tests do not verify behavior on physical FPP hardware.

**Security:** The door form is intended only for a trusted local Wi-Fi network. Do not expose FPP or the door form to the public internet. Ask a guardian before photographing children. Administrative FPP pages should remain access-restricted.
