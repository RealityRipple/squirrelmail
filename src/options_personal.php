<?php
   /**
    **  options_personal.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Displays all options relating to personal information
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include('../functions/page_header.php');
   include('../functions/display_messages.php');
   include('../functions/imap.php');
   include('../functions/array.php');
   include('../functions/plugin.php');
   include('../src/load_prefs.php');
   
   displayPageHeader($color, 'None');

   $fullname = getPref($data_dir, $username, 'full_name');
   $replyto = getPref($data_dir, $username, 'reply_to');
   $email_address  = getPref($data_dir, $username, 'email_address'); 

?>
   <br>
   <table width=95% align=center border=0 cellpadding=2 cellspacing=0><tr><td bgcolor="<?php echo $color[0] ?>">
      <center><b><?php echo _("Options") . " - " . _("Personal Information"); ?></b></center>
   </td></tr></table>

   <form name=f action="options.php" method=post>
      <table width=100% cellpadding=0 cellspacing=2 border=0>
         <tr>
            <td align=right nowrap><?php echo _("Full Name"); ?>:
            </td><td>
               <input size=50 type=text value="<?php echo $fullname ?>" name=full_name> 
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("E-Mail Address"); ?>:
            </td><td>
               <input size=50 type=text value="<?php echo $email_address ?>" name=email_address> 
            </td>
         </tr>
         <tr>
            <td align=right nowrap><?php echo _("Reply To"); ?>:
            </td><td>
               <input size=50 type=text value="<?php echo $replyto ?>" name=reply_to> 
            </td>
         </tr>
         <tr>
            <td align=right nowrap valign=top><br><?php echo _("Signature"); ?>:
            </td><td>
<?php
   if ($use_signature == true)
      echo '<input type=checkbox value="1" name=usesignature checked>&nbsp;&nbsp;' . _("Use a signature?") . '&nbsp;&nbsp;';
   else
      echo '<input type=checkbox value="1" name=usesignature>&nbsp;&nbsp;' . _("Use a signature?") . '&nbsp;&nbsp;';
  if ( ! isset($prefix_sig) || $prefix_sig == true )
    echo '<input type="checkbox" value="1" name="prefixsig" checked>&nbsp;&nbsp;' 
        . _( "Prefix signature with '--' ?" ) . '<BR>';
  else
    echo '<input type="checkbox" value="1" name="prefixsig">&nbsp;&nbsp;' . 
        _( "Prefix signature with '--' ?" ) . '<BR>';
   echo "\n<textarea name=\"signature_edit\" rows=\"5\" cols=\"50\">$signature_abs</textarea><br>";
?>
            </td>
         </tr>
         <?php do_hook("options_personal_inside"); ?>
         <tr>
            <td>&nbsp;
            </td><td>
               <input type="submit" value="<?php echo _("Submit"); ?>" name="submit_personal">
            </td>
         </tr>
      </table>   
   </form>
   <?php do_hook('options_personal_bottom'); ?>
</body></html>
