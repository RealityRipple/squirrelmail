<?php

/**
 * arrays.php
 *
 * Copyright (c) 2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * Contains utility functions for array operations
 *
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
     if (in_array($v1,$ar) && in_array($v2,$a)) {
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
  * @return bool $succes
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
  * @return bool $succes
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
     * @return mixed $r combined array on succes, false on failure
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