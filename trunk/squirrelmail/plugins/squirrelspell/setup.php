<?php

/**
 * setup.php
 *
 * Squirrelspell setup file, as defined by the SquirrelMail-1.2 API.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 * @todo remove sqspell_ prefix from main php scripts.
 */


/**
 * Standard SquirrelMail plugin initialization API.
 *
 * @return void
 */
function squirrelmail_plugin_init_squirrelspell() {
  global $squirrelmail_plugin_hooks;
  $squirrelmail_plugin_hooks['template_construct_compose_buttons.tpl']['squirrelspell'] = 
      'squirrelspell_setup';
  $squirrelmail_plugin_hooks['optpage_register_block']['squirrelspell'] =
      'squirrelspell_optpage_register_block';
  $squirrelmail_plugin_hooks['right_main_after_header']['squirrelspell'] =
      'squirrelspell_upgrade';
}

/**
 * Register option block
 *
 * This function formats and adds the plugin and its description to the
 * Options screen. Code moved to internal function in order to reduce
 * setup.php size.
 * @return void
 */
function squirrelspell_optpage_register_block() {
  include_once(SM_PATH . 'plugins/squirrelspell/sqspell_functions.php');
  squirrelspell_optpage_block_function();
}

/**
 * Add spell check button in compose.
 *
 * This function adds a "Check Spelling" link to the "Compose" row
 * during message composition.
 * @return void
 */
function squirrelspell_setup() {
  include_once(SM_PATH . 'plugins/squirrelspell/sqspell_functions.php');
  return squirrelspell_setup_function();
}

/**
 * Upgrade dictionaries
 *
 * Transparently upgrades user's dictionaries when message listing is loaded
 * @since 1.5.1 (sqspell 0.5)
 */
function squirrelspell_upgrade() {
  include_once(SM_PATH . 'plugins/squirrelspell/sqspell_functions.php');
  squirrelspell_upgrade_function();
}

/**
 * Display SquirrelSpell version
 * @since 1.5.1 (sqspell 0.5)
 * @return string plugin's version
 */
function squirrelspell_version() {
  return '0.5';
}
