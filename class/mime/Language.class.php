<?php

/**
 * Language.class.php
 *
 * Copyright (c) 2003-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions needed to handle mime messages.
 *
 * @version $Id$
 * @package squirrelmail
 */

/**
 * Undocumented class
 * @package squirrelmail
 */
class Language {
    function Language($name) {
       $this->name = $name;
       $this->properties = array();
    }
}

?>