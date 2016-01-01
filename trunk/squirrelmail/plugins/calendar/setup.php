<?php

/**
 * Calendar plugin activation script
 *
 * @copyright 2002-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage calendar
 */

/**
  * Register this plugin with SquirrelMail
  * 
  * @return void
  *
  */
function squirrelmail_plugin_init_calendar() {

    global $squirrelmail_plugin_hooks;

    $squirrelmail_plugin_hooks['template_construct_page_header.tpl']['calendar'] 
        = 'calendar';

}


/**
  * Add link to menu at top of content pane
  *
  * @return void
  *
  */
function calendar() {

    include_once(SM_PATH . 'plugins/calendar/functions.php');
    return calendar_do();

}


