<?php

/**
 * setup.php
 *
 * Copyright (c) 2002-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Originally contrubuted by Michal Szczotka <michal@tuxy.org>
 *
 * Init plugin into SquirrelMail
 *
 * $Id$
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

function calendar() {
    /* Add Calendar link to upper menu */
    displayInternalLink('plugins/calendar/calendar.php',_("Calendar"),'right');
    echo "&nbsp;&nbsp;\n";
}

?>