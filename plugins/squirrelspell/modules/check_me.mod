<?php
/**
 * check_me.mod
 * -------------
 * Squirrelspell module.
 *
 * Copyright (c) 1999-2003 The SquirrelMail development team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This module is the main workhorse of SquirrelSpell. It submits
 * the message to the spell-checker, parses the output, and loads
 * the interface window.
 *
 * $Id$
 *
 * @author Konstantin Riabitsev <icon@duke.edu> ($Author$)
 * @version $Date$
 */

/**
 * This function makes a javascript-powered link. Not sure why
 * Philippe decided to move it outside the main code, but hey. ;)
 * I bet for the i18n reasons.
 *
 * @param  $jscode Javascript code to include in the link.
 * @param  $title  A little pop-up title to provide for the links.
 * @param  $link   The content of the link.
 * @return         void, since this just draws the content.
 */
function SpellLink($jscode, $title, $link) {
  echo "<td><a href=\"javascript:$jscode\" "
    . "title=\"$title\">$link</a>"
    . '</td>';
}

/**
 * Declaring globals for users with E_ALL set.
 */
global $SQSPELL_APP, $attachment_dir, $SQSPELL_EREG, $color;

$sqspell_text = $_POST['sqspell_text'];
$sqspell_use_app = $_POST['sqspell_use_app'];

/**
 * Now we explode the lines for three reasons:
 * 1) So we can ignore lines starting with ">" (reply's)
 * 2) So we can stop processing when we get to "--" on a single line,
 *    which means that the signature is starting
 * 3) So we can add an extra space at the beginning of each line. This way
 *    ispell/aspell don't treat these as command characters.
 */
$sqspell_raw_lines = explode("\n", $sqspell_text);
for ($i=0; $i<sizeof($sqspell_raw_lines); $i++){
  /**
   * See if the signature is starting, which will be a "--" on the
   * single line (after trimming).
   */
  if (trim($sqspell_raw_lines[$i]) == '--'){
    break;
  }
  /**
   * See if this is quoted text. Don't check the quoted text, since
   * it's no business of ours how badly our correspondents misspell
   * stuff.
   */
  if(substr($sqspell_raw_lines[$i], 0, 1) != '>'){
    $sqspell_new_lines[$i] = ' ' . $sqspell_raw_lines[$i];
  } else {
    $sqspell_new_lines[$i] = '';
  }
}
/**
 * $sqspell_new_lines array now contains the lines to submit to the
 * spellchecker.
 */
$sqspell_new_text=implode("\n", $sqspell_new_lines);

/**
 * Define the command used to spellcheck the document.
 */
$sqspell_command=$SQSPELL_APP[$sqspell_use_app];
/**
 * For the simplicity's sake we'll put all text into a file in
 * attachment_dir directory, then cat it and pipe it to
 * sqspell_command.  There are other ways to do it, including popen(),
 * but it's unidirectional and no fun at all.  
 *
 * The name of the file is an md5 hash of the message itself plus
 * microtime. This prevents symlink attacks. The loop is here to
 * further enhance this feature, and make sure we don't overwrite
 * someone else's data, although the possibility of this happening is
 * QUITE remote.
 */
do {
  $floc = "$attachment_dir/" . md5($sqspell_new_text . microtime());
} while (file_exists($floc));
/**
 * Write the contents to the file.
 */
$fp=fopen($floc, 'w');
fwrite($fp, $sqspell_new_text);
fclose($fp);
/**
 * Execute ispell/aspell and catch the output.
 */
exec("cat $floc | $sqspell_command 2>&1", $sqspell_output, $sqspell_exitcode);
/**
 * Remove the temp file.
 */
unlink($floc);

/**
 * Check if the execution was successful. Bail out if it wasn't.
 */
if ($sqspell_exitcode){
  $msg= "<div align='center'>"
     . sprintf(_("I tried to execute '%s', but it returned:"),
               $sqspell_command) . "<pre>"
     . nl2br(join("\n", $sqspell_output)) . "</pre>"
     . "<form onsubmit=\"return false\">"
     . "<input type=\"submit\" value=\"  " . _("Close")
     . "  \" onclick=\"self.close()\"></form></div>";
  sqspell_makeWindow(null, _("SquirrelSpell is misconfigured."), null, $msg);
  exit;
}

/**
 * Load the user dictionary.
 */
$words=sqspell_getLang(sqspell_getWords(), $sqspell_use_app);
/**
 * Define some variables to be used during the processing.
 */
$current_line=0;
$missed_words=Array();
$misses = Array();
$locations = Array();
$errors=0;
/**
 * Now we process the output of sqspell_command (ispell or aspell in
 * ispell compatibility mode, whichever). I'm going to be scarce on
 * comments here, since you can just look at the ispell/aspell output
 * and figure out what's going on. ;) The best way to describe this is
 * "Dark Magic".
 */
for ($i=0; $i<sizeof($sqspell_output); $i++){
  switch (substr($sqspell_output[$i], 0, 1)){
  /**
   * Line is empty.
   * Ispell adds empty lines when an end of line is reached
   */
  case '':
    $current_line++;
  break;
  /**
   * Line begins with "&".
   * This means there's a misspelled word and a few suggestions.
   */
  case '&':
    list($left, $right) = explode(": ", $sqspell_output[$i]);
    $tmparray = explode(" ", $left);
    $sqspell_word=$tmparray[1];
    /**
     * Check if the word is in user dictionary.
     */
    if (!$SQSPELL_EREG("\n$sqspell_word\n", $words)){
      $sqspell_symb=intval($tmparray[3])-1;
      if (!isset($misses[$sqspell_word])) {
        $misses[$sqspell_word] = $right;
        $missed_words[$errors] = $sqspell_word;
        $errors++;
      }
      if (isset($locations[$sqspell_word])){
        $locations[$sqspell_word] .= ', ';
      } else { 
        $locations[$sqspell_word] = '';
      }
      $locations[$sqspell_word] .= "$current_line:$sqspell_symb";
    }
  break;
  /**
   * Line begins with "#".
   * This means a misspelled word and no suggestions.
   */
  case '#':
    $tmparray = explode(" ", $sqspell_output[$i]);
    $sqspell_word=$tmparray[1];
    /**
     * 
     * Check if the word is in user dictionary.
     */
    if (!$SQSPELL_EREG("\n$sqspell_word\n", $words)){
      $sqspell_symb=intval($tmparray[2])-1;
      if (!isset($misses[$sqspell_word])) {
	    $misses[$sqspell_word] = '_NONE';
	    $missed_words[$errors] = $sqspell_word;
	    $errors++;
      }
      if (isset($locations[$sqspell_word])) {
	    $locations[$sqspell_word] .= ', ';
      } else {
	    $locations[$sqspell_word] = '';
	  }
      $locations[$sqspell_word] .= "$current_line:$sqspell_symb";
    }
  break;
  }
}

if ($errors){
  /**
   * So, there are errors
   * This is the only place where the generic GUI-wrapper is not
   * called, but generated right here. This is due to the complexity
   * of the output.
   */
  echo "<html>\n"
    . "<head>\n"
    . '<title>' . _("SquirrelSpell Results") . '</title>';
  /**
   * Check if there are user-defined stylesheets.
   */
  if ($theme_css != '') {
    echo "<LINK REL=\"stylesheet\" TYPE=\"text/css\" HREF=\"$theme_css\">\n";
  }
  /**
   * Load the spelling errors into JavaScript arrays
   * (More dark magic!)
   */
  echo "<script type=\"text/javascript\">\n"
    . "<!--\n";
  
  $sqspell_lines = explode("\n", $sqspell_text);
  /**
   * The javascript array sqspell_lines[] contains all lines of
   * the message we've been checking.
   */
  echo "var sqspell_lines=new Array();\n";
  for ($i=0; $i<sizeof($sqspell_lines); $i++){
    echo "sqspell_lines[$i] = \"" 
      . chop(addslashes($sqspell_lines[$i])) . "\";\n";
  }  
  echo "\n\n";

  /**
   * The javascript array misses[] contais all misspelled words.
   */
  echo "var misses=new Array();\n";
  for ($i=0; $i<sizeof($missed_words); $i++){
    echo "misses[$i] = \"" . $missed_words[$i] . "\";\n";
  }
  echo "\n\n";
  
  /**
   * Suggestions are (guess what!) suggestions for misspellings
   */
  echo "var suggestions = new Array();\n";
  $i=0;
  while (list($word, $value) = each($misses)){
    if ($value=='_NONE') $value='';
    echo "suggestions[$i] = \"$value\";\n";
    $i++;
  }
  echo "\n\n";

  /**
   * Locations are where those misspellings are located, line:symbol
   */
  echo "var locations= new Array();\n";
  $i=0;
  while (list($word, $value) = each($locations)){
    echo "locations[$i] = \"$value\";\n";
    $i++;
  }

  /** 
   * Add some strings so they can be i18n'd.
   */
  echo "var ui_completed = \"" . _("Spellcheck completed. Commit changes?")
    . "\";\n";
  echo "var ui_nochange = \"" . _("No changes were made.") . "\";\n";
  echo "var ui_wait = \"" 
    . _("Now saving your personal dictionary... Please wait.")
    . "\";\n";
  

  /**
   * Did I mention that I hate dots on the end of contcatenated lines?
   * Dots at the beginning make so much more sense!
   */
  echo "//-->\n"
    . "</script>\n"
    . "<script src=\"js/check_me.js\" type=\"text/javascript\"></script>\n"
    . "</head>\n";
  
  echo "<body bgcolor=\"$color[4]\" text=\"$color[8]\" link=\"$color[7]\" "
    . "alink=\"$color[7]\" vlink=\"$color[7]\" "
    . "onload=\"populateSqspellForm()\">\n";
  ?>
  <table width="100%" border="0" cellpadding="2">
   <tr>
    <td bgcolor="<?php echo $color[9] ?>" align="center">
     <b>
      <?php printf( _("Found %s errors"), $errors ) ?>
     </b>
    </td>
   </tr>
   <tr>
    <td>
      <hr>
    </td>
   </tr>
   <tr>
    <td>
     <form method="post">
      <input type="hidden" name="MOD" value="forget_me_not" />
      <input type="hidden" name="words" value="" />
      <input type="hidden" name="sqspell_use_app" 
             value="<?php echo $sqspell_use_app ?>" />
      <table border="0" width="100%">
       <tr align="center">
        <td colspan="4">
         <?php
          $sptag = "<span style=\"background-color: $color[9]\">";
          echo $sptag . _("Line with an error:") . '</span>';
         ?>
         <br />
         <textarea name="sqspell_line_area" cols="50" rows="3" 
                   wrap="hard" onfocus="this.blur()"></textarea>
        </td>
       </tr>
       <tr valign="middle">
        <td align="right" width="25%">
         <?php
          echo $sptag . _("Error:") . '</span>';
         ?>
        </td>
        <td align="left" width="25%">
         <input name="sqspell_error" size="10" value="" 
                onfocus="this.blur()" />
        </td>
        <td align="right" width="25%">
         <?php
          echo $sptag . _("Suggestions:") . '</span>';
         ?>
        </td>
        <td align="left" width="25%">
         <select name="sqspell_suggestion" 
                 onchange="if (this.options[this.selectedIndex].value != '_NONE') document.forms[0].sqspell_oruse.value=this.options[this.selectedIndex].value">
          <?php
           echo '<option>' . _("Suggestions") . '</option>';
          ?>
         </select>
        </td>
       </tr>
       <tr>
        <td align="right">
         <?php
          echo $sptag . _("Change to:") . '</span>';
         ?>
        </td>
        <td align="left">
         <input name="sqspell_oruse" size="15" value=""
                onfocus="if(!this.value) this.value=document.forms[0].sqspell_error.value">
        </td>
        <td align="right">
         <?php
          echo $sptag . _("Occurs times:") . '</span>';
         ?>
        </td>
        <td align="left">
         <input name="sqspell_likethis" size=3 value="" onfocus="this.blur()">
        </td>
       </tr>
        <!-- hello? What is this? </td></tr> -->
       <tr>
        <td colspan="4"><hr></td>
       </tr>
       <tr>
        <td colspan="4">
         <table border="0" cellpadding="0" cellspacing="3" width="100%">
	  <tr align="center" bgcolor="<?php echo $color[9] ?>">
           <?php
	    SpellLink('sqspellChange()',
		      _("Change this word"),
		      _("Change"));
	    SpellLink('sqspellChangeAll()',
		      _("Change ALL occurances of this word"),
		      _("Change All"));
	    SpellLink('sqspellIgnore()',
		      _("Ignore this word"),
		      _("Ignore"));
	    SpellLink('sqspellIgnoreAll()',
		      _("Ignore ALL occurances this word"),
		      _("Ignore All"));
	    SpellLink('sqspellRemember()',
		      _("Add this word to your personal dictionary"),
		      _("Add to Dic"));
           ?>
          </tr>
         </table>
        </td>
       </tr>
       <tr>
        <td colspan="4"><hr></td>
       </tr>
       <tr>
	<td colspan="4" align="center" bgcolor="<?php echo $color[9] ?>">
	 <?php
	  echo '<input type="button" value="  '
	    . _("Close and Commit")
	    . '  " onclick="if (confirm(\''
	    . _("The spellcheck is not finished. Really close and commit changes?")
	    . '\')) sqspellCommitChanges()">'
            . ' <input type="button" value="  '
            . _("Close and Cancel")
            . '  " onclick="if (confirm(\''
            . _("The spellcheck is not finished. Really close and discard changes?")
            . '\')) self.close()">';
         ?>
        </td>
       </tr>
      </table>
     </form>
    </td>
   </tr>
  </table>
  </body></html>
  <?php
} else {
  /**
   * AREN'T YOU SUCH A KNOW-IT-ALL!
   */
  $msg="<form onsubmit=\"return false\"><div align=\"center\">"
     . "<input type=\"submit\" value=\"  " . _("Close") 
     . "  \" onclick=\"self.close()\"></div></form>";
  sqspell_makeWindow(null, _("No errors found"), null, $msg);
}

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 */
?>
