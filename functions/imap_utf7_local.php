<?php

/**
 * imap_general.php
 *
 * Copyright (c) 1999-2002 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * This implements all functions that do general imap functions.
 *
 * $Id $
 */

function imap_utf7_encode_local($s) {
	$b64_s = '';	// buffer for substring to be base64-encoded
	$utf7_s = '';	// imap-utf7-encoded string
	for ($i = 0; $i < strlen($s); $i++) {
		$c = $s[$i];
		$ord_c = ord($c);
		if ((($ord_c >= 0x20) and ($ord_c <= 0x25)) or
		    (($ord_c >= 0x27) and ($ord_c <= 0x7e))) {
			if ($b64_s) {
				$utf7_s = $utf7_s . '&' . encodeBASE64($b64_s) .'-';
				$b64_s = '';
			}
			$utf7_s = $utf7_s . $c;
		} elseif ($ord_c == 0x26) {
			if ($b64_s) {
				$utf7_s = $utf7_s . '&' . encodeBASE64($b64_s) . '-';
				$b64_s = '';
			}
			$utf7_s = $utf7_s . '&-';
		} else {
			$b64_s = $b64_s . chr(0) . $c;
		}
	}
	//
	// flush buffer
	//
	if ($b64_s) {
		$utf7_s = $utf7_s . '&' . encodeBASE64($b64_s) . '-';
		$b64_s = '';
	}
	return $utf7_s;
}

function imap_utf7_decode_local($s) {
	$b64_s = '';
	$iso_8859_1_s = '';
	for ($i = 0, $len = strlen($s); $i < $len; $i++) {
		$c = $s[$i];
		if (strlen($b64_s) > 0) {
			if ($c == '-') {
				if ($b64_s == '&') {
					$iso_8859_1_s = $iso_8859_1_s . '&';
				} else {
					$iso_8859_1_s = $iso_8859_1_s .
					  decodeBASE64(substr($b64_s, 1));
				}
				$b64_s = '';
			} else {
				$b64_s = $b64_s . $c;
			}
		} else {
			if ($c == '&') {
				$b64_s = '&';
			} else {
				$iso_8859_1_s = $iso_8859_1_s . $c;
			}
		}
	}
	return $iso_8859_1_s;
}

function encodeBASE64($s) {
	$B64Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+,';
	$p = 0;		// phase: 1 / 2 / 3 / 1 / 2 / 3...
	$e = '';	// base64-encoded string
	//foreach($s as $c) {
	for ($i = 0; $i < strlen($s); $i++) {
		$c = $s[$i];
		if ($p == 0) {
			$e = $e . substr($B64Chars, ((ord($c) & 252) >> 2), 1);
			$t = (ord($c) & 3);
			$p = 1;
		} elseif ($p == 1) {
			$e = $e . $B64Chars[($t << 4) + ((ord($c) & 240) >> 4)];
			$t = (ord($c) & 15);
			$p = 2;
		} elseif ($p == 2) {
			$e = $e . $B64Chars[($t << 2) + ((ord($c) & 192) >> 6)];
			$e = $e . $B64Chars[ord($c) & 63];
			$p = 0;
		}
	}
	//
	// flush buffer
	//
	if ($p == 1) {
		$e = $e . $B64Chars[$t << 4];
	} elseif ($p == 2) {
		$e = $e . $B64Chars[$t << 2];
	}
	return $e;
}

function decodeBASE64($s) {
	$B64Values = array(
		'A' =>  0, 'B' =>  1, 'C' =>  2, 'D' =>  3, 'E' =>  4, 'F' =>  5,
		'G' =>  6, 'H' =>  7, 'I' =>  8, 'J' =>  9, 'K' => 10, 'L' => 11,
		'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17,
		'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
		'Y' => 24, 'Z' => 25,
		'a' => 26, 'b' => 27, 'c' => 28, 'd' => 29, 'e' => 30, 'f' => 31,
		'g' => 32, 'h' => 33, 'i' => 34, 'j' => 35, 'k' => 36, 'l' => 37,
		'm' => 38, 'n' => 39, 'o' => 40, 'p' => 41, 'q' => 42, 'r' => 43,
		's' => 44, 't' => 45, 'u' => 46, 'v' => 47, 'w' => 48, 'x' => 49,
		'y' => 50, 'z' => 51,
		'0' => 52, '1' => 53, '2' => 54, '3' => 55, '4' => 56, '5' => 57,
		'6' => 58, '7' => 59, '8' => 60, '9' => 61, '+' => 62, ',' => 63
	);
	$p = 0;
	$d = '';
	$unicodeNullByteToggle = 0;
	for ($i = 0, $len = strlen($s); $i < $len; $i++) {
		$c = $s[$i];
		if ($p == 0) {
			$t = $B64Values[$c];
			$p = 1;
		} elseif ($p == 1) {
			if ($unicodeNullByteToggle) {
				$d = $d . chr(($t << 2) + (($B64Values[$c] & 48) >> 4));
				$unicodeNullByteToggle = 0;
			} else {
				$unicodeNullByteToggle = 1;
			}
			$t = ($B64Values[$c] & 15);
			$p = 2;
		} elseif ($p == 2) {
			if ($unicodeNullByteToggle) {
				$d = $d . chr(($t << 4) + (($B64Values[$c] & 60) >> 2));
				$unicodeNullByteToggle = 0;
			} else {
				$unicodeNullByteToggle = 1;
			}
			$t = ($B64Values[$c] & 3);
			$p = 3;
		} elseif ($p == 3) {
			if ($unicodeNullByteToggle) {
				$d = $d . chr(($t << 6) + $B64Values[$c]);
				$unicodeNullByteToggle = 0;
			} else {
				$unicodeNullByteToggle = 1;
			}
			$t = ($B64Values[$c] & 3);
			$p = 0;
		}
	}
	return $d;
}

?>
