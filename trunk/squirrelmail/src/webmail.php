<?php
   /**
    **  webmail.php
    **
    **  This simply creates the frames.
    **
    **/

   session_start();

   if(!isset($username)) {
      echo _("You need a valid user and password to access this page!");
      exit;
   }

   setcookie("username", $username, 0, "/");
   setcookie("key", $key, 0, "/");
   setcookie("logged_in", 1, 0, "/");
   
   // Refresh the language cookie.
   if (isset($squirrelmail_language)) {
      setcookie("squirrelmail_language", $squirrelmail_language, time()+2592000);
   }
?>
<HTML><HEAD>
<?php
   include ("../config/config.php");
   include ("../functions/prefs.php");
   include ("../functions/imap.php");

   // verify that username and password are correct
   $imapConnection = sqimap_login($username, $key, $imapServerAddress, $imapPort, 0);
   
   include ("../src/load_prefs.php");
   
   echo "<TITLE>";
   echo "$org_title";
   echo "</TITLE>";
   echo "<FRAMESET COLS=\"$left_size, *\" NORESIZE BORDER=0>";

/**
    There are four ways to call webmail.php
    1.  webmail.php
         - This just loads the default entry screen.
    2.  webmail.php?right_frame=right_main.php&sort=X&startMessage=X&mailbox=XXXX
         - This loads the frames starting at the given values.
    3.  webmail.php?right_frame=folders.php
         - Loads the frames with the Folder options in the right frame.
    4.  webmail.php?right_frame=help.php
	 - Lets the left frame set up different menu for help and calls the right frame.

    This was done to create a pure HTML way of refreshing the folder list since
    we would like to use as little Javascript as possible.
**/
   if ($right_frame == "right_main.php") {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php?mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage\" NAME=\"right\">";
   } else if ($right_frame == "folders.php") {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"folders.php\" NAME=\"right\">";
   } else if ($right_frame == "help.php") {
      echo "<FRAME SRC=\"left_main.php?help.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"help.php\" NAME=\"right\">";
   } else {
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php\" NAME=\"right\">";
   }

?>
</FRAMESET>
</HEAD></HTML>
