<?php

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

?>
