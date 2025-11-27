<?php
 global $color, $row_highlite_color;
 global $openssl_cmds, $tmp_dir;

 $row_highlite_color = '';//$color[16];
 $openssl_cmds = SM_PATH . 'plugins/dkim/openssl-cmds.sh';
 $tmp_dir = posix_getpwuid(posix_getuid())['dir'].'/rrs/.maildata/data/tmp/';
?>