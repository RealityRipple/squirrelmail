<?php
   /**
    **  options.php
    **
    **  Pick your translator to translate the body of incoming mail messages
    **
    **/

   chdir("..");

   session_start();

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($strings_php))
      include("../functions/strings.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($display_messages_php))
      include("../functions/display_messages.php");
   if (!isset($imap_php))
      include("../functions/imap.php");
   if (!isset($array_php))
      include("../functions/array.php");
   if (!isset($i18n_php))
      include("../functions/i18n.php");


   include("../src/load_prefs.php");
   displayPageHeader($color, "None");

  $translate_server = getPref($data_dir, $username, "translate_server");
  if ($translate_server == '') 
    $translate_server = 'babelfish';
  $translate_location = getPref($data_dir, $username, "translate_location");
  if ($translate_location == '')
    $translate_location = 'center';
  $translate_show_read = getPref($data_dir, $username, 'translate_show_read');
  $translate_show_send = getPref($data_dir, $username, 'translate_show_send');
  $translate_same_window = getPref($data_dir, $username, 'translate_same_window');

   function ShowOption($Var, $value, $Desc)
   {
       $Var = 'translate_' . $Var;
       
       global $$Var;
       
       echo '<option value="' . $value . '"';
       if ($$Var == $value)
       {
           echo ' SELECTED';
       }
       echo '>' . $Desc . "</option>\n";
   }
       

?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") ?> - Translator</b></center>
   </td></tr></table>

   <p>Your server options are as follows:</p>
   
   <ul>
   
   <li><b>Babelfish</b> -
       13 language pairs,
       maximum of 1000 characters translated,
       powered by Systran
       [ <a href="http://babelfish.altavista.com/" 
       target="_blank">Babelfish</a> ]</li>

   <li><b>Go.com</b> -
       10 language pairs,
       maximum of 25 kilobytes translated,
       powered by Systran
       [ <a href="http://translator.go.com/"
       target="_blank">Translator.Go.com</a> ]</li>

   <li><b>Dictionary.com</b> -
       12 language pairs,
       no known limits,
       powered by Systran
       [ <a href="http://www.dictionary.com/translate"
       target="_blank">Dictionary.com</a> ]</li>
       
   <li><b>InterTran</b> -
       767 language pairs,
       no known limits,
       powered by Translation Experts's InterTran
       [ <a href="http://www.tranexp.com/"
       target="_blank">Translation Experts</a> ]</li>
       
   <li><b>GPLTrans</b> -
       8 language pairs,
       no known limits,
       powered by GPLTrans (free, open source)
       [ <a href="http://www.translator.cx/"
       target="_blank">GPLTrans</a> ]</li>

   </ul>
   
   <p>You also decide if you want the translation box displayed, 
   and where it will be located.</p>

   <form action="../../src/options.php" method=post>
   <table border=0 cellpadding=0 cellspacing=2>
   <tr><td align=right nowrap>Select your translator:</td>
       <td><select name="translate_translate_server">
<?PHP
    ShowOption('server', 'babelfish', 'Babelfish');
    ShowOption('server', 'go', 'Go.com');
    ShowOption('server', 'dictionary', 'Dictionary.com');
    ShowOption('server', 'intertran', 'Intertran');
    ShowOption('server', 'gpltrans', 'GPLTrans');
?>       </select>
       </td></tr>
   <tr><td align=right nowrap valign="top">When reading:</td>
   <td><input type=checkbox name="translate_translate_show_read"<?PHP
   if ($translate_show_read) 
     echo " CHECKED";
   ?>> - Show translation box
   <select name="translate_translate_location">
<?PHP
    ShowOption('location', 'left', 'to the left');
    ShowOption('location', 'center', 'in the center');
    ShowOption('location', 'right', 'to the right');
?>    </select><br>
   <input type=checkbox name="translate_translate_same_window"<?PHP
   if ($translate_same_window)
     echo " CHECKED";
   ?>> - Translate inside the SquirrelMail frames</td></tr>
   <tr><td align=right nowrap>When composing:</td>
   <td><input type=checkbox name="translate_translate_show_send"<?PHP
   if ($translate_show_send)
     echo " CHECKED";
   ?>> - Not yet functional, currently does nothing</td></tr>
   <tr><td></td><td>
       <input type="submit" value="Submit" name="submit_translate">
       </td></tr>
   </table>
   </form>
</body></html>
