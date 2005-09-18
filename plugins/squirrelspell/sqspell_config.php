<?php

/**
 * sqspell_config.php -- SquirrelSpell Configuration file.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../../');

/** getHashedFile() function for SQSPELL_WORDS_FILE and sqgetGlobalVar() from global.php */
include_once(SM_PATH . 'functions/prefs.php');

/** vars needed for getHashedFile() */
global $data_dir;
sqgetGlobalVar('username', $username, SQ_SESSION);

/**
 * List of configured dictionaries
 *
 * This feature was added/changed in 0.3. Use this array to set up
 * which dictionaries are available to users. If you only have
 * English spellchecker on your system, then let this line be:
 *<pre>
 *   $SQSPELL_APP = array('English' => 'ispell -a');
 *     or
 *   $SQSPELL_APP = array('English' => '/usr/local/bin/aspell -a');
 *</pre>
 * Sometimes you have to specify full path for PHP to find it.
 * 
 * You can use Aspell or Ispell spellcheckers. Aspell might provide
 * better spellchecking for Western languages.
 *
 * If you want to have more than one dictionary available to users,
 * configure the array to look something like this:
 *<pre>
 *   $SQSPELL_APP = array('English' => 'aspell -a',
 *                        'Russian' => 'ispell -d russian -a',
 *                        ...
 *                        'Swahili' => 'ispell -d swahili -a'
 *                        );
 *</pre>
 * WARNINGS:
 * <ul>
 * <li>Watch the commas, making sure there isn't one after your last
 *     dictionary declaration. Also, make sure all these dictionaries
 *     are available on your system before you specify them here.</li>
 * <li>Whatever your setting is, don't omit the "-a" flag.</li>
 * <li>Remember to keep same array keys during upgrades. Don't rename them.
 *   Users' dictionary settings use it.</li>
 * <li>Interface might translate array key, if used key is present in 
 *   SquirrelMail translations.</li>
 * </ul>
 * <pre>
 * Example:
 * $SQSPELL_APP = array('English' => 'ispell -a',
 *                      'Spanish' => 'ispell -d spanish -a' );
 * </pre>
 *
 * @global array $SQSPELL_APP
 */
$SQSPELL_APP = array('English' => 'ispell -a',
                     'Spanish' => 'ispell -d spanish -a');

/**
 * Default dictionary
 * @global string $SQSPELL_APP_DEFAULT
 */
$SQSPELL_APP_DEFAULT = 'English';

/**
 * File that stores user's dictionary
 *
 * $SQSPELL_WORDS_FILE is a location and mask of a user dictionary file.
 * The default setting should be OK for most everyone.
 *     
 * This setting is used only when SquirrelSpell is upgraded from
 * older setup. Since SquirrelMail 1.5.1 SquirrelSpell stores all settings in
 * same place that stores other SquirrelMail user preferences.
 * @global string $SQSPELL_WORDS_FILE
 * @deprecated setting is still needed in order to handle upgrades
 */
$SQSPELL_WORDS_FILE =
   getHashedFile($username, $data_dir, "$username.words");

/**
 * Function used for checking words in user's dictionary
 * @global string $SQSPELL_EREG
 * @deprecated It is not used since 1.5.1 (sqspell 0.5)
 */
$SQSPELL_EREG = 'ereg';

?>