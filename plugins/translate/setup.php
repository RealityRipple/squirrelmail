<?php

/**
 * setup.php
 *
 * Easy plugin that sends the body of the message to a new browser
 * window using the specified translator.
 *
 * Translation of composed messages is not supported.
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */


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
  $squirrelmail_plugin_hooks['options_save']['translate'] = 'translate_save';
  $squirrelmail_plugin_hooks['optpage_set_loadinfo']['translate'] = 'translate_set_loadinfo';
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

/**
 * Calls user's translation preferences saving function
 * @access private
 */
function translate_save() {
    include_once(SM_PATH . 'plugins/translate/functions.php');
    translate_save_function();
}

/**
 * Calls user's translation preferences set_loadinfo function
 * @access private
 */
function translate_set_loadinfo() {
    include_once(SM_PATH . 'plugins/translate/functions.php');
    translate_set_loadinfo_function();
}
