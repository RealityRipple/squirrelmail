<?
   /**
    **  webmail.php
    **
    **  This simply creates the frames.
    **
    **/

   if(!isset($username)) {
      echo "You need a valid user and password to access this page!";
      exit;
   }

   setcookie("username", $username, 0, "/");
   setcookie("key", $key, 0, "/");
   setcookie("logged_in", 1, 0, "/");
?>
<HTML><HEAD>
<TITLE>
OM-USA WebMail
</TITLE>
<FRAMESET COLS="200, *" NORESIZE BORDER=0>
   <FRAME SRC="left_main.php" NAME="left">
   <FRAME SRC="right_main.php" NAME="right">
</FRAMESET>
</HEAD></HTML>
