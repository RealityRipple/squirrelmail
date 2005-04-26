<?php

/**
 * mime.class
 *
 * Copyright (c) 2003-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions needed to handle mime messages.
 *
 * @version $Id$
 * @package squirrelmail
 */

/** @ignore */
if (! defined('SM_PATH')) define('SM_PATH','../');

/** Load in the entire MIME system */
require_once(SM_PATH . 'class/mime/Rfc822Header.class.php');
require_once(SM_PATH . 'class/mime/MessageHeader.class.php');
require_once(SM_PATH . 'class/mime/AddressStructure.class.php');
require_once(SM_PATH . 'class/mime/Message.class.php');
require_once(SM_PATH . 'class/mime/SMimeMessage.class.php');
require_once(SM_PATH . 'class/mime/Disposition.class.php');
require_once(SM_PATH . 'class/mime/Language.class.php');
require_once(SM_PATH . 'class/mime/ContentType.class.php');

?>