<?php
/**
 * Deliver_IMAP.class.php
 *
 * Copyright (c) 1999-2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Delivery backend for the Deliver class.
 *
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