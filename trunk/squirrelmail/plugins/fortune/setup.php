<?php

/**
 * plugins/fortune/setup.php
 *
 * Original code contributed by paulm@spider.org
 *
 * Simple SquirrelMail WebMail Plugin that displays the output of
 * fortune above the message listing.
 *
 * @copyright (c) 1999-2004 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage fortune
 */

/**
 * Init plugin
 * @access private 
 */
function squirrelmail_plugin_init_fortune() {
  global $squirrelmail_plugin_hooks;
  
  $squirrelmail_plugin_hooks['mailbox_index_before']['fortune'] = 'fortune';
  $squirrelmail_plugin_hooks['options_display_inside']['fortune'] = 'fortune_options';
  $squirrelmail_plugin_hooks['options_display_save']['fortune'] = 'fortune_save';
  $squirrelmail_plugin_hooks['loading_prefs']['fortune'] = 'fortune_load';  
}

/**
 * Show fortune
 * @access private 
 */
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
        echo "$fortune_location" . _(" not found.");
    } else {
        echo "<CENTER><FONT=3><EM>" . _("Today's Fortune") . "</EM><BR></FONT></CENTER><pre>";
        system($fortune_location);
    } 
  
    echo '</pre></TD></TR></TABLE></td></tr></table></td></tr></table></center>';
}

/**
 * Get fortune prefs
 * @access private 
 */
function fortune_load() {
    global $username, $data_dir, $fortune_visible;

    $fortune_visible = getPref($data_dir, $username, 'fortune_visible');
}

/**
 * Add fortune options
 * @access private 
 */
function fortune_options() {
  global $fortune_visible;

  echo "<tr>" . html_tag('td',_("Fortunes:"),'right','','nowrap') . "\n";
  echo '<td><input name="fortune_fortune_visible" type=CHECKBOX';
  if ($fortune_visible)
    echo ' CHECKED';
  echo " /> " . _("Show fortunes at top of mailbox") . "</td></tr>\n";
}

/**
 * Save fortune prefs
 * @access private 
 */
function fortune_save() {
    global $username,$data_dir;

    if (sqgetGlobalVar('fortune_fortune_visible',$fortune_fortune_visible,SQ_POST)) {
        setPref($data_dir, $username, 'fortune_visible', '1');
    } else {
        setPref($data_dir, $username, 'fortune_visible', '');
    }
}

?>