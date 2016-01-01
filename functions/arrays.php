<?php

/**
 * arrays.php
 *
 * Contains utility functions for array operations
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */

 /**
  * Swaps two array values by position and maintain keys
  *
  * @param array $a (recursive) array
  * @param mixed $v1 value 1
  * @param mixed $v2 value 2
  * @return bool $r true on success
  * @author Marc Groot Koerkamp
  */
 function sqm_array_swap_values(&$a,$v1,$v2) {
     $r = false;
     if (in_array($v1,$a) && in_array($v2,$a)) {
        $k1 = array_search($v1,$a);
        $k2 = array_search($v1,$a);
        $d = $a[$k1];
        $a[$k1] = $a[$k2];
        $a[$k2] = $d;
        $r = true;
     }
     return $r;
 }


 /**
  * Move array value 2 array values by position and maintain keys
  *
  * @param array $a (recursive) array
  * @param mixed $v value to move
  * @param int $p positions to move
  * @return bool $success
  * @author Marc Groot Koerkamp
  */
function sqm_array_kmove_value(&$a,$v,$p) {
    $r = false;
    $a_v = array_values($a);
    $a_k = array_keys($a);
    if (in_array($v, $a_v)) {
        $k = array_search($v, $a_v);
        $p_n = $k + $p;
        if ($p_n > 0 && $p_n < count($a_v)) {
            $d = $a_v[$k];
            $a_v[$k] = $a_v[$p_n];
            $a_v[$p_n] = $d;
            $d = $a_k[$k];
            $a_k[$k] = $a_k[$p_n];
            $a_k[$p_n] = $d;
            $r = array_combine($a_k, $a_v);
            if ($a !== false) {
               $a = $r;
               $r = true;
            }
        }
    }
    return $r;
}

 /**
  * Move array value 2 array values by position. Does not maintain keys
  *
  * @param array $a (recursive) array
  * @param mixed $v value to move
  * @param int $p positions to move
  * @return bool $success
  * @author Marc Groot Koerkamp
  */
function sqm_array_move_value(&$a,$v,$p) {
    $r = false;
    $a_v = array_values($a);
    if (in_array($v, $a_v)) {
        $k = array_search($v, $a_v);
        $p_n = $k + $p;
        if ($p_n >= 0 && $p_n < count($a_v)) {
            $d = $a_v[$k];
            $a_v[$k] = $a_v[$p_n];
            $a_v[$p_n] = $d;
            $a = $a_v;
            $r = true;
        }
    }
    return $r;
}

 /**
  * Retrieve an array value n positions relative to a reference value.
  *
  * @param array $a array
  * @param mixed $v reference value
  * @param int $p offset to reference value in positions
  * @return mixed $r false on failure (or if the found value is false)
  * @author Marc Groot Koerkamp
  */
function sqm_array_get_value_by_offset($a,$v,$p) {
    $r = false;
    $a_v = array_values($a);
    if (in_array($v, $a_v)) {
        $k = array_search($v, $a_v);
        $p_n = $k + $p;
        if ($p_n >= 0 && $p_n < count($a_v)) {
            $r = $a_v[$p_n];
        }
    }
    return $r;
}


if (!function_exists('array_combine')) {
    /**
     * Creates an array by using one array for keys and another for its values (PHP 5)
     *
     * @param array $aK array keys
     * @param array $aV array values
     * @return mixed $r combined array on success, false on failure
     * @author Marc Groot Koerkamp
     */
    function array_combine($aK, $aV) {
        $r = false;
        $iCaK = count($aK);
        $iCaV = count($aV);
        if ($iCaK && $iCaV && $iCaK == $iCaV) {
            $aC = array();
            for ($i=0;$i<$iCaK;++$i) {
                $aC[$aK[$i]] = $aV[$i];
            }
            $r = $aC;
        }
        return $r;
    }
}


 /**
  * Merges two variables into a single array
  *
  * Similar to PHP array_merge function, but provides same
  * functionality as array_merge without losing array values
  * with same key names.  If the values under identical array
  * keys are both strings and $concat_strings is TRUE, those
  * values are concatenated together, otherwise they are placed
  * in a sub-array and are merged (recursively) in the same manner.
  *
  * If either of the elements being merged is not an array,
  * it will simply be added to the returned array.
  *
  * If both values are strings and $concat_strings is TRUE,
  * a concatenated string is returned instead of an array.
  *
  * @param mixed   $a              First element to be merged
  * @param mixed   $b              Second element to be merged
  * @param boolean $concat_strings Whether or not string values
  *                                should be concatenated instead
  *                                of added to different array
  *                                keys (default TRUE)
  *
  * @return array The merged $a and $b in one array
  *
  * @since 1.5.2
  * @author Paul Lesniewski 
  *
  */
function sqm_array_merge($a, $b, $concat_strings=true) {

    $ret = array();

    if (is_array($a)) {
        $ret = $a;
    } else {
        if (is_string($a) && is_string($b) && $concat_strings) {
            return $a . $b;
        }
        $ret[] = $a;
    }


    if (is_array($b)) {
        foreach ($b as $key => $value) {
            if (isset($ret[$key])) {
                $ret[$key] = sqm_array_merge($ret[$key], $value, $concat_strings);
            } else {
                $ret[$key] = $value;
            }
        }
    } else {
        $ret[] = $b;
    }

    return $ret;

}


