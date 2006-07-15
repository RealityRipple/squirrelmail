<?php

/**
 * Calendar plugin activation script
 *
 * @copyright &copy; 2002-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage calendar
 */

/**
 * Initialize the plugin
 * @return void
 */
function squirrelmail_plugin_init_calendar() {
    global $squirrelmail_plugin_hooks;
    $squirrelmail_plugin_hooks['menuline']['calendar'] = 'calendar';
}

/**
 * Adds Calendar link to upper menu
 * @return void
 */
function calendar() {
    displayInternalLink('plugins/calendar/calendar.php',_("Calendar"),'right');
    echo "&nbsp;&nbsp;\n";
}
