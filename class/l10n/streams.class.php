<?php

/**
 * Copyright (c) 2003 Danilo Segan <danilo@kvota.net>.
 *
 * This file is part of PHP-gettext.
 *
 * PHP-gettext is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * PHP-gettext is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PHP-gettext; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA  02110-1301, USA
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage i18n
 */

/**
 * Class that is used to read .mo files.
 * @package squirrelmail
 * @subpackage i18n
 */
class FileReader {
    /**
     * Current position in file
     * @var integer
     */
    var $_pos;
    /**
     * File descriptor
     * @var resource
     */
    var $_fd;
    /**
     * File size
     * @var integer
     */
    var $_length;
    /**
     * contains error codes
     *
     * 2 = File doesn't exist
     * 3 = Can't read file
     * @var integer
     */
    var $error=0;

    /**
     * reads translation file and fills translation input object properties
     * @param string $filename path to file
     * @return boolean false there is a problem with $filename
     */
    function FileReader($filename) {
        // disable stat warnings for unreadable directories
        if (@file_exists($filename)) {

            $this->_length=filesize($filename);
            $this->_pos = 0;
            $this->_fd = fopen($filename,'rb');
            if (!$this->_fd) {
                $this->error = 3; // Cannot read file, probably permissions
                return false;
            }
        } else {
            $this->error = 2; // File doesn't exist
            return false;
        }
    }

    /**
     * reads data from current position
     * @param integer $bytes number of bytes to read
     * @return string read data
     */
    function read($bytes) {
        fseek($this->_fd, $this->_pos);
        $data = fread($this->_fd, $bytes);
        $this->_pos = ftell($this->_fd);

        return $data;
    }

    /**
     * Moves to defined position in a file
     * @param integer $pos position
     * @return integer current position
     */
    function seekto($pos) {
        fseek($this->_fd, $pos);
        $this->_pos = ftell($this->_fd);
        return $this->_pos;
    }

    /**
     * return current position
     * @return integer current position
     */
    function currentpos() {
        return $this->_pos;
    }

    /**
     * return file length
     * @return integer file length
     */
    function length() {
        return $this->_length;
    }

    /**
     * close translation file
     */
    function close() {
        fclose($this->_fd);
    }
}
