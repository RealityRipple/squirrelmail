<?php
   /**
    **  options_personal.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all options relating to personal information
    **
    **/

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

   $fullname = getPref($data_dir, $username, "full_name");
   $replyto = getPref($data_dir, $username, "reply_to");
   $email_address  = getPref($data_dir, $username, "email_address"); 

?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<? echo $color[0] ?>">
      <center><b><? echo _("Options") . " - " . _("Personal Information"); ?></b></center>
   </td></tr></table>

   <form action="options.php" method=post>
      <table width=100% cellpadding=0 cellspacing=2 border=0>
         <tr>
            <td align=right nowrap><? echo _("Full Name"); ?>:
            </td><td>
               <input size=50 type=text value="<? echo $fullname ?>" name=full_name> 
            </td>
         </tr>
         <tr>
            <td align=right nowrap><? echo _("E-Mail Address"); ?>:
            </td><td>
               <input size=50 type=text value="<? echo $email_address ?>" name=email_address> 
            </td>
         </tr>
         <tr>
            <td align=right nowrap><? echo _("Reply To"); ?>:
            </td><td>
               <input size=50 type=text value="<? echo $replyto ?>" name=reply_to> 
            </td>
         </tr>
         <tr>
            <td align=right nowrap valign=top><br><? echo _("Signature"); ?>:
            </td><td>
<?
   if ($use_signature == true)
      echo "<input type=checkbox value=\"0\" name=usesignature checked>&nbsp;&nbsp;" . _("Use a signature") . "?<BR>";
   else {
      echo "<input type=checkbox value=\"1\" name=usesignature>&nbsp;&nbsp;";
      echo _("Use a signature?");
      echo "<BR>";
   } 
   echo "\n<textarea name=signature_edit rows=5 cols=50>$signature_abs</textarea><br>";
?>
            </td>
         </tr>
         <tr>
            <td>&nbsp;
            </td><td>
               <input type="submit" value="Submit" name="submit_personal">
            </td>
         </tr>
      </table>   
   </form>
</body></html>
