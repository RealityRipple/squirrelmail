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

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');
   require_once('../functions/imap.php');
   require_once('../functions/array.php');
   require_once('../functions/plugin.php');
   
   displayPageHeader($color, 'None');

   $fullname = getPref($data_dir, $username, 'full_name');
   $replyto = getPref($data_dir, $username, 'reply_to');
   $email_address  = getPref($data_dir, $username, 'email_address'); 

?>
   <br>
<table width=95% align=center border=0 cellpadding=2 cellspacing=0>
<tr><td align="center" bgcolor="<?php echo $color[0] ?>">

      <b><?php echo _("Options") . " - " . _("Personal Information"); ?></b>

    <table width="100%" border="0" cellpadding="1" cellspacing="1">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

   <form name=f action="options.php" method=post><br>
      <table width=100% cellpadding=2 cellspacing=0 border=0>
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
            <td align="right" nowrap><?PHP echo _("Reply Citation Style") ?>:</td>
            <td><select name="new_reply_citation_style">
                <option value="none"<?PHP
                    if ($reply_citation_style == 'none') echo ' SELECTED';
                    ?>>- <?PHP echo _("No Citation"); ?> -</option>
                <option value="author_said"<?PHP
                    if ($reply_citation_style == 'author_said') echo ' SELECTED';
                    ?>><?PHP echo _("AUTHOR Said"); ?></option>
                <option value="quote_who"<?PHP
                    if ($reply_citation_style == 'quote_who') echo ' SELECTED';
                    ?>><?PHP echo _("Quote Who XML"); ?></option>
                <option value="user-defined"<?PHP
                    if ($reply_citation_style == 'user-defined') echo ' SELECTED';
                    ?>><?PHP echo _("User-Defined"); ?></option>
                </select>
            </td>
         </tr>
         <tr>
            <td align="right" nowrap><?php echo _("User-Defined Reply Citation"); ?>:</td>
            <td>
               <tt><input type="text" size="20" name="new_reply_citation_start" value="<?php
                  echo $reply_citation_start;
               ?>"></tt> &lt;<?php
                  echo _("Author's Name");
               ?>&gt;
               <tt><input type="text" size="20" name="new_reply_citation_end" value="<?php
                  echo $reply_citation_end;
               ?>"></tt>
            </td>
         </tr>
	 <tr>
	    <td align=right nowrap><?PHP echo _("Multiple Identities"); ?>:
	    </td><td>
	       <a href="options_identities.php"><?PHP 
   echo _("Edit Advanced Identities") . '</a> ' . _("(discards changes made on this form so far)");
	    ?></td>
	 </tr>
         <tr><td colspan=2><hr size=1 width=80%></td></tr>
         <tr>
            <td align=right nowrap valign=top><br><?php echo _("Signature"); ?>:
            </td><td>
<?php
   echo '<input type=checkbox value="1" name=usesignature';
   if ($use_signature)
      echo ' checked';
   echo '>&nbsp;&nbsp;' . _("Use a signature?") . '&nbsp;&nbsp;';
   echo '<input type="checkbox" value="1" name="prefixsig"';
   if ( $prefix_sig )
     echo ' checked';
   echo '>&nbsp;&nbsp;' .
        _( "Prefix signature with '-- ' ?" ) . '<BR>';
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

    </td></tr>
    </table>

</td></tr>
</table>
</body></html>