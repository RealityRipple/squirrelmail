<?php

/**
 * check_me.mod
 *
 * Squirrelspell module.
 *
 * This module is the main workhorse of SquirrelSpell. It submits
 * the message to the spell-checker, parses the output, and loads
 * the interface window.
 *
 * @author Konstantin Riabitsev <icon at duke.edu>
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
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
global $SQSPELL_APP_DEFAULT, $SQSPELL_APP, $SQSPELL_SPELLCHECKER,
  $SQSPELL_FORCE_POPEN, $attachment_dir, $color;

if (! sqgetGlobalVar('sqspell_text',$sqspell_text,SQ_POST)) {
  $sqspell_text = '';
}
if (! sqgetGlobalVar('sqspell_use_app',$sqspell_use_app,SQ_POST)) {
  $sqspell_use_app = $SQSPELL_APP_DEFAULT;
}

/**
 * Now we explode the lines for two reasons:
 * 1) So we can ignore lines starting with ">" (reply's)
 * 2) So we can stop processing when we get to "--" on a single line,
 *    which means that the signature is starting
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
    $sqspell_new_lines[$i] = $sqspell_raw_lines[$i];
  } else {
    $sqspell_new_lines[$i] = '';
  }
}
/**
 * $sqspell_new_lines array now contains the lines to submit to the
 * spellchecker.
 */
$sqspell_new_text=implode("\n", $sqspell_new_lines);

include_once(SM_PATH . 'plugins/squirrelspell/class/common.php');

$aParams = array();
$aParams['words'] = sqspell_getLang($sqspell_use_app);

if ($SQSPELL_SPELLCHECKER===1) {
    include_once(SM_PATH . 'plugins/squirrelspell/class/php_pspell.php');
    $aParams['dictionary'] = $SQSPELL_APP[$sqspell_use_app];
    $aParams['charset'] = $default_charset;
    $check = new php_pspell($aParams);
} else {
    include_once(SM_PATH . 'plugins/squirrelspell/class/cmd_spell.php');
    $aParams['spell_command'] = $SQSPELL_APP[$sqspell_use_app];
    if ($SQSPELL_FORCE_POPEN) {
        $aParams['use_proc_open'] = false;
    } else {
        $aParams['use_proc_open'] = check_php_version(4,3);
    }
    $aParams['temp_dir'] = $attachment_dir;
    $aParams['debug'] = false;
    $check = new cmd_spell($aParams);
}

/**
 * Check for class constructor function errors
 */
if (!empty($check->error)) {
  $msg= '<div style="text-align: center;">'
      . nl2br(sm_encode_html_special_chars($check->error))
     . '<form onsubmit="return false">'
     . '<input type="submit" value="  ' . _("Close")
     . '  " onclick="self.close()" /></form></div>';
  sqspell_makeWindow(null, _("SquirrelSpell is misconfigured."), null, $msg);
  exit;
}

$missed_words=Array();
$misses = Array();
$locations = Array();
$errors=0;
$results = $check->check_text($sqspell_new_text);

/**
 * Check for execution errors
 */
if (!empty($check->error)) {
  $msg= '<div style="text-align: center;">'
      . nl2br(sm_encode_html_special_chars($check->error))
     . '<form onsubmit="return false">'
     . '<input type="submit" value="  ' . _("Close")
     . '  " onclick="self.close()" /></form></div>';
  sqspell_makeWindow(null, _("SquirrelSpell is misconfigured."), null, $msg);
  exit;
}

if (is_array($results)) {
    // convert variables to old style squirrelspell results
    if (!empty($results)) {
        foreach(array_keys($results) as $word) {
            if (isset($results[$word]['locations'])) {
                $missed_words[] = $word;
                $locations[$word] = implode(', ',$results[$word]['locations']);
                if (isset($results[$word]['suggestions'])) {
                    $misses[$word] = implode(', ',$results[$word]['suggestions']);
                } else {
                    $misses[$word] = '_NONE';
                }
            } else {
                // $word without 'locations'. ignore it
            }
        }
        $errors = count($missed_words);
    }
} else {
    if (!empty($check->error)) {
        $error_msg = nl2br(sm_encode_html_special_chars($check->error));
    } else {
        $error_msg = _("Unknown error");
    }
    $msg= '<div style="text-align: center;">'
        . $error_msg
     . '<form onsubmit="return false">'
     . '<input type="submit" value="  ' . _("Close")
     . '  " onclick="self.close()" /></form></div>';
    sqspell_makeWindow(null, _("SquirrelSpell error."), null, $msg);
    exit;
}

if ($errors){
  /**
   * Load the spelling errors into JavaScript arrays
   * (More dark magic!)
   */
  $extrajs="<script type=\"text/javascript\">\n"
    . "<!--\n";

  $sqspell_lines = explode("\n", $sqspell_text);
  /**
   * The javascript array sqspell_lines[] contains all lines of
   * the message we've been checking.
   */
  $extrajs.= "var sqspell_lines=new Array();\n";
  for ($i=0; $i<sizeof($sqspell_lines); $i++){
    // use addcslashes for compatibility with magic_quotes_sybase
    $extrajs.= "sqspell_lines[$i] = \""
      . chop(addcslashes($sqspell_lines[$i], ">'\"\\\x0")) . "\";\n";
  }
  $extrajs.= "\n\n";

  /**
   * The javascript array misses[] contais all misspelled words.
   */
  $extrajs.= "var misses=new Array();\n";
  for ($i=0; $i<sizeof($missed_words); $i++){
    $extrajs.= "misses[$i] = \"" . $missed_words[$i] . "\";\n";
  }
  $extrajs.= "\n\n";

  /**
   * Suggestions are (guess what!) suggestions for misspellings
   */
  $extrajs.= "var suggestions = new Array();\n";
  $i=0;
  while (list($word, $value) = each($misses)){
    if ($value=='_NONE') $value='';
    $extrajs.= "suggestions[$i] = \"$value\";\n";
    $i++;
  }
  $extrajs.= "\n\n";

  /**
   * Locations are where those misspellings are located, line:symbol
   */
  $extrajs.= "var locations= new Array();\n";
  $i=0;
  while (list($word, $value) = each($locations)){
    $extrajs.= "locations[$i] = \"$value\";\n";
    $i++;
  }

  /**
   * Add some strings so they can be i18n'd.
   */
  $extrajs.= "var ui_completed = \"" . _("Spellcheck completed. Commit changes?")
    . "\";\n";
  $extrajs.= "var ui_nochange = \"" . _("No changes were made.") . "\";\n";
  $extrajs.= "var ui_wait = \""
    . _("Now saving your personal dictionary... Please wait.")
    . "\";\n";


  /**
   * Did I mention that I hate dots on the end of concatenated lines?
   * Dots at the beginning make so much more sense!
   */
  $extrajs.= "//-->\n"
    . "</script>\n"
    . "<script src=\"js/check_me.js\" type=\"text/javascript\"></script>\n";


  displayHtmlHeader(_("SquirrelSpell Results"),$extrajs);

  echo "<body bgcolor=\"$color[4]\" text=\"$color[8]\" link=\"$color[7]\" "
    . "alink=\"$color[7]\" vlink=\"$color[7]\" "
    . "onload=\"populateSqspellForm()\">\n";
  ?>
  <table width="100%" border="0" cellpadding="2">
   <tr>
    <td bgcolor="<?php echo $color[9] ?>" align="center">
     <b>
      <?php printf( ngettext("Found %d error","Found %d errors",$errors), $errors ) ?>
     </b>
    </td>
   </tr>
   <tr>
    <td>
      <hr />
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
                   onfocus="this.blur()"></textarea>
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
                onfocus="if(!this.value) this.value=document.forms[0].sqspell_error.value" />
        </td>
        <td align="right">
         <?php
          echo $sptag . _("Occurs times:") . '</span>';
         ?>
        </td>
        <td align="left">
         <input name="sqspell_likethis" size=3 value="" onfocus="this.blur()" />
        </td>
       </tr>
        <!-- hello? What is this? </td></tr> -->
       <tr>
        <td colspan="4"><hr /></td>
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
        <td colspan="4"><hr /></td>
       </tr>
       <tr>
        <td colspan="4" align="center" bgcolor="<?php echo $color[9] ?>">
         <?php
             echo '<input type="button" value="  '
                 . _("Close and Commit")
                 . '  " onclick="if (confirm(\''
                 . _("The spellcheck is not finished. Really close and commit changes?")
                 . '\')) sqspellCommitChanges()" />'
                 . ' <input type="button" value="  '
                 . _("Close and Cancel")
                 . '  " onclick="if (confirm(\''
                 . _("The spellcheck is not finished. Really close and discard changes?")
                 . '\')) self.close()" />';
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
  $msg='<form onsubmit="return false"><div style="text-align: center;">' .
       '<input type="submit" value="  ' . _("Close") .
       '  " onclick="self.close()" /></div></form>';
  sqspell_makeWindow(null, _("No errors found"), null, $msg);
}

/**
 * For Emacs weenies:
 * Local variables:
 * mode: php
 * End:
 * vim: syntax=php et ts=4
 */
