<?php
   /**  This just includes the different sections of the imap functions.
    **  They have been organized into these sections for simplicity sake.
    **
    **  $Id$
    **/

   if (defined ('imap_php'))
      return; 
   define ('imap_php', true); 

   $imap_backend = 'imap';
   
   require_once('../functions/' . $imap_backend . '_mailbox.php');
   require_once('../functions/' . $imap_backend . '_messages.php');
   require_once('../functions/' . $imap_backend . '_general.php');
   require_once('../functions/' . $imap_backend . '_search.php');
?>