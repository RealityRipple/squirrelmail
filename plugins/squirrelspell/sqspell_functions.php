<?php

/**
 * sqspell_functions.php
 *
 * All SquirrelSpell-wide functions are in this file.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/** globalize configuration vars **/
global $SQSPELL_APP, $SQSPELL_APP_DEFAULT, $SQSPELL_WORDS_FILE, $SQSPELL_CRYPTO;

/**
 * load plugin configuration
 * @todo allow storing configuration file in config/ directory
 */
include_once(SM_PATH . 'plugins/squirrelspell/sqspell_config.php');

/**
 * Workaround for including function squirrelspell_version() in SM 1.5 CVS,
 * where plugins' setup.php is not included by default.
 */
include_once(SM_PATH . 'plugins/squirrelspell/setup.php');

/** Hooked functions **/

/**
 * Register option page block (internal function)
 * @since 1.5.1 (sqspell 0.5)
 * @return void
 */
function squirrelspell_optpage_block_function() {
  global $optpage_blocks;

  /**
   * Dependency on JavaScript is checked by SquirrelMail scripts
   * Register Squirrelspell with the $optpage_blocks array.
   */
  $optpage_blocks[] =
    array(
      'name' => _("SpellChecker Options"),
      'url'  => '../plugins/squirrelspell/sqspell_options.php',
      'desc' => _("Here you may set up how your personal dictionary is stored, edit it, or choose which languages should be available to you when spell-checking."),
      'js'   => TRUE);
}

/**
 * This function adds a "Check Spelling" link to the "Compose" row
 * during message composition (internal function).
 * @since 1.5.1 (sqspell 0.5)
 * @return void
 */
function squirrelspell_setup_function() {
  /**
   * Check if this browser is capable of displaying SquirrelSpell
   * correctly.
   */
  if (checkForJavascript()) {

    global $oTemplate, $base_uri, $nbsp;

    $output = addButton(_("Check Spelling"), 
                        'check_spelling', 
                        array('onclick' => 'window.open(\'' . $base_uri 
                                         . 'plugins/squirrelspell/sqspell_interface.php\', \'sqspell\', \'status=yes,width=550,height=370,resizable=yes\')')) . $nbsp;

    return array('compose_button_row' => $output);
  }
}

/**
 * Upgrade dictionaries (internal function)
 *
 * Transparently upgrades user's dictionaries when message listing is loaded
 * @since 1.5.1 (sqspell 0.5)
 */
function squirrelspell_upgrade_function() {
  global $data_dir, $username;

  if (! sqspell_check_version(0,5)) {
    $langs=sqspell_getSettings_old(null);
    $words=sqspell_getWords_old();
    sqspell_saveSettings($langs);
    foreach ($langs as $lang) {
      $lang_words=sqspell_getLang_old($words,$lang);
      $aLang_words=explode("\n",$lang_words);
      $new_words=array();
      foreach($aLang_words as $word) {
        if (! preg_match("/^#/",$word) && trim($word)!='') {
          $new_words[]=$word;
        }
      }
      sqspell_writeWords($new_words,$lang);
    }
    // bump up version number
    setPref($data_dir,$username,'sqspell_version','0.5');
  }
}

/** Internal functions **/

/**
 * This function is the GUI wrapper for the options page. SquirrelSpell
 * uses it for creating all Options pages.
 *
 * @param  string $title     The title of the page to display
 * @param  string $scriptsrc This is used to link a file.js into the
 *                    <script src="file.js"></script> format. This
 *                    allows to separate javascript from the rest of the
 *                    plugin and place it into the js/ directory.
 * @param  string $body      The body of the message to display.
 * @return            void
 */
function sqspell_makePage($title, $scriptsrc, $body){
  global $color;

  if (! sqgetGlobalVar('MOD', $MOD, SQ_GET) ) {
      $MOD = 'options_main';
  }

  displayPageHeader($color);
  echo "&nbsp;<br />\n";
  /**
   * Check if we need to link in a script.
   */
  if($scriptsrc) {
    echo "<script type=\"text/javascript\" src=\"js/$scriptsrc\"></script>\n";
  }
  echo html_tag( 'table', '', 'center', '', 'width="95%" border="0" cellpadding="2" cellspacing="0"' ) . "\n"
    . html_tag( 'tr', "\n" .
          html_tag( 'td', '<strong>' . $title .'</strong>', 'center', $color[9] )
      ) . "\n"
    . html_tag( 'tr', "\n" .
          html_tag( 'td', '<hr />', 'left' )
      ) . "\n"
    . html_tag( 'tr', "\n" .
          html_tag( 'td', $body, 'left' )
      ) . "\n";
  /**
   * Generate a nice "Return to Options" link, unless this is the
   * starting page.
   */
  if ($MOD != "options_main"){
    echo html_tag( 'tr', "\n" .
                html_tag( 'td', '<hr />', 'left' )
            ) . "\n"
      . html_tag( 'tr', "\n" .
            html_tag( 'td', '<a href="sqspell_options.php">'
                . _("Back to &quot;SpellChecker Options&quot; page")
                . '</a>',
            'center' )
        ) . "\n";
  }
  /**
   * Close the table and display the version.
   */
  echo html_tag( 'tr', "\n" .
              html_tag( 'td', '<hr />', 'left' )
          ) . "\n"
    . html_tag( 'tr',
          html_tag( 'td', 'SquirrelSpell ' . squirrelspell_version(), 'center', $color[9] )
      ) . "\n</table>\n";
  echo '</body></html>';
}

/**
 * Function similar to the one above. This one is a general wrapper
 * for the Squirrelspell pop-up window. It's called form nearly
 * everywhere, except the check_me module, since that one is highly
 * customized.
 *
 * @param  string $onload    Used to indicate and pass the name of a js function
 *                    to call in a <body onload="function()" for automatic
 *                    onload script execution.
 * @param  string $title     Title of the page.
 * @param  string $scriptsrc If defined, link this javascript source page into
 *                    the document using <script src="file.js"> format.
 * @param  string $body      The content to include.
 * @return            void
 */
function sqspell_makeWindow($onload, $title, $scriptsrc, $body){
  global $color;

  displayHtmlHeader($title,
      ($scriptsrc ? "\n<script type=\"text/javascript\" src=\"js/$scriptsrc\"></script>\n" : ''));

  echo "<body text=\"$color[8]\" bgcolor=\"$color[4]\" link=\"$color[7]\" "
      . "vlink=\"$color[7]\" alink=\"$color[7]\"";
  /**
   * Provide an onload="jsfunction()" if asked to.
   */
  if ($onload) {
      echo " onload=\"$onload\"";
  }
  /**
   * Draw the rest of the page.
   */
  echo ">\n"
    . html_tag( 'table', "\n" .
          html_tag( 'tr', "\n" .
              html_tag( 'td', '<strong>' . $title . '</strong>', 'center', $color[9] )
          ) . "\n" .
          html_tag( 'tr', "\n" .
              html_tag( 'td', '<hr />', 'left' )
          ) . "\n" .
          html_tag( 'tr', "\n" .
              html_tag( 'td', $body, 'left' )
          ) . "\n" .
          html_tag( 'tr', "\n" .
              html_tag( 'td', '<hr />', 'left' )
          ) . "\n" .
          html_tag( 'tr', "\n" .
              html_tag( 'td', 'SquirrelSpell ' . squirrelspell_version(), 'center', $color[9] )
          ) ,
      '', '', 'width="100%" border="0" cellpadding="2"' );

  global $oTemplate;
  $oTemplate->display('footer.tpl');
}

/**
 * Encryption function used by plugin (old format)
 *
 * This function does the encryption and decryption of the user
 * dictionary. It is only available when PHP is compiled with
 * mcrypt support (--with-mcrypt). See doc/CRYPTO for more
 * information.
 *
 * @param  $mode  A string with either of the two recognized values:
 *                "encrypt" or "decrypt".
 * @param  $ckey  The key to use for processing (the user's password
 *                in our case.
 * @param  $input Content to decrypt or encrypt, according to $mode.
 * @return        encrypted/decrypted content, or "PANIC" if the
 *                process bails out.
 * @since 1.5.1 (sqspell 0.5)
 * @deprecated
 */
function sqspell_crypto_old($mode, $ckey, $input){
  /**
   * Double-check if we have the mcrypt_generic function. Bail out if
   * not so.
   */
  if (!function_exists('mcrypt_generic')) {
    return 'PANIC';
  }
  /**
   * Setup mcrypt routines.
   */
  $td = mcrypt_module_open(MCRYPT_Blowfish, "", MCRYPT_MODE_ECB, "");
  $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
  mcrypt_generic_init($td, $ckey, $iv);
  /**
   * See what we have to do depending on $mode.
   * 'encrypt' -- Encrypt the content.
   * 'decrypt' -- Decrypt the content.
   */
  switch ($mode){
  case 'encrypt':
    $crypto = mcrypt_generic($td, $input);
    break;
  case 'decrypt':
    $crypto = mdecrypt_generic($td, $input);
    /**
     * See if it decrypted successfully. If so, it should contain
     * the string "# SquirrelSpell". If not, then bail out.
     */
    if (!strstr($crypto, "# SquirrelSpell")){
      $crypto='PANIC';
    }
    break;
  }
  /**
   * Finish up the mcrypt routines and return the processed content.
   */
  if (function_exists('mcrypt_generic_deinit')) {
      // php 4.1.1+ syntax
      mcrypt_generic_deinit ($td);
      mcrypt_module_close ($td);
  } else {
      // older deprecated function
      mcrypt_generic_end ($td);
  }
  return $crypto;
}

/**
 * Encryption function used by plugin
 *
 * This function does the encryption and decryption of the user
 * dictionary. It is only available when PHP is compiled with
 * mcrypt support (--with-mcrypt). See doc/CRYPTO for more
 * information.
 *
 * @param  $mode  A string with either of the two recognized values:
 *                "encrypt" or "decrypt".
 * @param  $ckey  The key to use for processing (the user's password
 *                in our case.
 * @param  $input Content to decrypt or encrypt, according to $mode.
 * @return        encrypted/decrypted content, or "PANIC" if the
 *                process bails out.
 */
function sqspell_crypto($mode, $ckey, $input){
  /**
   * Double-check if we have the mcrypt_generic function. Bail out if
   * not so.
   */
    if (!function_exists('mcrypt_generic')) {
        return 'PANIC';
    }
    /**
     * Setup mcrypt routines.
     */
    $td = mcrypt_module_open(MCRYPT_Blowfish, "", MCRYPT_MODE_ECB, "");
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
    mcrypt_generic_init($td, $ckey, $iv);
    /**
     * See what we have to do depending on $mode.
     * 'encrypt' -- Encrypt the content.
     * 'decrypt' -- Decrypt the content.
     */
    switch ($mode){
    case 'encrypt':
        $crypto = mcrypt_generic($td, '{sqspell}'.$input);
        break;
    case 'decrypt':
        $crypto = mdecrypt_generic($td, $input);
        if (preg_match("/^\{sqspell\}(.*)/",$crypto,$match)){
            $crypto = trim($match[1]);
        } else {
            $crypto='PANIC';
        }
        break;
    }
    /**
     * Finish up the mcrypt routines and return the processed content.
     */
    if (function_exists('mcrypt_generic_deinit')) {
        // php 4.1.1+ syntax
        mcrypt_generic_deinit ($td);
        mcrypt_module_close ($td);
    } else {
        // older deprecated function
        mcrypt_generic_end ($td);
    }
    return $crypto;
}

/**
 * This function transparently upgrades the 0.2 dictionary format to the
 * 0.3 format, since user-defined languages have been added in 0.3 and
 * the new format keeps user dictionaries selection in the file.
 *
 * This function will be retired soon, as it's been a while since anyone
 * has been using SquirrelSpell-0.2.
 *
 * @param  $words_string Contents of the 0.2-style user dictionary.
 * @return               Contents of the 0.3-style user dictionary.
 * @deprecated
 */
function sqspell_upgradeWordsFile($words_string){
  global $SQSPELL_APP_DEFAULT, $SQSPELL_VERSION;
  /**
   * Define just one dictionary for this user -- the default.
   * If the user wants more, s/he can set them up in personal
   * preferences. See doc/UPGRADING for more info.
   */
  $new_words_string =
     substr_replace($words_string,
                    "# SquirrelSpell User Dictionary $SQSPELL_VERSION\n# "
                    . "Last Revision: " . date("Y-m-d")
                    . "\n# LANG: $SQSPELL_APP_DEFAULT\n# $SQSPELL_APP_DEFAULT",
                    0, strpos($words_string, "\n")) . "# End\n";
  sqspell_writeWords($new_words_string);
  return $new_words_string;
}

/**
 * gets list of available dictionaries from user's prefs.
 * Function was modified in 1.5.1 (sqspell 0.5).
 * Older function is suffixed with '_old'
 * @return array list of dictionaries used by end user.
 */
function sqspell_getSettings(){
    global $data_dir, $username, $SQSPELL_APP_DEFAULT, $SQSPELL_APP;

    $ret=array();

    $sLangs=getPref($data_dir,$username,'sqspell_langs','');
    if ($sLangs=='') {
        $ret[0]=$SQSPELL_APP_DEFAULT;
    } else {
        $aLangs = explode(',',$sLangs);
        foreach ($aLangs as $lang) {
            if (array_key_exists($lang,$SQSPELL_APP)) {
                $ret[]=$lang;
            }
        }
    }
    return $ret;
}

/**
 * Saves user's language preferences
 * @param array $langs languages array (first key is default language)
 * @since 1.5.1 (sqspell 0.5)
 */
function sqspell_saveSettings($langs) {
  global $data_dir, $username;
  setPref($data_dir,$username,'sqspell_langs',implode(',',$langs));
}

/**
 * Get list of enabled languages.
 *
 * Right now it just returns an array with the dictionaries
 * available to the user for spell-checking. It will probably
 * do more in the future, as features are added.
 *
 * @param string $words The contents of the user's ".words" file.
 * @return array a strings array with dictionaries available
 *                to this user, e.g. {"English", "Spanish"}, etc.
 * @since 1.5.1 (sqspell 0.5)
 * @deprecated
 */
function sqspell_getSettings_old($words){
  global $SQSPELL_APP, $SQSPELL_APP_DEFAULT;
  /**
   * Check if there is more than one dictionary configured in the
   * system config.
   */
  if (sizeof($SQSPELL_APP) > 1){
    /**
     * Now load the user prefs. Check if $words was empty -- a bit of
     * a dirty fall-back. TODO: make it so this is not required.
     */
    if(!$words){
      $words=sqspell_getWords_old();
    }
    if ($words){
      /**
       * This user has a ".words" file.
       * Find which dictionaries s/he wants to use and load them into
       * the $langs array.
       */
      preg_match("/# LANG: (.*)/i", $words, $matches);
      $langs=explode(", ", $matches[1]);
    } else {
      /**
       * User doesn't have a personal dictionary. Grab the default
       * system setting.
       */
      $langs[0]=$SQSPELL_APP_DEFAULT;
    }
  } else {
    /**
     * There is no need to read the ".words" file as there is only one
     * dictionary defined system-wide.
     */
    $langs[0]=$SQSPELL_APP_DEFAULT;
  }
  return $langs;
}

/**
 * Get user dictionary for selected language
 * Function was modified in 1.5.1 (sqspell 0.5).
 * Older function is suffixed with '_old'
 * @param string $lang language
 * @param array words stored in selected language dictionary
 */
function sqspell_getLang($lang) {
  global $data_dir, $username,$SQSPELL_CRYPTO;
  $sWords=getPref($data_dir,$username,'sqspell_dict_' . $lang,'');
  if (preg_match("/^\{crypt\}(.*)/i",$sWords,$match)) {
    /**
     * Dictionary is encrypted or mangled. Try to decrypt it.
     * If fails, complain loudly.
     *
     * $old_key would be a value submitted by one of the modules with
     * the user's old mailbox password. I admin, this is rather dirty,
     * but efficient. ;)
     */
    if (sqgetGlobalVar('old_key', $old_key, SQ_POST)) {
      $clear_key=$old_key;
    } else {
      sqgetGlobalVar('key', $key, SQ_COOKIE);
      sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);
      /**
       * Get user's password (the key).
       */
      $clear_key = OneTimePadDecrypt($key, $onetimepad);
    }
    /**
     * Invoke the decryption routines.
     */
    $sWords=sqspell_crypto("decrypt", $clear_key, $match[1]);
    /**
     * See if decryption failed.
     */
    if ($sWords=="PANIC"){
      sqspell_handle_crypt_panic($lang);
      // script execution stops here
    } else {
      /**
       * OK! Phew. Set the encryption flag to true so we can later on
       * encrypt it again before saving to HDD.
       */
      $SQSPELL_CRYPTO=true;
    }
  } else {
    /**
     * No encryption is/was used. Set $SQSPELL_CRYPTO to false,
     * in case we have to save the dictionary later.
     */
    $SQSPELL_CRYPTO=false;
  }
  // rebuild word list and remove empty entries
  $aWords=array();
  foreach (explode(',',$sWords) as $word) {
    if (trim($word) !='') {
      $aWords[]=trim($word);
      }
  }
  return $aWords;
}

/**
 * Get user's dictionary (old format)
 *
 * This function returns only user-defined dictionary words that correspond
 * to the requested language.
 *
 * @param  $words The contents of the user's ".words" file.
 * @param  $lang  Which language words to return, e.g. requesting
 *                "English" will return ONLY the words from user's
 *                English dictionary, disregarding any others.
 * @return        The list of words corresponding to the language
 *                requested.
 * @since 1.5.1 (sqspell 0.5)
 * @deprecated
 */
function sqspell_getLang_old($words, $lang){
  $start=strpos($words, "# $lang\n");
  /**
   * strpos() will return -1 if no # $lang\n string was found.
   * Use this to return a zero-length value and indicate that no
   * words are present in the requested dictionary.
   */
  if (!$start) return '';
  /**
   * The words list will end with a new directive, which will start
   * with "#". Locate the next "#" and thus find out where the
   * words end.
   */
  $end=strpos($words, "#", $start+1);
  $lang_words = substr($words, $start, $end-$start);
  return $lang_words;
}

/**
 * Saves user's dictionary (old format)
 *
 * This function operates the user dictionary. If the format is
 * clear-text, then it just reads the file and returns it. However, if
 * the file is encrypted (well, "garbled"), then it tries to decrypt
 * it, checks whether the decryption was successful, troubleshoots if
 * not, then returns the clear-text dictionary to the app.
 *
 * @return the contents of the user's ".words" file, decrypted if
 *         necessary.
 * @since 1.5.1 (sqspell 0.5)
 * @deprecated
 */
function sqspell_getWords_old(){
  global $SQSPELL_WORDS_FILE, $SQSPELL_CRYPTO;
  $words="";
  if (file_exists($SQSPELL_WORDS_FILE)){
    /**
     * Gobble it up.
     */
    $fp=fopen($SQSPELL_WORDS_FILE, 'r');
    $words=fread($fp, filesize($SQSPELL_WORDS_FILE));
    fclose($fp);
  }
  /**
   * Check if this is an encrypted file by looking for
   * the string "# SquirrelSpell" in it (the crypto
   * function does that).
   */
  if ($words && !strstr($words, "# SquirrelSpell")){
    /**
     * This file is encrypted or mangled. Try to decrypt it.
     * If fails, complain loudly.
     *
     * $old_key would be a value submitted by one of the modules with
     * the user's old mailbox password. I admin, this is rather dirty,
     * but efficient. ;)
     */
    sqgetGlobalVar('key', $key, SQ_COOKIE);
    sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

    sqgetGlobalVar('old_key', $old_key, SQ_POST);

    if ($old_key != '') {
        $clear_key=$old_key;
    } else {
      /**
       * Get user's password (the key).
       */
      $clear_key = OneTimePadDecrypt($key, $onetimepad);
    }
    /**
     * Invoke the decryption routines.
     */
    $words=sqspell_crypto_old("decrypt", $clear_key, $words);
    /**
     * See if decryption failed.
     */
    if ($words=="PANIC"){
      sqspell_handle_crypt_panic();
      // script execution stops here.
    } else {
      /**
       * OK! Phew. Set the encryption flag to true so we can later on
       * encrypt it again before saving to HDD.
       */
      $SQSPELL_CRYPTO=true;
    }
  } else {
    /**
     * No encryption is/was used. Set $SQSPELL_CRYPTO to false,
     * in case we have to save the dictionary later.
     */
    $SQSPELL_CRYPTO=false;
  }
  /**
   * Check if we need to upgrade the dictionary from version 0.2.x
   * This is going away soon.
   */
  if (strstr($words, "Dictionary v0.2")){
    $words=sqspell_upgradeWordsFile($words);
  }
  return $words;
}

/**
 * Saves user's dictionary
 * Function was replaced in 1.5.1 (sqspell 0.5).
 * Older function is suffixed with '_old'
 * @param array $words words that should be stored in dictionary
 * @param string $lang language
 */
function sqspell_writeWords($words,$lang){
  global $SQSPELL_CRYPTO,$username,$data_dir;

  $sWords = implode(',',$words);
  if ($SQSPELL_CRYPTO){
    /**
     * User wants to encrypt the file. So be it.
     * Get the user's password to use as a key.
     */
    sqgetGlobalVar('key', $key, SQ_COOKIE);
    sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

    $clear_key=OneTimePadDecrypt($key, $onetimepad);
    /**
     * Try encrypting it. If fails, scream bloody hell.
     */
    $save_words = sqspell_crypto("encrypt", $clear_key, $sWords);
    if ($save_words == 'PANIC'){
      // FIXME: handle errors here

    }
    $save_words='{crypt}'.$save_words;
  } else {
    $save_words=$sWords;
  }
  setPref($data_dir,$username,'sqspell_dict_'.$lang,$save_words);
}

/**
 * Writes user dictionary into the $username.words file, then changes mask
 * to 0600. If encryption is needed -- does that, too.
 *
 * @param  $words The contents of the ".words" file to write.
 * @return        void
 * @since 1.5.1 (sqspell 0.5)
 * @deprecated
 */
function sqspell_writeWords_old($words){
  global $SQSPELL_WORDS_FILE, $SQSPELL_CRYPTO;
  /**
   * if $words is empty, create a template entry by calling the
   * sqspell_makeDummy() function.
   */
  if (!$words){
    $words=sqspell_makeDummy();
  }
  if ($SQSPELL_CRYPTO){
    /**
     * User wants to encrypt the file. So be it.
     * Get the user's password to use as a key.
     */
    sqgetGlobalVar('key', $key, SQ_COOKIE);
    sqgetGlobalVar('onetimepad', $onetimepad, SQ_SESSION);

    $clear_key=OneTimePadDecrypt($key, $onetimepad);
    /**
     * Try encrypting it. If fails, scream bloody hell.
     */
    $save_words = sqspell_crypto("encrypt", $clear_key, $words);
    if ($save_words == 'PANIC'){
      /**
       * AAAAAAAAH! I'm not handling this yet, since obviously
       * the admin of the site forgot to compile the MCRYPT support in
       * when upgrading an existing PHP installation.
       * I will add a handler for this case later, when I can come up
       * with some work-around... Right now, do nothing. Let the Admin's
       * head hurt.. ;)))
       */
      /** save some hairs on admin's head and store error message in logs */
      error_log('SquirrelSpell: php does not have mcrypt support');
    }
  } else {
    $save_words = $words;
  }
  /**
   * Do the actual writing.
   */
  $fp=fopen($SQSPELL_WORDS_FILE, "w");
  fwrite($fp, $save_words);
  fclose($fp);
  chmod($SQSPELL_WORDS_FILE, 0600);
}

/**
 * Deletes user's dictionary
 * Function was modified in 1.5.1 (sqspell 0.5). Older function is suffixed
 * with '_old'
 * @param string $lang dictionary
 */
function sqspell_deleteWords($lang) {
  global $data_dir, $username;
  removePref($data_dir,$username,'sqspell_dict_'.$lang);
}

/**
 * Deletes user's dictionary when it is corrupted.
 * @since 1.5.1 (sqspell 0.5)
 * @deprecated
 */
function sqspell_deleteWords_old(){
  /**
   * So I open the door to my enemies,
   * and I ask can we wipe the slate clean,
   * but they tell me to please go...
   * uhm... Well, this just erases the user dictionary file.
   */
  global $SQSPELL_WORDS_FILE;
  if (file_exists($SQSPELL_WORDS_FILE)){
    unlink($SQSPELL_WORDS_FILE);
  }
}
/**
 * Creates an empty user dictionary for the sake of saving prefs or
 * whatever.
 *
 * @return The template to use when storing the user dictionary.
 * @deprecated
 */
function sqspell_makeDummy(){
  global $SQSPELL_VERSION, $SQSPELL_APP_DEFAULT;
  $words = "# SquirrelSpell User Dictionary $SQSPELL_VERSION\n"
     . "# Last Revision: " . date('Y-m-d')
     . "\n# LANG: $SQSPELL_APP_DEFAULT\n# End\n";
  return $words;
}

/**
 * This function checks for security attacks. A $MOD variable is
 * provided in the QUERY_STRING and includes one of the files from the
 * modules directory ($MOD.mod). See if someone is trying to get out
 * of the modules directory by providing dots, unicode strings, or
 * slashes.
 *
 * @param  string $rMOD the name of the module requested to include.
 * @return void, since it bails out with an access error if needed.
 */
function sqspell_ckMOD($rMOD){
  if (strstr($rMOD, '.')
      || strstr($rMOD, '/')
      || strstr($rMOD, '%')
      || strstr($rMOD, "\\")){
    echo _("Invalid URL");
    exit;
  }
}

/**
 * Used to check internal version of SquirrelSpell dictionary
 * @param integer $major main version number
 * @param integer $minor second version number
 * @return boolean true if stored dictionary version is $major.$minor or newer
 * @since 1.5.1 (sqspell 0.5)
 */
function sqspell_check_version($major,$minor) {
  global $data_dir, $username;
  // 0.4 version is internal version number that is used to indicate upgrade from
  // separate files to generic SquirrelMail prefs storage.
  $sqspell_version=getPref($data_dir,$username,'sqspell_version','0.4');

  $aVersion=explode('.',$sqspell_version);

  if ($aVersion[0] < $major ||
      ( $aVersion[0] == $major && $aVersion[1] < $minor)) {
    return false;
  }
  return true;
}

/**
 * Displays form that allows to enter different password for dictionary decryption.
 * If language is not set, function provides form to handle older dictionary files.
 * @param string $lang language
 * @since 1.5.1 (sqspell 0.5)
 */
function sqspell_handle_crypt_panic($lang=false) {
  if (! sqgetGlobalVar('SCRIPT_NAME',$SCRIPT_NAME,SQ_SERVER))
    $SCRIPT_NAME='';

  /**
   * AAAAAAAAAAAH!!!!! OK, ok, breathe!
   * Let's hope the decryption failed because the user changed his
   * password. Bring up the option to key in the old password
   * or wipe the file and start over if everything else fails.
   *
   * The _("SquirrelSpell...) line has to be on one line, otherwise
   * gettext will bork. ;(
   */
  $msg = html_tag( 'p', "\n" .
    '<strong>' . _("ATTENTION:") . '</strong><br />'
    .  _("SquirrelSpell was unable to decrypt your personal dictionary. This is most likely due to the fact that you have changed your mailbox password. In order to proceed, you will have to supply your old password so that SquirrelSpell can decrypt your personal dictionary. It will be re-encrypted with your new password after this. If you haven't encrypted your dictionary, then it got mangled and is no longer valid. You will have to delete it and start anew. This is also true if you don't remember your old password -- without it, the encrypted data is no longer accessible.") ,
    'left' ) .  "\n"
    . (($lang) ? html_tag('p',sprintf(_("Your %s dictionary is encrypted with password that differs from your current password."),
                                      sm_encode_html_special_chars($lang)),'left') : '')
    . '<blockquote>' . "\n"
    . '<form method="post" onsubmit="return AYS()">' . "\n"
    . '<input type="hidden" name="MOD" value="crypto_badkey" />' . "\n"
    . (($lang) ?
       '<input type="hidden" name="dict_lang" value="'.sm_encode_html_special_chars($lang).'" />' :
       '<input type="hidden" name="old_setup" value="yes" />')
    . html_tag( 'p',  "\n" .
        '<input type="checkbox" name="delete_words" value="ON" id="delete_words" />'
        . '<label for="delete_words">'
        . _("Delete my dictionary and start a new one")
        . '</label><br /><label for="old_key">'
        . _("Decrypt my dictionary with my old password:")
        . '</label><input type="text" name="old_key" id="old_key" size="10" />' ,
        'left' ) . "\n"
        . '</blockquote>' . "\n"
        . html_tag( 'p', "\n"
               . '<input type="submit" value="'
               . _("Proceed") . ' &gt;&gt;" />' ,
           'center' ) . "\n"
         . '</form>' . "\n";
  /**
   * Add some string vars so they can be i18n'd.
   */
  $msg .= "<script type=\"text/javascript\"><!--\n"
    . "var ui_choice = \"" . _("You must make a choice") ."\";\n"
    . "var ui_candel = \"" . _("You can either delete your dictionary or type in the old password. Not both.") . "\";\n"
    . "var ui_willdel = \"" . _("This will delete your personal dictionary file. Proceed?") . "\";\n"
    . "//--></script>\n";
  /**
   * See if this happened in the pop-up window or when accessing
   * the SpellChecker options page.
   * This is a dirty solution, I agree.
   * TODO: make this prettier.
   */
  if (strstr($SCRIPT_NAME, "sqspell_options")){
    sqspell_makePage(_("Error Decrypting Dictionary"),
                     "decrypt_error.js", $msg);
  } else {
    sqspell_makeWindow(null, _("Error Decrypting Dictionary"),
                       "decrypt_error.js", $msg);
  }
  exit;
}

/**
 * SquirrelSpell version. Don't modify, since it identifies the format
 * of the user dictionary files and messing with this can do ugly
 * stuff. :)
 * @global string $SQSPELL_VERSION
 * @deprecated
 */
$SQSPELL_VERSION="v0.3.8";
