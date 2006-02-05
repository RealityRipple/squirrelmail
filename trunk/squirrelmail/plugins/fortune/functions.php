<?php

/**
 * Fortune plugin functions
 *
 * @copyright &copy; 2004-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage fortune
 */

/**
 * Declare configuration globals
 */
global $fortune_location, $fortune_options;

/**
 * Load default config
 */
include_once(SM_PATH . 'plugins/fortune/config_default.php');

/**
 * Load site config
 */
if (file_exists(SM_PATH . 'config/fortune_config.php')) {
    include_once(SM_PATH . 'config/fortune_config.php');
} elseif (file_exists(SM_PATH . 'plugins/fortune/config.php')) {
    include_once(SM_PATH . 'plugins/fortune/config.php');
}

/**
 * Show fortune
 * @access private
 * @since 1.5.1
 */
function fortune_function() {
    global $fortune_visible, $color, $fortune_location, $fortune_options;

    if (!$fortune_visible) {
        return;
    }

    $exist = file_exists($fortune_location);

    if ($fortune_options!='') {
        $fortune_command=$fortune_location . ' ' . $fortune_options;
    } else {
        $fortune_command=$fortune_location;
    }

    echo "<div style="text-align: center;"><table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" bgcolor=\"$color[10]\">\n".
        "<tr><td><table width=\"100%\" cellpadding=\"2\" cellspacing=\"1\" border=\"0\" bgcolor=\"$color[5]\">\n".
        "<tr><td align=\"center\">\n";
    echo '<table><tr><td>';
    if (!$exist) {
        printf(_("%s is not found."),$fortune_location);
    } else {
        echo "<div style="text-align: center;"><em>" . _("Today's Fortune") . "</em></div><pre>\n" .
            htmlspecialchars(shell_exec($fortune_command)) .
            "</pre>\n";
    }

    echo '</td></tr></table></td></tr></table></td></tr></table></div>';
}

/**
 * Add fortune options
 * @access private
 * @since 1.5.1
 */
function fortune_function_options() {
    global $optpage_data;

    $optpage_data['grps']['fortune'] = _("Fortunes:");
    $optionValues = array();
    $optionValues[] = array('name'    => 'fortune_visible',
                            'caption' => _("Show fortunes at top of mailbox"),
                            'type'    => SMOPT_TYPE_BOOLEAN,
                            'initial_value' => false );
    $optpage_data['vals']['fortune'] = $optionValues;
}

/**
 * Get fortune prefs
 * @access private
 * @since 1.5.1
 */
function fortune_function_load() {
    global $username, $data_dir, $fortune_visible;

    $fortune_visible = getPref($data_dir, $username, 'fortune_visible');
}
?>