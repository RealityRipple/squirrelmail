<?php
   /**
    **  addrbook_popup.php
    **
    **  Copyright (c) 1999-2000 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Frameset for the JavaScript version of the address book.
    **
    **  $Id$
    **/

   include('../src/validate.php');
   include("../functions/strings.php");
   include('../functions/i18n.php');
   include('../config/config.php');
   include('../functions/page_header.php');
   include('../functions/addressbook.php');
   include('../src/load_prefs.php');
   
   set_up_language(getPref($data_dir, $username, 'language'));
   
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
