# Snow-Globe FPP 10.3 HDMI projector mode (224×126)

This variant adds an opt-in **HDMI VirtualMatrix 224x126** display mode.
It uses the EXISTING channel model `VirtualMatrix` (start channel 1,
84,672 RGB channels), **not** the `FB - HDMI-A-1` framebuffer model.
Neither output configuration nor FPP model-overlays.json is edited by this mode.

## Setup

1. Back up the original plugin directory and FPP settings before installing.
2. Install/update this plugin using your normal FPP plugin workflow. Verify
   your playlist `spooky_news` has a **script** entry named
   `tot_display_image.py`. The install script may run provisioning on first
   install; do NOT allow zone creation to proceed unchecked: it was designed
   for separate physical zones. Existing installations should retain playlists.
3. In **Content Setup → Trick-or-Treat → Advanced**, choose
   **Snow-Globe HDMI VirtualMatrix 224x126**, and save.
4. Verify the resulting config/settings.json contains:
   `"display_mode":"virtual_matrix", "photo_model":"VirtualMatrix",
   "ticker_model":"VirtualMatrix", "matrix_width":224,
   "matrix_height":126, "photo_zone_height":126, "ticker_zone_height":0`.
5. Keep FPP Pixel Overlay model `VirtualMatrix` at start channel 1 and
   84,672 channels. Leave `FB - HDMI-A-1` and Virtuals untouched.
6. Disable FPP Display Testing and other active overlays first, then run
   the plugin's **Test Fire** (name `PUMPKIN`, verdict `TREAT`). The script
   will upload one 224×126 RGB image with name, verdict, and static ticker,
   start `spooky_news`, enable `VirtualMatrix`, then disable it after the
   configured duration.

**Important:** This mode renders the headline/ticker as static pixels, not
animated scrolling text. Independent text effects on `VirtualMatrix` can
race with the image writer. The compositor intentionally avoids them.

**Remote mode:** Starting a playlist can interrupt synchronized playback.
Test only while the show is idle. Mode does not change FPP player mode,
output channels, xLights configuration, or other auto-created models.

**Failure modes:** If text effects work but the image stays blank, look at
FPP logs for `tot_display_image.py` and check `ffmpeg` is installed,
`/dev/shm/FPP-Model-Overlay-Buffer-VirtualMatrix` is available, and that
`spooky_news` actually runs the image script. The overlay buffer is written
at RGB offset 12, as in this plugin's original implementation; confirm
compatibility on FPP 10.3 before using live. If image colors look scrambled,
stop and disable the overlay instead of adjusting live output geometry.
