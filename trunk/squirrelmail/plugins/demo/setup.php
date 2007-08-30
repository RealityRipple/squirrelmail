<?php

/**
  * SquirrelMail Demo Plugin
  * @copyright &copy; 2006-2007 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package plugins
  * @subpackage demo
  */



/**
  * Register this plugin with SquirrelMail
  *
  * @return void
  *
  */
function squirrelmail_plugin_init_demo() 
{

   global $squirrelmail_plugin_hooks;

   $squirrelmail_plugin_hooks['login_cookie']['demo']
      = 'demo_login_cookie';

   $squirrelmail_plugin_hooks['login_top']['demo']
      = 'demo_login_top';

   $squirrelmail_plugin_hooks['login_bottom']['demo']
      = 'demo_login_bottom';

   $squirrelmail_plugin_hooks['template_construct_page_header.tpl']['demo']
      = 'demo_page_header_template';

//FIXME: put *ALL* SM hooks in here... which includes template_construct hooks for any templates that have plugin output sections in them and put page_header_template in right order
//FIXME: not all of the above hooks are yet implemented below
//FIXME: many hooks have examples in the original demo plugin in trunk/plugins/demo
}



/**
  * Returns info about this plugin
  *
  * @return array An array of plugin information.
  *
  */
function demo_info()
{

   return array(
             'english_name' => 'Demo',
             'summary' => 'This plugin provides test/sample code for many of the hook points in the SquirrelMail core.',
             'details' => 'This plugin provides test/sample code for many of the hook points in the SquirrelMail core.', 
             'requires_configuration' => 0,
             'requires_source_patch' => 0,
          );

}



/**
  * Returns version info about this plugin
  *
  */
function demo_version()
{
   $info = demo_info();
   return $info['version'];
}



/**
  * Add link to menu at top of content pane
  *
  * @return void
  *
  */
function demo_page_header_template()
{
   include_once(SM_PATH . 'plugins/demo/functions.php');
   return demo_page_header_template_do();
}



