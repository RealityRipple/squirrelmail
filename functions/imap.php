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
   
   include ('../functions/' . $imap_backend . '_mailbox.php');
   include ('../functions/' . $imap_backend . '_messages.php');
   include ('../functions/' . $imap_backend . '_general.php');
   include ('../functions/' . $imap_backend . '_search.php');
?>
