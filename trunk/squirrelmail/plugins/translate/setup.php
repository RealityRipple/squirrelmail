<?php
/**
 * setup.php
 *
 * Easy plugin that sends the body of the message to a new browser
 * window using the specified translator.
 *
 * Translation of composed messages is not supported.
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */

/**
 * If SM_PATH isn't defined, define it.
 * @ignore
 */
if (!defined('SM_PATH'))  {
    define('SM_PATH','../../');
}

/**
 * Initialize the translation plugin
 * @return void
 * @access private
 */
function squirrelmail_plugin_init_translate() {
  global $squirrelmail_plugin_hooks;

  $squirrelmail_plugin_hooks['read_body_bottom']['translate'] = 'translate_read_form';
  $squirrelmail_plugin_hooks['optpage_register_block']['translate'] = 'translate_optpage_register_block';
  $squirrelmail_plugin_hooks['loading_prefs']['translate'] = 'translate_pref';
//  $squirrelmail_plugin_hooks['compose_button_row']['translate'] = 'translate_button';
}

/** 
 * Shows translation box in message display window 
 * @access private
 */
function translate_read_form() {
    include_once(SM_PATH . 'plugins/translate/functions.php');
    translate_read_form_function();
}

/**
 * Should add translation options in compose window
 *
 * Unimplemented
 * @access private
 */
function translate_button() {
    include_once(SM_PATH . 'plugins/translate/functions.php');
    translate_button_function();
}

/**
 * Calls translation option block function
 * @access private
 */
function translate_optpage_register_block() {
    include_once(SM_PATH . 'plugins/translate/functions.php');
    translate_optpage_function();
}

/**
 * Calls user's translation preferences function
 * @access private
 */
function translate_pref() {
    include_once(SM_PATH . 'plugins/translate/functions.php');
    translate_pref_function();
}
?>