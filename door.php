<?php
// Phone-friendly "door" page: whoever is handing out candy types a name,
// picks a costume, optionally snaps a photo, and submits a greeting. The
// entry goes into the local queue (config/local_queue.json) and the daemon
// puts it on the matrix within a couple of seconds. No cloud needed.
//
// Registered in menu.inc with wrap=0 so FPP serves it bare (&nopage=1),
// which is why this file outputs a complete HTML document of its own.
// Open it on any phone on the same Wi-Fi as the FPP box.
require_once __DIR__ . '/lib/config.php';

$totMsg = '';
$totMsgOk = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_greeting'])) {
    $r = tot_enqueue_local($_POST['child_name'] ?? '', $_POST['submit_greeting'] ?? '', $_POST['costume'] ?? '', $_POST['photo_data'] ?? '');
    $totMsgOk = !empty($r['ok']);
    if ($totMsgOk) {
        $who = tot_h(trim((string)($_POST['child_name'] ?? '')));
        // Redirect-after-POST prevents accidental duplicate submissions on refresh.
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        $query = $_GET;
        $query['queued'] = intval($r['position']);
        header('Location: ' . $url . '?' . http_build_query($query), true, 303);
        exit;
    } else {
        $totMsg = tot_h($r['message']);
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['queued'])) {
    $position = max(1, intval($_GET['queued']));
    $totMsgOk = true;
    $totMsg = $position === 1 ? '&#127875; You are up next — watch the screen!'
        : '&#127875; Your greeting is #' . $position . ' in line.';
}
// $totMsg is HTML-safe at this point.

$totSettings = tot_load_settings();
$totQueue = tot_read_queue();
$totStatus = tot_read_status();
$totRunning = tot_daemon_running();
$last = isset($totStatus['last_item']) ? $totStatus['last_item'] : null;
$costumes = array('Ghost' => '&#128123;', 'Pumpkin' => '&#127875;', 'Witch' => '&#129497;', 'Vampire' => '&#129499;',
                  'Skeleton' => '&#128128;', 'Black Cat' => '&#128008;', 'Superhero' => '&#129464;', 'Princess' => '&#128120;');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#12001f">
<title>Trick or Treat — Door</title>
<style>
  :root { --bg:#12001f; --card:#22093a; --line:#3d1a5c; --orange:#ff7a00; --slime:#6ee628; --text:#f6ecff; --muted:#b9a5cc; }
  * { box-sizing: border-box; }
  html, body { margin:0; background:var(--bg); color:var(--text);
    font: 17px/1.4 -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  main { max-width: 480px; margin: 0 auto; padding: 18px 16px 40px; }
  h1 { font-size: 26px; margin: 4px 0 2px; color: var(--orange); letter-spacing: .5px; }
  .sub { color: var(--muted); font-size: 14px; margin-bottom: 16px; }
  .msg { padding: 12px 14px; border-radius: 10px; margin-bottom: 14px; font-weight: 600; }
  .msg.ok { background: #1f3d0c; color: #c9ff9e; }
  .msg.err { background: #4a0d18; color: #ffc2cc; }
  label { display:block; font-size: 14px; color: var(--muted); margin: 14px 0 6px; }
  input[type=text] { width:100%; font-size: 22px; padding: 14px; border-radius: 10px;
    border: 2px solid var(--line); background: var(--card); color: var(--text); outline: none; }
  input[type=text]:focus { border-color: var(--orange); }
  .chips { display:flex; flex-wrap:wrap; gap:8px; }
  .chip { border:1px solid var(--line); background: var(--card); color: var(--text); border-radius: 999px;
    padding: 8px 12px; font-size: 15px; cursor:pointer; }
  .chip.on { border-color: var(--orange); background:#3a1a05; }
  .photo { display:flex; align-items:center; gap:12px; }
  .photo label.btn { margin:0; display:inline-block; padding: 12px 14px; border-radius: 10px; border:1px dashed var(--line);
    color: var(--text); background: var(--card); cursor:pointer; font-size: 15px; }
  .photo img { width:64px; height:64px; object-fit:cover; border-radius: 8px; display:none; border:2px solid var(--orange); }
  .photo button { background:none; border:none; color: var(--muted); text-decoration: underline; font-size:14px; display:none; }
  .go { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 22px; }
  .go button { font-size: 19px; font-weight: 800; padding: 22px 8px; border-radius: 14px; border: none; cursor: pointer;
    color: #120016; letter-spacing: 1px; }
  .treat { background: var(--orange); }
  .trick { background: var(--slime); }
  .go button:active { transform: scale(.97); }
  .go button[disabled] { opacity:.5; }
  .meta { margin-top: 22px; font-size: 14px; color: var(--muted); border-top: 1px solid var(--line); padding-top: 12px; }
  .warn { color: #ffb3b3; }
  input[type=file] { display:none; }
</style>
</head>
<body>
<main>
  <h1>&#127875; Trick or Treat</h1>
  <div class="sub">Enter your name, choose a costume, and optionally add a photo. Your Halloween greeting will appear on the big screen!</div>

  <?php if ($totMsg): ?>
    <div class="msg <?php echo $totMsgOk ? 'ok' : 'err'; ?>"><?php echo $totMsg; ?></div>
  <?php endif; ?>

  <form method="post" action="<?php echo tot_h($_SERVER['REQUEST_URI']); ?>" id="f" autocomplete="off">
    <label for="child_name">Name</label>
    <input type="text" id="child_name" name="child_name" maxlength="40" required placeholder="e.g. Emma" enterkeyhint="done">

    <label for="costume">Choose your costume</label>
    <div class="chips" id="chips">
      <?php foreach ($costumes as $label => $emoji): ?>
        <button type="button" class="chip" data-v="<?php echo tot_h($label); ?>"><?php echo $emoji . ' ' . tot_h($label); ?></button>
      <?php endforeach; ?>
    </div>
    <input type="text" id="costume" name="costume" maxlength="40" placeholder="…or type one" style="margin-top:8px; font-size:18px; padding:10px 14px;">

    <?php if (!empty($totSettings['local_show_photo'])): ?>
    <label>Photo <span style="opacity:.7">(optional — ask a grown-up first)</span></label>
    <div class="photo">
      <label class="btn" for="photo">&#128247; Take photo</label>
      <input type="file" id="photo" accept="image/*" capture="environment">
      <img id="thumb" alt="">
      <button type="button" id="clearPhoto">remove</button>
    </div>
    <input type="hidden" name="photo_data" id="photo_data">
    <?php else: ?>
    <div class="sub">Photo uploads are disabled by the display administrator.</div>
    <?php endif; ?>

    <div class="go">
      <button class="treat" type="submit" name="submit_greeting" value="treat">TREAT</button>
      <button class="trick" type="submit" name="submit_greeting" value="trick">TRICK</button>
    </div>
  </form>

  <div class="meta">
    <?php if (!$totRunning): ?>
      <div class="warn">&#9888; The display daemon isn't running — entries will wait until it starts (see the plugin's setup page).</div>
    <?php endif; ?>
    <?php if (empty($totSettings['enabled'])): ?>
      <div class="warn">&#9888; The plugin is disabled on its setup page.</div>
    <?php endif; ?>
    Waiting in line: <strong><?php echo count($totQueue); ?></strong>
    <?php if ($last): ?>
      &nbsp;·&nbsp; Last shown: <strong><?php echo tot_h($last['name']); ?></strong> (<?php echo tot_h($last['verdict']); ?>)
    <?php endif; ?>
  </div>
</main>
<script>
  // After a POST, swap history so a refresh doesn't resubmit the same kid.
  // Do not modify the POST history entry; use redirect-after-post server-side instead.

  var costume = document.getElementById('costume');
  document.getElementById('chips').addEventListener('click', function (e) {
    var b = e.target.closest('.chip'); if (!b) return;
    var on = !b.classList.contains('on');
    document.querySelectorAll('.chip').forEach(function (c) { c.classList.remove('on'); });
    if (on) { b.classList.add('on'); costume.value = b.dataset.v; } else { costume.value = ''; }
  });
  costume.addEventListener('input', function () {
    document.querySelectorAll('.chip').forEach(function (c) { c.classList.toggle('on', c.dataset.v === costume.value); });
  });

  // Shrink phone photos in the browser (max 480px JPEG) so uploads are tiny
  // and never hit PHP's upload/post size limits on the FPP box.
  var photo = document.getElementById('photo');
  if (photo) {
    var thumb = document.getElementById('thumb'), data = document.getElementById('photo_data'),
        clear = document.getElementById('clearPhoto');
    photo.addEventListener('change', function () {
      var file = photo.files && photo.files[0]; if (!file) return;
      var img = new Image(), url = URL.createObjectURL(file);
      img.onload = function () {
        var max = 480, s = Math.min(1, max / Math.max(img.width, img.height));
        var c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        data.value = c.toDataURL('image/jpeg', 0.82);
        thumb.src = data.value; thumb.style.display = 'block'; clear.style.display = 'inline';
        URL.revokeObjectURL(url);
      };
      img.src = url;
    });
    clear.addEventListener('click', function () {
      data.value = ''; photo.value = ''; thumb.style.display = 'none'; clear.style.display = 'none';
    });
  }

  // The keyboard's Enter/Done key would otherwise submit as TREAT (the
  // first submit button) — make it just close the keyboard instead.
  document.querySelectorAll('input[type=text]').forEach(function (inp) {
    inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
  });

  // Prevent double-taps queuing the same kid twice. (Disabled after the
  // submit event, so the tapped button's verdict is still sent.)
  document.getElementById('f').addEventListener('submit', function () {
    setTimeout(function () { document.querySelectorAll('.go button').forEach(function (b) { b.disabled = true; }); }, 0);
  });
</script>
</body>
</html>
