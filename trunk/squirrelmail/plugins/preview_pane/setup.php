<?php

/**
 * SquirrelMail Preview Pane Plugin
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @author Paul Lesniewski <paul@squirrelmail.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage preview_pane
 */


/**
 * Register this plugin with SquirrelMail
 */
function squirrelmail_plugin_init_preview_pane() 
{

   global $squirrelmail_plugin_hooks;


   $squirrelmail_plugin_hooks['subject_link']['preview_pane'] 
      = 'preview_pane_change_message_target';
   $squirrelmail_plugin_hooks['optpage_loadhook_display']['preview_pane'] 
      = 'preview_pane_show_options';
   $squirrelmail_plugin_hooks['template_construct_message_list.tpl']['preview_pane']
      = 'preview_pane_message_list';
   $squirrelmail_plugin_hooks['template_construct_page_header.tpl']['preview_pane']
      = 'preview_pane_open_close_buttons';

}


if (!defined('SM_PATH'))
   define('SM_PATH', '../');


/**
 * Returns info about this plugin
 */
function preview_pane_info()
{

   return array(
                 'english_name' => 'Preview Pane',
                 'version' => '2.0',
                 'required_sm_version' => '1.5.2',
                 'requires_configuration' => 0,
                 'requires_source_patch' => 0,
                 'required_plugins' => array(
                                            ),
                 'summary' => 'Provides a third frame below the message list for viewing message bodies.',
                 'details' => 'This plugin allows the user to turn on an extra frame below the mailbox message list where the messages themselves are displayed, very similar to many other popular (typically non-web-based) email clients.',
               );

}



/**
 * Returns version info about this plugin
 */
function preview_pane_version()
{

   $info = preview_pane_info();
   return $info['version'];

}



/**
 * Build user options for display on "Display Preferences" page
 */
function preview_pane_show_options() 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  preview_pane_show_options_do();

}



/**
 * Construct button that clears out any preview pane 
 * contents and inserts JavaScript function used by 
 * message subject link onclick handler.  Also disallows 
 * the message list to be loaded into the bottom frame.
 */
function preview_pane_message_list() 
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  return preview_pane_message_list_do();

}



/**
 * Points message targets to open in the preview pane
 * (and possibly refresh message list as well)
 */
function preview_pane_change_message_target($args)
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  preview_pane_change_message_target_do($args);

}



/**
 * Adds preview pane open/close (and clear) buttons next to
 * "provider link"
 */
function preview_pane_open_close_buttons()
{

  include_once(SM_PATH . 'plugins/preview_pane/functions.php');
  return preview_pane_open_close_buttons_do();

}



