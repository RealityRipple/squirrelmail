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
<?
   include ("../config/config.php");
   include ("../functions/prefs.php");
   echo "$org_title";
?>
</TITLE>
<FRAMESET COLS="200, *" NORESIZE BORDER=0>

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
<?
   checkForPrefs($username);

   if ($right_frame == "right_main.php") {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php?mailbox=$urlMailbox&sort=$sort&startMessage=$startMessage\" NAME=\"right\">";
   } else if ($right_frame == "folders.php") {
      $urlMailbox = urlencode($mailbox);
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"folders.php\" NAME=\"right\">";
   } else {
      echo "<FRAME SRC=\"left_main.php\" NAME=\"left\">";
      echo "<FRAME SRC=\"right_main.php\" NAME=\"right\">";
   }
?>
</FRAMESET>
</HEAD></HTML>
