<?php

/**
 * imap.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This just includes the different sections of the imap functions.
 * They have been organized into these sections for simplicity sake.
 *
 * $Id$
 */

$imap_backend = 'imap';

require_once('../functions/' . $imap_backend . '_mailbox.php');
require_once('../functions/' . $imap_backend . '_messages.php');
require_once('../functions/' . $imap_backend . '_general.php');
require_once('../functions/' . $imap_backend . '_search.php');

?>
