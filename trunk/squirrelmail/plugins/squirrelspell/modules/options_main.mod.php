<?php
/**
   OPTIONS_MAIN.MOD.PHP
   ---------------------
   Default page called when accessing SquirrelSpell's options.
   								**/
 // E_ALL: protection behind 3000 miles.
 global $SQSPELL_APP;

 $msg = "<p>Please choose which options you wish to set up:</p>
 <ul>
  <li><a href=\"sqspell_options.php?MOD=edit_dic\">Edit your personal dictionary</a></li>
 ";
 // See if more than one dictionary is defined system-wide.
 // If so, let the user choose his preferred ones.
 if (sizeof($SQSPELL_APP)>1)
 	$msg .= "<li><a href=\"sqspell_options.php?MOD=lang_setup\">Set up international dictionaries</a></li>\n";
 // See if MCRYPT is available.
 // If so, let the user choose whether s/he wants to encrypt the
 // personal dictionary file.
 if (function_exists("mcrypt_generic"))
 	$msg .= "<li><a href=\"sqspell_options.php?MOD=enc_setup\">Encrypt or decrypt your personal dictionary</a></li>\n";
	else $msg .= "<li>Encrypt or decrypt your personal dictionary <em>(not available)</em></li>\n";
 $msg .= "</ul>\n";
 sqspell_makePage("SquirrelSpell Options Menu", null, $msg);

?>
