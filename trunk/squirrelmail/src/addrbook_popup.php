<?php
   /**
    **  addrbook_popup.php
    **
    **  Frameset for the JavaScript version of the address book.
    **
    **/

   session_start();

   if(!isset($logged_in)) {
      echo _("You must login first.");
      exit;
   }
   if(!isset($username) || !isset($key)) {
      echo _("You need a valid user and password to access this page!");
      exit;
   }

   if (!isset($config_php))
      include("../config/config.php");
   if (!isset($page_header_php))
      include("../functions/page_header.php");
   if (!isset($addressbook_php))
      include("../functions/addressbook.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Frameset//EN">

<HTML>
<HEAD>
<TITLE><?php 
   printf("%s: %s", $org_title, _("Address Book")); 
?></TITLE>
</HEAD>

<FRAMESET ROWS="60,*" BORDER=0>
 <FRAME NAME="abookmain" MARGINWIDTH=0 SCROLLING=NO
        SRC="addrbook_search.php?show=form" BORDER=0>
 <FRAME NAME="abookres" MARGINWIDTH=0 SRC="addrbook_search.php?show=blank"
        BORDER=0>
</FRAMESET>

</HTML>
