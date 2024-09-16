<?php

/**
 * Disposition.class.php
 *
 * This file contains functions needed to handle content disposition headers 
 * in mime messages. See RFC 2183.
 *
 * @copyright 2003-2024 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.2
 */

/**
 * Class that handles content disposition header
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.0
 */
class Disposition {
    var $name;
    var $properties;

    /**
     * Constructor (PHP5 style, required in some future version of PHP)
     * @param string $name
     */
    function __construct($name) {
       $this->name = $name;
       $this->properties = array();
    }

    /**
     * Constructor (PHP4 style, kept for compatibility reasons)
     * @param string $name
     */
    function Disposition($name) {
       self::__construct($name);
    }

    /**
     * Returns value of content disposition property
     * @param string $par content disposition property name
     * @return string
     * @since 1.3.1
     */
    function getProperty($par) {
        $par = strtolower($par);
        if (isset($this->properties[$par])) {
            return $this->properties[$par];
        }
        return '';
    }
}
