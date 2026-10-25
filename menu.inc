<?php
# $menuEntries is an array of menu entries for this plugin.
# See FalconChristmas/fpp-plugin-Template for the field reference this
# follows (text/type/page/wrap). The Door page uses wrap=0 so FPP serves it
# bare (no FPP header/menus) — it's a full-screen phone page.

$menuEntries = Array(
	Array(
		'text' => 'Trick-or-Treat - Status',
		'type' => 'status',
		'page' => 'status.php',
		'wrap' => 1
	),
	Array(
		'text' => 'Trick-or-Treat - Setup',
		'type' => 'content',
		'page' => 'content.php',
		'wrap' => 1
	),
	Array(
		'text' => 'Trick-or-Treat - Door (phone page)',
		'type' => 'content',
		'page' => 'door.php',
		'wrap' => 0
	),
	Array(
		'text' => 'Trick-or-Treat - About',
		'type' => 'help',
		'page' => 'about.php',
		'wrap' => 1
	)
);

foreach ($menuEntries as $entry)
{
	if ($entry['type'] != $menu)
		continue;

	if (preg_match('/^http.?:\/\//', $entry['page']))
	{
		printf("<li><a href='%s' target='_blank'>%s</a></li>\n",
			$entry['page'], $entry['text']);
	}
	else
	{
		$nopage = '';
		if (isset($entry['wrap']) && ($entry['wrap'] == 0))
			$nopage = '&nopage=1';

		printf("<li><a href='plugin.php?plugin=%s&page=%s%s'>%s</a></li>\n",
			$plugin, $entry['page'], $nopage, $entry['text']);
	}
}
?>
