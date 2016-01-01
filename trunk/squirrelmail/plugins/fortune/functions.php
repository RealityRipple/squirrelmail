<?php

/**
 * Fortune plugin functions
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage fortune
 */

/**
 * Declare configuration global and set default value
 */
global $fortune_command;
$fortune_command = '/usr/games/fortune -s';

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
    global $oTemplate, $fortune_visible, $color, $fortune_command;

    if (!$fortune_visible) {
        return;
    }

    /* open handle and get all command output*/
    $handle = popen($fortune_command,'r');
    $fortune = '';
    while ($read = fread($handle,1024)) {
        $fortune .= $read;
    }
    /* if pclose return != 0, popen command failed. Yes, I know that it is broken when --enable-sigchild is used */
    if (pclose($handle)) {
        // i18n: %s shows executed fortune cookie command.
        $fortune = sprintf(_("Unable to execute \"%s\"."),$fortune_command);
    }

    $oTemplate->assign('color', $color);
    $oTemplate->assign('fortune', sm_encode_html_special_chars($fortune));
    $output = $oTemplate->fetch('plugins/fortune/mailbox_index_before.tpl');
    return array('mailbox_index_before' => $output);

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
