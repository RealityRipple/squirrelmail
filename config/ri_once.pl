#!/usr/bin/env perl
# ri_once.pl
# Wouter Teepe (wouter@teepe.com)
#
# A simple configure script to fix the ri_once issue
#
# $Id$
############################################################              

$debug = 0;

# sets the xterm color (only used when $debug=1)
sub color {
    $t = $_[0];
    if ($t == 0) {
	print "\e[0m";
    } else {if ($t == 1) {
	print "\e[0m\e[31;43m";
    } else {if ($t == 2) {
	print "\e[0m\e[30;42m";
    } else {if ($t == 3) {
	print "\e[0m\e[33;41m";
    }}}}
}

# prints arg1 with color arg2 (only used when $debug=1)
sub myprintdebug {
    $line = $_[0];
    $color = $_[1];
    while ($line ne "") {
        $pos = index($line, "\n");
	if ($pos == -1) {
	    &color($color);
	    print $line;
	    $line = "";
	} else {
	    &color($color);
	    $str = substr($line, 0, $pos);
	    print $str;
	    print "\e[30;40m\n";
	    $line = substr($line, $pos+1);
	}
    }
    
}

# print arg1 to term or $out
sub myprint {
    if ($debug) {
	&myprintdebug;
    } else {
	$out .= $_[0];
    }
}

# parse php code fore include's and require's
sub code {
    $lastbyte = 0;
    while ($phpcode ne "") {
        $inc = index($phpcode, 'include');
        $req = index($phpcode, 'require');
        if (($req == -1) or (($inc != -1) and ($inc < $req))) {
	    $index = $inc;
        } else {
	    $index = $req;
        }
        if ($index != -1) {
	    &myprint(substr($phpcode, 0, $index), 1);
	    if ($index > 0) {
		$r = ord(substr($phpcode, $index-1, 1));
	    } else {
		$r = $lastbyte;
	    }
	    $falsematch = 0;
	    if ((($r >= 125) and ($r <= 255)) or ($r == ord("_")) or 
                (($r >= ord("A")) and ($r <= ord("Z"))) or
                (($r >= ord("a")) and ($r <= ord("z"))) or
                (($r >= ord("1")) and ($r <= ord("9")))) {
	        $falsematch = 1;
	    }
	    if (!$falsematch) {
	        $o = index($phpcode, '(', $index);
	        if ($o == -1) {
                    $p = substr($phpcode, $index+7);
	        } else {
                    $p = substr($phpcode, $index+7, $o-$index-7);
	        }
	        if (!($p =~ /^\s*$/)) {
	            $falsematch = 1;
	        }
	    }
	    if (!$falsematch) {
		$mod++;
	        &myprint('/* \'_once\' Added by ri_once */ ', 3);
	    }
	    &myprint(substr($phpcode, $index, 7), 1);
	    if (!$falsematch) {
	        &myprint('_once', 3);
	    }
            $lastbyte = ord('e');
	    $phpcode = substr($phpcode, $index+7);
	} else {
	    &myprint($phpcode, 1);
	    $phpcode = '';
	}
    }
}

# parse php block for comments and strings
sub php {
    while ($htmlcomment ne "") {
	$len = length($htmlcomment);
	$doublequote = index($htmlcomment, '"');
	$singlequote = index($htmlcomment, "'");
	$pound       = index($htmlcomment, '#');
	$slashslash  = index($htmlcomment, '//');
	$slashstar   = index($htmlcomment, '/*');
	if ($doublequote == -1) { $doublequote = $len; }
	if ($singlequote == -1) { $singlequote = $len; }
	if ($pound       == -1) { $pound       = $len; }
	if ($slashslash  == -1) { $slashslash  = $len; }
	if ($slashstar   == -1) { $slashstar   = $len; }

                                   $pos = $doublequote; $end = '"'; $sl = 1; $el = 1;
	if ($pos > $singlequote) { $pos = $singlequote; $end = "'"; }
	if ($pos > $pound)       { $pos = $pound;       $end = "\n"; }
	if ($pos > $slashslash)  { $pos = $slashslash;  $end = "\n"; $sl = 2; }
	if ($pos > $slashstar)   { $pos = $slashstar;   $end = '*/'; $sl = 2; $el = 2; }

        if ($pos < $len) {
	    $phpcode = substr($htmlcomment, 0, $pos);
	    $rest = substr($htmlcomment, $pos);
	    $eoc = index($rest, $end, $sl);
	    if (($end = '"') or ($end = "'")) {
		while (($eoc > 0) and (substr($rest, $eoc-1, 1) eq '\\')) {
		    $eoc = index($rest, $end, $eoc+1);
		}
	    }
	    if ($eoc == -1) { $eoc = length($rest); }
	    $phpcomment = substr($rest, 0, $eoc+$el);
	    $htmlcomment = substr($rest, $eoc+$el);
	    &code;
	    &myprint($phpcomment, 2);
	} else {
	    $phpcode = $htmlcomment;
	    &code;
	    $htmlcomment = '';
	}
    }
}

# parse html file for php blocks
sub html {
    while ($text ne '') {
	$index = index($text, '<?');
	if ($index != -1) {
	    $html = substr($text, 0, $index+2);
	    $text = substr($text, $index+2);
	    &myprint($html, 0);
	    $index = index($text, '?>');
	    if ($index == -1) { $index = length($text); }
	    $htmlcomment = substr($text, 0, $index);
	    $text = substr($text, $index);
            $type = substr($htmlcomment, 0, 3);
            if (uc($type) eq "PHP") {
		&myprint($type, 0);
		$htmlcomment = substr($htmlcomment, 3);
	    } else { if (substr($htmlcomment, 0, 1) eq '=') {
		$mod++;
		&myprint('php /* \'=\' Modified to \'php blah echo\'by ri_once */ echo ', 3);
		$htmlcomment = substr($htmlcomment, 1);
	    } else {
		$mod++;
		&myprint('php /* \'php\' Added by ri_once */', 3);
	    }}
            &php;
	} else {
	    &myprint($text, 0);
	    $text ='';
	}
    }
}

# process a file
sub dofile {
    $file = $_[0];

    open (FILE, '<'.$file);
    $text = '';
    $htmlcomment = '';
    $phpcode = '';
    $out = '';
    $mod = 0;
    while ($line = <FILE>) {
        $text .= $line;
    }
    close (FILE);

    &html;
    if ($debug) {
	&color(0);
	print "\n";
    } else {
	if ($mod) {
	    $out = "<?php /* Modified at $mod places by ri_once */ ?>\n" . $out;
#            $mode = (stat($file))[2];
	    rename($file, $file.'.before_ri_once');
	    open (FILE, '>'.$file);
	    print FILE $out;
	    close(FILE);
#            chmod($stats[2], $file);
	}
    }
}

# process a directory recursively
sub dodir {
    my $dirname;
    my $file;
    my $full;
    my @files;
    my $i;
    $dirname = $_[0];
    $dirname =~ s/\/$//;
    opendir(DIR, $dirname) or die "can't opendir $dirname: $!";
    $i = 0;
    while (defined($file = readdir(DIR))) {
        @files[$i++] = $file;
    }
    $i = 0;
    while (defined($file = @files[$i++])) {
	next if $file =~ /^\.\.?$/;
	$full = $dirname.'/'.$file;
#        print "found: $full\n";
	if (-d $full) {
#            print "doing dir: $full\n";
	    &dodir($full);
	} else { if ($file =~ /.*\.php$/) {
#            print "doing file: $full\n";
	    &dofile($full);
	}}
    }
    closedir(DIR);
}

#&dofile($ARGV[0]);
&dodir($ARGV[0]);

