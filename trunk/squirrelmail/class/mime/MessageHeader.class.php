<?php

/**
 * MessageHeader.class.php
 *
 * Copyright (c) 2003 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions needed to handle mime messages.
 *
 * $Id$
 */

class MessageHeader {
    /** msg_header contains all variables available in a bodystructure **/
    /** entity like described in rfc2060                               **/

    var $type0 = '',
        $type1 = '',
        $parameters = array(),
        $id = 0,
        $description = '',
        $encoding='',
        $size = 0,
        $md5='',
        $disposition = '',
        $language='';

    /*
     * returns addres_list of supplied argument
     * arguments: array('to', 'from', ...) or just a string like 'to'.
     * result: string: address1, addres2, ....
     */

    function setVar($var, $value) {
        $this->{$var} = $value;
    }

    function getParameter($p) {
        $value = strtolower($p);
        return (isset($this->parameters[$p]) ? $this->parameters[$p] : '');
    }

    function setParameter($parameter, $value) {
        $this->parameters[strtolower($parameter)] = $value;
    }
}

?>
