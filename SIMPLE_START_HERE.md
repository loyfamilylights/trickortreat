# Trick-or-Treat — SIMPLE Master Controls (FPP 10.3)

This is a manual two-button front end for the existing **working** command presets on Master. It deliberately does not use a Python daemon, queue, photo processing, sequence triggering, playlist, virtual matrix channel writes, or provisioning.

- Master: `192.168.1.192` (plugin installed here only)
- Remote: `192.168.1.113`, Snow-Globe FPP 10.3, Remote mode
- Projector: `VirtualMatrix`, 224x126, HDMI-A-1. Leave it alone.

## Prerequisite on Master

Under **Status/Control → Command Presets**, confirm that both *locally saved on Master* presets work using **Run Now**:

- `treat`: **Overlay Model Effect**, Text `TREAT!`, MultiSync on, **only** host `192.168.1.113`, model `VirtualMatrix`.
- `TRICK`: **Overlay Model Effect**, Text `TRICK!`, MultiSync on, **only** host `192.168.1.113`, model `VirtualMatrix`.

Names are case-sensitive in the simple page. If your working Master presets use different names, edit only the two values in the `$presets` map at the top of `simple.php`.

## Install/update

Back up your installed plugin folder and FPP settings first. Put the **contents of the `trickortreat/` directory** in the repository plugin root, commit, and use the FPP Plugin Manager update feature on Master. Do not install this plugin on Snow-Globe. Existing data and configuration files are left untouched. No daemon will be launched on startup. If an old daemon is still running, stop it separately during idle time (it is not needed by SIMPLE).

Open **Content Setup → Trick-or-Treat - Simple Controls** on the Master, or use `http://192.168.1.192/plugin.php?plugin=fpp-plugin-trickortreat&page=simple.php`.

Click **TREAT!**, then **TRICK!** while your show is stopped. The buttons call Master FPP's localhost API to execute the saved preset names; Master performs the already-proven direct MultiSync overlay command.

## Troubleshooting

- If the buttons say 'FPP did not confirm', check the FPPD status and that the two preset names exist on Master.
- If the buttons say 'Sent' but the projector stays blank, test the same preset using **Run Now** on Master. 'Sent' only means FPP accepted the request.
- Don't change the HDMI Virtual Matrix, channel numbers, playlist, or MultiSync.
- The older setup/daemon/door pages remain in the source for backup compatibility, but are not shown in the new plugin menu and are **not part of SIMPLE mode**.

## Safety

The simple page calls only the local FPP API with two fixed preset names; it does not accept arbitrary command input. Like the original FPP UI, it should be available only on a trusted local network. It is not a public internet-facing visitor page.
