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

   require_once('../src/validate.php');
   require_once('../functions/prefs.php');
   require_once('../functions/plugin.php');

   // Erase any lingering attachments
   if (! isset($attachments)) {
       $attachments = array();
   }
   foreach ($attachments as $info) {
       if (file_exists($attachment_dir . $info['localfilename'])) {
           unlink($attachment_dir . $info['localfilename']);
       }
   }

   // If a user hits reload on the last page, $base_uri isn't set
   // because it was deleted with the session.
   if (! isset($base_uri)) {
       ereg ("(^.*/)[^/]+/[^/]+$", $PHP_SELF, $regs);
       $base_uri = $regs[1];
   }

   do_hook('logout');
   setcookie('username', '', 0, $base_uri);
   setcookie('key', '', 0, $base_uri);
   session_destroy();

   if ($signout_page) {
       header("Status: 303 See Other");
       header("Location: $signout_page");
       exit; /* we send no content if we're redirecting. */
   }
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
LINK="<?php echo $color[7] ?>" VLINK="<?php echo $color[7] ?>"
ALINK="<?php echo $color[7] ?>">
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
      <?php do_hook('logout_above_text'); ?>
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
