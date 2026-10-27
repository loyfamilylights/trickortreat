<?php
$menuEntries = array(
    array('text' => 'Trick-or-Treat - Simple Controls', 'type' => 'content', 'page' => 'simple.php', 'wrap' => 1),
    array('text' => 'Trick-or-Treat - Instructions', 'type' => 'help', 'page' => 'about.php', 'wrap' => 1)
);
foreach ($menuEntries as $entry) {
    if ($entry['type'] != $menu) continue;
    printf("<li><a href='plugin.php?plugin=%s&page=%s'>%s</a></li>\n",
        htmlspecialchars($plugin, ENT_QUOTES), htmlspecialchars($entry['page'], ENT_QUOTES), htmlspecialchars($entry['text'], ENT_QUOTES));
}
?>
