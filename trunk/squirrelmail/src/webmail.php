<?
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

#   setcookie("username", $username, 0, "/");
#   setcookie("key", $key, 0, "/");
#   setcookie("logged_in", 1, 0, "/");
   
   session_register("username");
   session_register("key");
   session_register("logged_in");
   $logged_in = 0;

   $PHPSESSID = session_id();
   
   // Refresh the language cookie.
   if (isset($squirrelmail_language)) {
      session_register("squirrelmail_language");
#      setcookie("squirrelmail_language", $squirrelmail_language, time()+2592000);
   }
?>
<HTML><HEAD>
<?
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
    There are three ways to call webmail.php
    1.  webmail.php
         - this just loads the default entry screen.
    2.  webmail.php?right_frame=right_main.php&sort=X&startMessage=X&mailbox=XXXX
         - This loads the frames starting at the given values.
    3.  webmail.php?right_frame=folders.php
         - Loads the frames with the Folder options in the right frame.

    This was done to create a pure HTML way of refreshing the folder list since
    we would like to use as little Javascript as possible.
**/
   if ($right_frame == "right_main.php") {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php?PHPSESSID=$PHPSESSID\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php?PHPSESSID=$PHPSESSID&mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage\" NAME=\"right\">";
   } else if ($right_frame == "folders.php") {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php?PHPSESSID=$PHPSESSID\" NAME=\"left\">";
      echo "<FRAME SRC=\"folders.php?PHPSESSID=$PHPSESSID\" NAME=\"right\">";
   } else {
      echo "<FRAME SRC=\"left_main.php?PHPSESSID=$PHPSESSID\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php?PHPSESSID=$PHPSESSID\" NAME=\"right\">";
   }
?>
</FRAMESET>
</HEAD></HTML>
