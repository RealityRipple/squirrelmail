<?php

/**
 * array.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This contains functions that work with array manipulation.  They
 * will help sort, and do other types of things with arrays
 *
 * $Id$
 */

function ary_sort($ary,$col, $dir = 1)
{
    /* The globals are used because USORT determines what is passed to comp2 */
    /* These should be $this->col and $this->dir in a class */
    /* Would beat using globals */
    if (!is_array($col)) {
        $col = array($col);
    }
    $GLOBALS['col'] = $col;  /* Column or Columns as an array */
    if ($dir > 0) {
        $dir = 1;
    }
    else {
        $dir = -1;
    }
    /* Direction, a positive number for ascending a negative for descending */
    $GLOBALS['dir'] = $dir;

    usort($ary,'array_comp2');
    return $ary;
}

function array_comp2($a,$b,$i = 0)
{
    global $col;
    global $dir;
    $c = count($col) -1;
    if ($a[$col[$i]] == $b[$col[$i]]) {
        $r = 0;
        while ($i < $c && $r == 0) {
            $i++;
            $r = comp2($a,$b,$i);
        }
    }
    elseif ($a[$col[$i]] < $b[$col[$i]]) {
        return (- $dir);
    } 
    return $dir;
}

function removeElement($array, $element)
{
    $j = 0;
    for ($i = 0;$i < count($array);$i++) {
        if ($i != $element) {
            $newArray[$j] = $array[$i];
            $j++;
        }
    }
    return $newArray;
}

function array_cleave($array1, $column)
{
    $key=0;
    $array2 = array();
    while ($key < count($array1)) {
        array_push($array2, $array1[$key][$column]);
        $key++;
    }
    return ($array2);
}

?>
