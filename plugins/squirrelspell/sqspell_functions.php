<?php
/**
   SQSPELL_FUNCTIONS.PHP
   --------------
   All SquirrelSpell-wide functions are in this file.
   								**/
								
function sqspell_makePage($title, $scriptsrc, $body){
 //
 // GUI wrap-around for the OPTIONS page.
 //
 global $color, $SQSPELL_VERSION, $MOD;
 displayPageHeader($color, "None");
 ?>
 &nbsp;<br>
 <?php if($scriptsrc) { ?>
  <script type="text/javascript" src="js/<?php echo $scriptsrc ?>"></script>
 <?php } ?>
 <table width="95%" align="center" border="0" cellpadding="2" cellspacing="0">
  <tr>
   <td bgcolor="<?php echo $color[9] ?>" align="center">
      <strong><?php echo $title ?></strong>
   </td>
  </tr>
  <tr><td><hr></td></tr>
  <tr><td>
   <?php echo $body ?>
  </td></tr>
  <?php if ($MOD!="options_main"){ 
   // Generate a nice return-to-main link.
   ?>
   <tr><td><hr></td></tr>
   <tr><td align="center"><a href="sqspell_options.php">Back to &quot;SpellChecker Options&quot; page</a></td></tr>
  <?php } ?>
  <tr><td><hr></td></tr>
  <tr>
   <td bgcolor="<?php echo $color[9] ?>" align="center">
      SquirrelSpell <?php echo $SQSPELL_VERSION ?>
   </td>
  </tr>
 </table>
 <?php
}

function sqspell_makeWindow($onload, $title, $scriptsrc, $body){
 //
 // GUI wrap-around for the pop-up window interface.
 //
 global $color, $SQSPELL_VERSION;
 ?>
 <html>
  <head>
   <title><?php echo $title ?></title>
   <?php if ($scriptsrc){ ?>
    <script type="text/javascript" src="js/<?php echo $scriptsrc ?>"></script>
   <?php } ?>
  </head>
  <body text="<?php echo $color[8] ?>" 
        bgcolor="<?php echo $color[4] ?>" 
	link="<?php echo $color[7] ?>" 
	vlink="<?php echo $color[7] ?>" 
	alink="<?php echo $color[7] ?>"<?php
	if ($onload) echo " onload=\"$onload\""; ?>>
   <table width="100%" border="0" cellpadding="2">
    <tr>
     <td bgcolor="<?php echo $color[9] ?>" align="center">
      <strong><?php echo $title ?></strong>
     </td>
    </tr>
    <tr>
     <td><hr></td>
    </tr>
    <tr>
     <td>
      <?php echo $body ?>
     </td>
    </tr>
    <tr>
     <td><hr></td>
    </tr>
    <tr>
     <td bgcolor="<?php echo $color[9] ?>" align="center">
      SquirrelSpell <?php echo $SQSPELL_VERSION ?>
     </td>
    </tr>
   </table>
  </body>
 </html>
 <?php
}

function sqspell_crypto($mode, $ckey, $input){
 //
 // This function does the encryption and decryption of the user
 // dictionary. It is only available when PHP is compiled
 // --with-mcrypt. See doc/CRYPTO for more information.
 //
 if (!function_exists(mcrypt_generic)) return "PANIC";
 $td = mcrypt_module_open(MCRYPT_Blowfish, "", MCRYPT_MODE_ECB, "");
 $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
 mcrypt_generic_init($td, $ckey, $iv);
 switch ($mode){
  case "encrypt":
   $crypto = mcrypt_generic($td, $input);
  break;
  case "decrypt":
   $crypto = mdecrypt_generic($td, $input);
   // See if it decrypted successfully. If so, it should contain
   // the string "# SquirrelSpell".
   if (!strstr($crypto, "# SquirrelSpell")) $crypto="PANIC";
  break;
 }
 mcrypt_generic_end ($td);
 return $crypto;
}

function sqspell_upgradeWordsFile($words_string){
 //
 // This function transparently upgrades the 0.2 dictionary format to 
 // 0.3, since user-defined languages have been added in 0.3 and
 // the new format keeps user dictionaries selection in the file.
 //
 global $SQSPELL_APP_DEFAULT, $SQSPELL_VERSION;
 
 // Define just one dictionary for this user -- the default.
 // If the user wants more, s/he can set them up in personal
 // preferences. See doc/UPGRADING for more info.
 $new_words_string=substr_replace($words_string, "# SquirrelSpell User Dictionary $SQSPELL_VERSION\n# Last Revision: " . date("Y-m-d") . "\n# LANG: $SQSPELL_APP_DEFAULT\n# $SQSPELL_APP_DEFAULT", 0, strpos($words_string, "\n")) . "# End\n";
 sqspell_writeWords($new_words_string);
 return $new_words_string;
}

function sqspell_getSettings($words){
 //
 // Right now it just returns an array with the dictionaries 
 // available to the user for spell-checking. It will probably
 // do more in the future, as features are added.
 //
 global $SQSPELL_APP, $SQSPELL_APP_DEFAULT;
 if (sizeof($SQSPELL_APP) > 1){
  // OK, so there are more than one dictionary option.
  // Now load the user prefs.
  if(!$words) $words=sqspell_getWords();
  if ($words){
   // find which dictionaries user wants to use
   preg_match("/# LANG: (.*)/i", $words, $matches);
   $langs=explode(", ", $matches[1]);
  } else {
   // User doesn't have a personal dictionary. Set him up with
   // a default setting.
   $langs[0]=$SQSPELL_APP_DEFAULT;
  }
 } else {
  // There is only one dictionary defined system-wide.
  $langs[0]=$SQSPELL_APP_DEFAULT;
 }
 return $langs;
}

function sqspell_getLang($words, $lang){
 //
 // Returns words of a specific user dictionary.
 //
 $start=strpos($words, "# $lang\n");
 if (!$start) return "";
 $end=strpos($words, "#", $start+1);
 $lang_words = substr($words, $start, $end-$start);
 return $lang_words;
}
 
function sqspell_getWords(){
 //
 // This baby operates the user dictionary. If the format is clear-text,
 // then it just reads the file and returns it. However, if the file is
 // encrypted, then it decrypts it, checks whether the decryption was 
 // successful, troubleshoots if not, then returns the clear-text dictionary
 // to the app.
 //
 global $SQSPELL_WORDS_FILE, $SQSPELL_CRYPTO;
 $words="";
 if (file_exists($SQSPELL_WORDS_FILE)){
  // Gobble it up.
  $fp=fopen($SQSPELL_WORDS_FILE, "r");
  $words=fread($fp, filesize($SQSPELL_WORDS_FILE));
  fclose($fp);
 }
 // Check if this is an encrypted file by looking for
 // the string "# SquirrelSpell" in it.
 if ($words && !strstr($words, "# SquirrelSpell")){
  // This file is encrypted or mangled. Try to decrypt it.
  // If fails, raise hell.
  global $key, $onetimepad, $old_key;
  if ($old_key) {
   // an override in case user is trying to decrypt a dictionary
   // with his old password
   $clear_key=$old_key;
  } else {
   // get user's password (the key).
   $clear_key = OneTimePadDecrypt($key, $onetimepad);
  }
  // decrypt
  $words=sqspell_crypto("decrypt", $clear_key, $words);
  if ($words=="PANIC"){
   // AAAAAAAAAAAH!!!!! OK, ok, breathe!
   // Let's hope the decryption failed because the user changed his
   // password. Bring up the option to key in the old password
   // or wipe the file and start over if everything else fails.
   $msg="<p>
    <strong>ATTENTION:</strong><br>
    SquirrelSpell was unable to decrypt your personal dictionary. This is most likely
    due to the fact that you have changed your mailbox password. In order to proceed,
    you will have to supply your old password so that SquirrelSpell can decrypt your
    personal dictionary. It will be re-encrypted with your new password after this.<br>
    If you haven't encrypted your dictionary, then it got mangled and is no longer
    valid. You will have to delete it and start anew. This is also true if you don't
    remember your old password -- without it, the encrypted data is no longer 
    accessible.</p>
    <blockquote>
    <form method=\"post\" onsubmit=\"return AYS()\">
     <input type=\"hidden\" name=\"MOD\" value=\"crypto_badkey\">
     <p><input type=\"checkbox\" name=\"delete_words\" value=\"ON\"> Delete my dictionary and start a new one<br>
     Decrypt my dictionary with my old password: <input name=\"old_key\" size=\"10\"></p>
    </blockquote>
     <p align=\"center\"><input type=\"submit\" value=\"Proceed &gt;&gt;\"></p>
    </form>
   ";
   // See if this happened in the pop-up window or when accessing
   // the SpellChecker options page. 
   global $SCRIPT_NAME;
   if (strstr($SCRIPT_NAME, "sqspell_options"))
   	sqspell_makePage("Error Decrypting Dictionary", "decrypt_error.js", $msg);
   else sqspell_makeWindow(null, "Error Decrypting Dictionary", "decrypt_error.js", $msg); 
   exit;
  } else {
   // OK! Phew. Set the encryption flag to true so we can later on 
   // encrypt it again before saving to HDD.
   $SQSPELL_CRYPTO=true;
  }
 } else {
  // No encryption is used. Set $SQSPELL_CRYPTO to false, in case we have to
  // save the dictionary later.
  $SQSPELL_CRYPTO=false;
 }
 // Check if we need to upgrade the dictionary from version 0.2.x
 if (strstr($words, "Dictionary v0.2")) $words=sqspell_upgradeWordsFile($words);
 return $words;
}

function sqspell_writeWords($words){
 //
 // Writes user dictionary into the $username.words file, then changes mask
 // to 0600. If encryption is needed -- does that, too.
 //
 global $SQSPELL_WORDS_FILE, $SQSPELL_CRYPTO;
 // if $words is empty, create a template entry.
 if (!$words) $words=sqspell_makeDummy();
 if ($SQSPELL_CRYPTO){
  // User wants to encrypt the file. So be it.
  // get his password to use as a key.
  global $key, $onetimepad;
  $clear_key=OneTimePadDecrypt($key, $onetimepad);
  // Try encrypting it. If fails, scream bloody hell.
  $save_words = sqspell_crypto("encrypt", $clear_key, $words);
  if ($save_words=="PANIC"){
   // AAAAAAAAH! I'm not handling this yet, since obviously
   // the admin of the site forgot to compile the MCRYPT support in.
   // I will add a handler for this case later, when I can come up
   // with some work-around... Right now, do nothing. Let the Admin's
   // head hurt.. ;)))
  }
 } else {
  $save_words = $words;
 }
 $fp=fopen($SQSPELL_WORDS_FILE, "w");
 fwrite($fp, $save_words);
 fclose($fp);
 chmod($SQSPELL_WORDS_FILE, 0600);
}

function sqspell_deleteWords(){
 //
 // so I open the door to my enemies,
 // and I ask can we wipe the slate clean,
 // but they tell me to please go...
 // uhm... Well, this just erases the user dictionary file.
 //
 global $SQSPELL_WORDS_FILE;
 if (file_exists($SQSPELL_WORDS_FILE)) unlink($SQSPELL_WORDS_FILE);
}

function sqspell_makeDummy(){
 //
 // Creates an empty user dictionary for the sake of saving prefs or
 // whatever.
 //
 global $SQSPELL_VERSION, $SQSPELL_APP_DEFAULT;
 $words="# SquirrelSpell User Dictionary $SQSPELL_VERSION\n# Last Revision: " . date("Y-m-d") . "\n# LANG: $SQSPELL_APP_DEFAULT\n# End\n"; 
 return $words;
}

/** 
   VERSION:
   ---------
   SquirrelSpell version. Don't modify, since it identifies the format
   of the user dictionary files and messing with this can do ugly 
   stuff. :)
   								**/
$SQSPELL_VERSION="v0.3.5";


?>
