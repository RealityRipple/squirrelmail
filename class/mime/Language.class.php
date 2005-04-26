<?php

/**
 * Language.class.php
 *
 * Copyright (c) 2003-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file should contain class needed to handle Language properties in 
 * mime messages. I suspect that it is RFC2231
 *
 * @version $Id$
 * @package squirrelmail
 * @since 1.3.2
 */

/**
 * Class that can be used to handle language properties in MIME headers.
 *
 * @package squirrelmail
 * @since 1.3.0
 */
class Language {
    /**
     * Class constructor
     * @param mixed $name
     */
    function Language($name) {
        /** @var mixed */
        $this->name = $name;
        /**
         * Language properties
         * @var array 
         */
        $this->properties = array();
    }
}

?>