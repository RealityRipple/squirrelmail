<?php

   /**
    **  options_identities.php
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Display Identities Options
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/display_messages.php');

   if (isset($return)) {
      SaveUpdateFunction();
      header('Location: options_personal.php');
      exit();
   }
   
   displayPageHeader($color, 'None');

   $Info = do_hook('options_identities_process', 0);
   if ($Info[1])
      SaveUpdateFunction();

   if (CheckAndDoDefault() || CheckAndDoPromote()) {
      SaveUpdateFunction();
   }
   if (isset($update) || CheckForDelete())
      SaveUpdateFunction();

   LoadInfo($full_name, $email_address, $reply_to, '');

?>
<br>
<table width=95% align=center border=0 cellpadding=2 cellspacing=0>
<tr><td bgcolor="<?php echo $color[0] ?>" align="center">

      <b><?php echo _("Options") . ' - ' . _("Advanced Identities"); ?></b>

    <table width="100%" border="0" cellpadding="1" cellspacing="1">
    <tr><td bgcolor="<?php echo $color[4] ?>" align="center">

<form name=f action="options_identities.php" method=post><br>

<?PHP do_hook('options_identities_top'); ?>

<table width=80% cellpadding=2 cellspacing=0 border=0>
  <tr bgcolor="<?PHP echo $color[9] ?>">
    <th colspan=2 align=center><?PHP echo _("Default Identity") ?></th>
  </tr>
<?PHP

   ShowTableInfo($full_name, $email_address, $reply_to, '');
  
   $num = 1;
   while (LoadInfo($full_name, $email_address, $reply_to, $num))
   {
?>
  <tr bgcolor="<?PHP echo $color[9] ?>">
    <th colspan=2 align=center><?PHP printf (_("Alternate Identity %d"),
    $num) ?></th>
  </tr>
<?PHP
       ShowTableInfo($full_name, $email_address, $reply_to, $num);
       $num ++;
   }
   
?>
  <tr bgcolor="<?PHP echo $color[9] ?>">
    <th colspan=2 align=center><?PHP echo _("Add a New Identity") ?></th>
  </tr>
<?

   ShowTableInfo('', '', '', $num);
?>
</table>   
</form>

    </td></tr>
    </table>

</td></tr>
</table>
</body></html>

<?PHP

function SaveUpdateFunction()
{
   global $username, $data_dir, $full_name, $email_address, $reply_to;
    
   $i = 1;
   $fakeI = 1;
   $name = 'form_for_' . $i;
   global $$name;
   while (isset($$name))
   {
      $name = 'delete_' . $i;
      global $$name;
      if (isset($$name)) {
         $fakeI --;
      } else {
         do_hook('options_identities_renumber', $i, $fakeI);
         $filled = 0;
	 
         $name = 'full_name' . $i;
         global $$name;
	 if ($$name != '') 
	    $filled ++;
         setPref($data_dir, $username, 'full_name' . $fakeI, $$name);
	 
         $name = 'email_address' . $i;
         global $$name;
	 if ($$name != '') 
	    $filled ++;
         setPref($data_dir, $username, 'email_address' . $fakeI, $$name);
	 
         $name = 'reply_to' . $i;
         global $$name;
	 if ($$name != '') 
	    $filled ++;
         setPref($data_dir, $username, 'reply_to' . $fakeI, $$name);
	 
	 if ($filled == 0) 
	    $fakeI --;
      }
      
      $fakeI ++;
      $i ++;
      $name = 'form_for_' . $i;
      global $$name;
   }
   
   setPref($data_dir, $username, 'identities', $fakeI);
   
   while ($fakeI != $i)
   {
      removePref($data_dir, $username, 'full_name' . $fakeI);
      removePref($data_dir, $username, 'email_address' . $fakeI);
      removePref($data_dir, $username, 'reply_to' . $fakeI);
      $fakeI ++;
   }

   setPref($data_dir, $username, 'full_name', $full_name);
   setPref($data_dir, $username, 'email_address', $email_address);
   setPref($data_dir, $username, 'reply_to', $reply_to);
}

function CheckAndDoDefault()
{
   global $username, $data_dir, $full_name, $email_address, $reply_to;
    
   $i = 1;
   $name = 'form_for_' . $i;
   global $$name;
   while (isset($$name))
   {
      $name = 'make_default_' . $i;
      global $$name;
      if (isset($$name)) {
          do_hook('options_identities_renumber', $i, 'default');
          global $full_name, $email_address, $reply_to;
	  
          $name = 'full_name' . $i;
          global $$name;
          $temp = $full_name;
          $full_name = $$name;
          $$name = $temp;
          
          $name = 'email_address' . $i;
          global $$name;
          $temp = $email_address;
          $email_address = $$name;
          $$name = $temp;
          
          $name = 'reply_to' . $i;
          global $$name;
          $temp = $reply_to;
          $reply_to = $$name;
          $$name = $temp;
          
          return true;
      }
      
      $i ++;
      $name = 'form_for_' . $i;
      global $$name;
   }
   return false;
}

function CheckForDelete()
{
   global $username, $data_dir, $full_name, $email_address, $reply_to;
    
   $i = 1;
   $name = 'form_for_' . $i;
   global $$name;
   while (isset($$name))
   {
      $name = 'delete_' . $i;
      global $$name;
      if (isset($$name)) {
          return true;
      }
      
      $i ++;
      $name = 'form_for_' . $i;
      global $$name;
   }
   return false;
}

function CheckAndDoPromote()
{
   global $username, $data_dir, $full_name, $email_address, $reply_to;
    
   $i = 1;
   $name = 'form_for_' . $i;
   global $$name;
   while (isset($$name))
   {
      $name = 'promote_' . $i;
      global $$name;
      if (isset($$name) && $i > 1) {
          do_hook('options_identities_renumber', $i, $i - 1);
	  
	  $nameA = 'full_name' . $i;
	  $nameB = 'full_name' . ($i - 1);
	  global $$nameA, $$nameB;
	  $temp = $$nameA;
	  $$nameA = $$nameB;
	  $$nameB = $temp;
	  
	  $nameA = 'email_address' . $i;
	  $nameB = 'email_address' . ($i - 1);
	  global $$nameA, $$nameB;
	  $temp = $$nameA;
	  $$nameA = $$nameB;
	  $$nameB = $temp;
	  
	  $nameA = 'reply_to' . $i;
	  $nameB = 'reply_to' . ($i - 1);
	  global $$nameA, $$nameB;
	  $temp = $$nameA;
	  $$nameA = $$nameB;
	  $$nameB = $temp;
	  
	  return true;
      }

      $i ++;
      $name = 'form_for_' . $i;
      global $$name;
   }
   return false;
}

function LoadInfo(&$n, &$e, &$r, $post)
{
   global $username, $data_dir;

   $n = getPref($data_dir, $username, 'full_name' . $post);
   $e = getPref($data_dir, $username, 'email_address' . $post);
   $r = getPref($data_dir, $username, 'reply_to' . $post);

   if ($n != '' || $e != '' || $r != '')
      return true;
}

function ShowTableInfo($full_name, $email_address, $reply_to, $post)
{
   global $color;
   
   $OtherBG = ' bgcolor="' . $color[0] . '"';
   if ($full_name == '' && $email_address == '' && $reply_to == '')
   	  $OtherBG = '';

   if ($full_name == '' && $email_address == '' && $reply_to == '')
      $isEmptySection = true;
   else
      $isEmptySection = false;

?>
  <tr<?PHP echo $OtherBG ?>>
    <td align=right nowrap>
      <?php echo _("Full Name"); ?>:
    </td>
    <td>
      <input size=50 type=text value="<?php echo htmlspecialchars($full_name)
      ?>" name="full_name<?PHP echo $post ?>"> 
    </td>
  </tr>
  <tr<?PHP echo $OtherBG ?>>
    <td align=right nowrap>
      <?php echo _("E-Mail Address"); ?>:
    </td>
    <td>
      <input size=50 type=text value="<?php echo htmlspecialchars($email_address)
      ?>" name="email_address<?PHP echo $post ?>"> 
    </td>
  </tr>
  <tr<?PHP echo $OtherBG ?>>
    <td align=right nowrap>
      <?php echo _("Reply To"); ?>:
    </td>
    <td>
      <input size=50 type=text value="<?php echo htmlspecialchars($reply_to)
      ?>" name="reply_to<?PHP echo $post ?>"> 
    </td>
  </tr>
<?PHP do_hook('options_identities_table', $OtherBG, $isEmptySection, $post); ?>
  <tr<?PHP echo $OtherBG ?>>
    <td>&nbsp;</td><td>
      <input type=hidden name="form_for_<?PHP echo $post ?>" value="1">
      <input type=submit name="update" value="<?PHP 
   echo _("Save / Update") . '">';
   if (! $isEmptySection && $post != '') {
?>
      <input type=submit name="make_default_<?PHP echo $post ?>" value="<?PHP
         echo _("Make Default") ?>">
      <input type=submit name="delete_<?PHP echo $post ?>" value="<?PHP echo _("Delete") ?>">
<?PHP
   }
   if (! $isEmptySection && $post != '' && $post > 1) {
?>
      <input type=submit name="promote_<?PHP echo $post ?>" value="<?PHP
         echo _("Move Up") ?>">
<?PHP
   }
   do_hook('options_identities_buttons', $isEmptySection, $post);
?>
    </td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
<?PHP
}
?>