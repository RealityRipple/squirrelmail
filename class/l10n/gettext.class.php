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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage i18n
 */

/**
 * Class that uses parsed translation input objects
 * @package squirrelmail
 * @subpackage i18n
 */
class gettext_reader {
    /**
     * holds error code (0 if no error)
     * @var integer
     * @access public
     */
    var $error = 0;
    /**
     * specifies the byte order: 0 low endian, 1 big endian
     * @var integer
     * @access private
     */
    var $BYTEORDER = 0;
    /**
     * input object data
     * @var object
     * @access private
     */
    var $STREAM = NULL;

    /**
     *
     */
    function readint() {
        // Reads 4 byte value from $FD and puts it in int
        // $BYTEORDER specifies the byte order: 0 low endian, 1 big endian
        for ($i=0; $i<4; $i++) {
            $byte[$i]=ord($this->STREAM->read(1));
        }
        //print sprintf("pos: %d\n",$this->STREAM->currentpos());
        if ($this->BYTEORDER == 0)
            return (int)(($byte[0]) | ($byte[1]<<8) | ($byte[2]<<16) | ($byte[3]<<24));
        else
            return (int)(($byte[3]) | ($byte[2]<<8) | ($byte[1]<<16) | ($byte[0]<<24));
    }

    /**
     * constructor that requires StreamReader object
     * @param object $Reader
     * @return boolean false, if some error with stream
     */
    function gettext_reader($Reader) {
        $MAGIC1 = (int) ((222) | (18<<8) | (4<<16) | (149<<24));
        $MAGIC2 = (int) ((149) | (4<<8) | (18<<16) | (222<<24));

        $this->STREAM = $Reader;
        if ($this->STREAM->error>0) {
            $this->error=1;
            return false;
        }
        $magic = $this->readint();
        if ($magic == $MAGIC1) {
            $this->BYTEORDER = 0;
        } elseif ($magic == $MAGIC2) {
            $this->BYTEORDER = 1;
        } else {
            $this->error = 1; // not MO file
            return false;
        }

        // FIXME: Do we care about revision? We should.
        $revision = $this->readint();

        $total = $this->readint();
        $originals = $this->readint();
        $translations = $this->readint();

        $this->total = $total;
        $this->originals = $originals;
        $this->translations = $translations;

        // Here we store already found translations
        $this->_HASHED = array();
    }

    /**
     * @param boolean $translations do translation have to be loaded
     */
    function load_tables($translations=false) {
        // if tables are loaded do not load them again
        if (!isset($this->ORIGINALS)) {
            $this->ORIGINALS = array();
            $this->STREAM->seekto($this->originals);
            for ($i=0; $i<$this->total; $i++) {
                $len = $this->readint();
                $ofs = $this->readint();
                $this->ORIGINALS[] = array($len,$ofs);
            }
        }

        // similar for translations
        if ($translations and !isset($this->TRANSLATIONS)) {
            $this->TRANSLATIONS = array();
            $this->STREAM->seekto($this->translations);
            for ($i=0; $i<$this->total; $i++) {
                $len = $this->readint();
                $ofs = $this->readint();
                $this->TRANSLATIONS[] = array($len,$ofs);
            }
        }
    }

    /**
     * get a string with particular number
     * @param integer $num
     * @return string untranslated string
     */
    function get_string_number($num) {
        // TODO: Add simple hashing [check array, add if not already there]
        $this->load_tables();
        $meta = $this->ORIGINALS[$num];
        $length = $meta[0];
        $offset = $meta[1];
        $this->STREAM->seekto($offset);
        $data = $this->STREAM->read($length);
        return (string)$data;
    }

    /**
     * get translated string with particular number
     * @param integer $num
     * @return string translated string
     */
    function get_translation_number($num) {
        // get a string with particular number
        // TODO: Add simple hashing [check array, add if not already there]
        $this->load_tables(true);
        $meta = $this->TRANSLATIONS[$num];
        $length = $meta[0];
        $offset = $meta[1];
        $this->STREAM->seekto($offset);
        $data = $this->STREAM->read($length);
        return (string)$data;
    }

    /**
     * binary search for string
     * @param string $string
     * @param integer $start
     * @param integer $end
     */
    function find_string($string, $start,$end) {
        //print "start: $start, end: $end\n";
        // Simple hashing to improve speed
        if (isset($this->_HASHED[$string])) return $this->_HASHED[$string];

        if (abs($start-$end)<=1) {
            // we're done, if it's not it, bye bye
            $txt = $this->get_string_number($start);
            if ($string == $txt) {
                $this->_HASHED[$string] = $start;
                return $start;
            } else
                return -1;
        } elseif ($start>$end) {
            return $this->find_string($string,$end,$start);
        }  else {
            $half = (int)(($start+$end)/2);
            $tst = $this->get_string_number($half);
            $cmp = strcmp($string,$tst);
            if ($cmp == 0) {
                $this->_HASHED[$string] = $half;
                return $half;
            } elseif ($cmp<0)
                return $this->find_string($string,$start,$half);
            else
                return $this->find_string($string,$half,$end);
        }
    }

    /**
     * translate string
     * @param string $string English string
     * @return string translated string
     */
    function translate($string) {
        if ($this->error > 0) return $string;
        $num = $this->find_string($string, 0, $this->total);
        if ($num == -1)
            return $string;
        else
            return $this->get_translation_number($num);
    }

    /**
     * extract plural forms header
     * @return string plural-forms header string
     */
    function get_plural_forms() {
        // lets assume message number 0 is header
        // this is true, right?

        // cache header field for plural forms
        if (isset($this->pluralheader) && is_string($this->pluralheader))
            return $this->pluralheader;
        else {
            $header = $this->get_translation_number(0);

            if (preg_match('/plural-forms: (.*)\n/i',$header,$regs)) {
                $expr = $regs[1];
            } else {
                $expr = "nplurals=2; plural=n == 1 ? 0 : 1;";
            }
            $this->pluralheader = $expr;
            return $expr;
        }
    }

    /**
     * find out the appropriate form number
     * @param integer $n count
     * @return integer
     */
    function select_string($n) {
        $string = $this->get_plural_forms();
        $string = str_replace('nplurals',"\$total",$string);
        $string = str_replace("n",$n,$string);
        $string = str_replace('plural',"\$plural",$string);

        $total = 0;
        $plural = 0;

        eval("$string");
        if ($plural>=$total) $plural = 0;
        return $plural;
    }

    /**
     * translate string with singular/plural forms
     * @param string $single English singural form of translation
     * @param string $plural English plural form of translation
     * @param string $number count
     * @return string
     */
    function ngettext($single, $plural, $number) {
        if ($this->error > 0) {
            $result=-1;
        } else {
            // find out the appropriate form
            $select = $this->select_string($number);

            // this should contains all strings separated by NULLs
            $result = $this->find_string($single.chr(0).$plural,0,$this->total);
        }
        if ($result == -1) {
            if ($number != 1) return $plural;
            else return $single;
        } else {
            $result = $this->get_translation_number($result);

            // lets try to parse all the NUL staff
            //$result = "proba0".chr(0)."proba1".chr(0)."proba2";
            $list = explode (chr(0), $result);
            return $list[$select];
        }
    }
}
