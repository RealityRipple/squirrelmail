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

/**
    There are two ways to call webmail.php
    1.  webmail.php
         - this just loads the default entry screen.
    2.  webmail.php?sort=X&startMessage=X&mailbox=XXXX
         - This loads the frames starting at the given values.

    This was done to create a pure HTML way of refreshing the folder list since
    we would like to use as little Javascript as possible.
**/
<?
   if (strlen($mailbox) > 0) {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php?mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage\" NAME=\"right\">";
   } else {
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php\" NAME=\"right\">";
   }
?>
</FRAMESET>
</HEAD></HTML>
