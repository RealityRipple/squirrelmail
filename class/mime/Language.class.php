<?php

/**
 * Language.class.php
 *
 * Copyright (c) 2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions needed to handle mime messages.
 *
 * $Id$
 */

class Language {
    function Language($name) {
       $this->name = $name;
       $this->properties = array();
    }
}

?>
