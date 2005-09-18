<?php

/**
 * Deliver_IMAP.class.php
 *
 * Delivery backend for the Deliver class.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

/** This of course depends upon Deliver.. */

require_once(SM_PATH . 'class/deliver/Deliver.class.php');

/**
 * This class is incomplete and entirely undocumented.
 * @package squirrelmail
 */
class Deliver_IMAP extends Deliver {

    function getBcc() {
       return true;
    }

    /* to do: finishing the imap-class so the initStream function can call the
       imap-class */
}


?>