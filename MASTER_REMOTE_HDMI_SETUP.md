# Trick-or-Treat Master → Snow-Globe HDMI

* Master FPP: `192.168.1.192` — install the **plugin ZIP here only**; keep the existing Halloween playlist.
* Remote FPP: `192.168.1.113` — upload only `remote/tot_remote_hdmi.py` to **File Manager → Scripts**.
* Snow-Globe outputs stay **unchanged**: `VirtualMatrix`, 224 × 126, channels 1–84672; `FB - HDMI-A-1` unchanged; MultiSync Remote mode unchanged.

## Master configuration

Open **Content Setup → Trick-or-Treat → Advanced**. Choose **MASTER → Snow-Globe 224x126**, set Remote FPP address to `http://192.168.1.113`, keep **FPP API URL** as `http://localhost`, then **Save settings**. Do not run automatic zone setup or Full reset.

**Test Fire** renders one 224×126 frame on Master, uploads it to Snow-Globe by HTTP and invokes **Run Script** on Snow-Globe. The remote helper writes the image directly into the existing overlay buffer; it holds it for 20 seconds and disables the overlay. The code does not change output definitions or invoke Start Playlist on either device.

The existing `spooky_news` playlist remains on Master, but **remote HDMI mode intentionally does not start it**. Existing show sequencing remains independently controlled by the Master. Verdict-specific musical triggers will require a separate opt-in configuration.

## Important limitation

This package is statically tested, **not verified against the actual devices**. Specifically, `/api/command` Run Script argument behavior and mmap buffer access should be confirmed with a test while the show is stopped. Do not treat an HTTP 200 as proof that the script finished displaying successfully.
