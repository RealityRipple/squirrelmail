<?php

/**
 * setup file for the IMAP server info plugin
 *
 * @author Jason Munro <jason at stdbev.com>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage info
 */

/**
 * Plugin init function
 * @access private
 */
function squirrelmail_plugin_init_info() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['optpage_register_block']['info'] = 'info_opt';
}

/**
 * Plugin's block in option page
 * @access private
 */
function info_opt() {
    global $optpage_blocks;

    $optpage_blocks[] = array(
        'name' => _("IMAP server information"),
        'url'  => '../plugins/info/options.php',
        'desc' => _("Run some test IMAP commands, displaying both the command and the result. These tests use the SquirrelMail IMAP commands and your current SquirrelMail configuration. Custom command strings can be used."),
        'js'   => false
    );
}
