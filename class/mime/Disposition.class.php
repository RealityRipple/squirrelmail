<?php

/**
 * Disposition.class.php
 *
 * Copyright (c) 2003-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains functions needed to handle content disposition headers 
 * in mime messages.
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.2
 * @todo find rfc number
 */

/**
 * Class that handles content disposition header
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.0
 * @todo FIXME: do we have to declare vars ($name and $properties)?
 */
class Disposition {
    /**
     * Constructor function
     * @param string $name
     */
    function Disposition($name) {
       $this->name = $name;
       $this->properties = array();
    }

    /**
     * Returns value of content disposition property
     * @param string $par content disposition property name
     * @return string
     * @since 1.3.1
     */
    function getProperty($par) {
        $value = strtolower($par);
        if (isset($this->properties[$par])) {
            return $this->properties[$par];
        }
        return '';
    }
}

?>