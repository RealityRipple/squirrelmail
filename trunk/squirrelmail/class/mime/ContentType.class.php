<?php

/**
 * ContentType.class.php
 *
 * Copyright (c) 2003-2005 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This file contains functions needed to handle content type headers 
 * (rfc2045) in mime messages.
 *
 * @version $Id$
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.2
 */

/**
 * Class that handles content-type headers
 * Class was named content_type in 1.3.0 and 1.3.1. It is used internally
 * by rfc822header class.
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.2
 */
class ContentType {
    /**
     * Media type
     * @var string
     */
    var $type0 = 'text';
    /**
     * Media subtype
     * @var string
     */
    var $type1 = 'plain';
    /**
     * Auxiliary header information
     * prepared with parseContentType() function in rfc822header class.
     * @var array
     */
    var $properties = '';

    /**
     * Constructor function.
     * Prepared type0 and type1 properties
     * @param string $type content type string without auxiliary information
     */
    function ContentType($type) {
        $pos = strpos($type, '/');
        if ($pos > 0) {
            $this->type0 = substr($type, 0, $pos);
            $this->type1 = substr($type, $pos+1);
        } else {
            $this->type0 = $type;
        }
        $this->properties = array();
    }
}

?>