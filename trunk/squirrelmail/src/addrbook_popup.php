<?php

   /**
    **  addrbook_popup.php
    **
    **  Copyright (c) 1999-2001 The SquirrelMail development team
    **  Licensed under the GNU GPL. For full terms see the file COPYING.
    **
    **  Frameset for the JavaScript version of the address book.
    **
    **  $Id$
    **/

   require_once('../src/validate.php');
   require_once('../functions/addressbook.php');
   
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Frameset//EN">

<HTML>
<HEAD>
<TITLE><?php 
   echo "$org_title: " . _("Address Book"); 
?></TITLE>
</HEAD>

<FRAMESET ROWS="60,*" BORDER=0>
 <FRAME NAME="abookmain" MARGINWIDTH=0 SCROLLING=NO
        SRC="addrbook_search.php?show=form" BORDER=0>
 <FRAME NAME="abookres" MARGINWIDTH=0 SRC="addrbook_search.php?show=blank"
        BORDER=0>
</FRAMESET>

</HTML>