<?php
   /**
    **  signout.php -- cleans up session and logs the user out
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Cleans up after the user. Resets cookies and terminates
    **  session.
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include('../functions/strings.php');
   include ('../src/load_prefs.php');
   include('../config/config.php');
   include('../functions/i18n.php');
   include ('../functions/prefs.php');
   include ('../functions/plugin.php');


   // Erase any lingering attachments
   foreach ($attachments as $info)
   {
       if (file_exists($attachment_dir . $info['localfilename']))
       {
           unlink($attachment_dir . $info['localfilename']);
       }
   }

   set_up_language(getPref($data_dir, $username, 'language'));

   // If a user hits reload on the last page, $base_uri isn't set
   // because it was deleted with the session.
   if (! isset($base_uri))
   {
       ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
       $base_uri = $regs[1];
   }

   do_hook('logout');
   setcookie('username', '', 0, $base_uri);
   setcookie('key', '', 0, $base_uri);
   setcookie('logged_in', '', 0, $base_uri);
   session_destroy();
?>
<HTML>
   <HEAD>
<?php
   if ($theme_css != '') {
?>
<LINK REL="stylesheet" TYPE="text/css" HREF="<?php echo $theme_css ?>">
<?php
   }
?>
<TITLE><?php echo $org_title ?> - Signout</TITLE>
</HEAD>
<BODY TEXT="<?php echo $color[8] ?>" BGCOLOR="<?php echo $color[4] ?>" 
LINK="<?php echo $color[7] ?>" VLINK="<?php echo $color[7] ?>" A
LINK="<?php echo $color[7] ?>">
<BR><BR>
<TABLE BGCOLOR="FFFFFF" BORDER="0" COLS="1" WIDTH="50%" CELLSPACING="0" 
CELLPADDING="2" ALIGN="CENTER">
  <TR BGCOLOR="<?php echo $color[0] ?>" WIDTH=100%>
    <TD ALIGN="CENTER">
      <B><?php echo _("Sign Out") ?></B>
    </TD>
  </TR>
  <TR BGCOLOR="<?php echo $color[4] ?>" WIDTH=100%>
    <TD ALIGN="CENTER">
      <?php echo _("You have been successfully signed out.") ?><BR>
      <A HREF="login.php" TARGET="_top">
      <?php echo _("Click here to log back in.") ?>
      </A><BR><BR>
    </TD>
  </TR>
  <TR BGCOLOR="<?php echo $color[0] ?>" WIDTH=100%>
    <TD ALIGN="CENTER">
      <BR>
    </TD>
  </TR>
</TABLE>
</BODY>
</HTML>
