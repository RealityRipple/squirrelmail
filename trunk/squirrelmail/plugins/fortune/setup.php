<?php

/**
 * plugins/fortune/setup.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Original code contributed by paulm@spider.org
 *
 * Simple SquirrelMail WebMail Plugin that displays the output of
 * fortune above the message listing.
 *
 * $Id$
 */

function squirrelmail_plugin_init_fortune() {
  global $squirrelmail_plugin_hooks;
  
  $squirrelmail_plugin_hooks['mailbox_index_before']['fortune'] = 'fortune';
  $squirrelmail_plugin_hooks['options_display_inside']['fortune'] = 'fortune_options';
  $squirrelmail_plugin_hooks['options_display_save']['fortune'] = 'fortune_save';
  $squirrelmail_plugin_hooks['loading_prefs']['fortune'] = 'fortune_load';  
}

function fortune() {
    global $fortune_visible, $color;

    if (!$fortune_visible) {
        return;
    }

    $fortune_location = '/usr/games/fortune';
    $exist = file_exists($fortune_location);
    echo "<center><table cellpadding=0 cellspacing=0 border=0 bgcolor=$color[10]><tr><td><table width=100% cellpadding=2 cellspacing=1 border=0 bgcolor=\"$color[5]\"><tr><td align=center>";
    echo '<TABLE><TR><TD>';
    if (!$exist) {
        echo "$fortune_location not found.";
    } else {
        echo "<CENTER><FONT=3><EM>Today's Fortune</EM><BR></FONT></CENTER><pre>";
        system($fortune_location);
    } 
  
    echo '</pre></TD></TR></TABLE></td></tr></table></td></tr></table></center>';
}

function fortune_load() {
    global $username, $data_dir, $fortune_visible;

    $fortune_visible = getPref($data_dir, $username, 'fortune_visible');
}

function fortune_options() {
  global $fortune_visible;

  echo "<tr><td align=right nowrap>Fortunes:</td>\n";
  echo '<td><input name="fortune_fortune_visible" type=CHECKBOX';
  if ($fortune_visible)
    echo ' CHECKED';
  echo "> Show fortunes at top of mailbox</td></tr>\n";
}

function fortune_save() {
    global $username,$data_dir;

    if (isset($_POST['fortune_fortune_visible'])) {
        setPref($data_dir, $username, 'fortune_visible', '1');
    } else {
        setPref($data_dir, $username, 'fortune_visible', '');
    }
}

?>
