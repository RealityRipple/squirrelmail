<?php

require_once('Deliver.class.php');

class Deliver_IMAP extends Deliver {

    function getBcc() {
       return true;
    }
    
    /* to do: finishing the imap-class so the initStream function can call the 
       imap-class */
}


?>
