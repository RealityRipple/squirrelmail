<?php
/**
 * Copyright (c) 2005 The SquirrelMail Project Team
 * This file is part of SquirrelMail webmail interface documentation.
 *
 * SquirrelMail is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * SquirrelMail is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SquirrelMail; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * @package squirrelmail
 */

/**
 * Controls anti indexing headers
 * @global boolean $block_robots
 */
$block_robots = true;

/**
 * Controls page css
 * @global string $custom_css
 */
$custom_css = '';

/**
 * Controls display of links to other formats
 * @global boolean $htmlonly
 */
$htmlonly = false;

/**
 * Controls format of manual links
 * @global boolean $packed_manuals
 */
$packed_manuals = false;

/** include site configuration */
if (file_exists('site_config.inc')) {
    // file can contain variables that are used to control header
    include_once('./site_config.inc');
}

/** Page header */
header('Content-Type: text/html; charset=iso-8859-1');

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';

echo "\n<html lang=\"en_US\">\n<head>\n";

if ($block_robots) echo "<meta name=\"robots\" content=\"noindex,nofollow\">\n";

if ( $custom_css != '' ) {
    echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"$custom_css\">\n";
}

echo "</head>\n<body>\n";

/** check if main admin documentation file exists */
if (!file_exists('admin.html')) {
    echo "<p>You must compile this documentation with linuxdoc tools.</p>\n";
    echo "<p>See <a href=\"README\">README</a> file.</p>\n";
} else {
    echo "<h1>SquirrelMail Administrator's Manual</h1>\n";
    echo "<p>Read <a href=\"admin.html\">html version</a> online</p>\n";
    if (! $htmlonly) {
        if (! $packed_manuals) {
            echo "<p>Other formats</p>\n"
                ."<ul>\n"
                ."<li><a href=\"admin.dvi\">Dvi</a></li>\n"
                ."<li><a href=\"admin.info\">info</a></li>\n"
                ."<li><a href=\"admin.lyx\">lyx</a></li>\n"
                ."<li><a href=\"admin.pdf\">pdf</a></li>\n"
                ."<li><a href=\"admin.ps\">postscript</a></li>\n"
                ."<li><a href=\"admin.rtf\">rfc</a></li>\n"
                ."<li><a href=\"admin.sgml\">sgml (original)</a></li>\n"
                ."<li><a href=\"admin.tex\">tex</a></li>\n"
                ."<li><a href=\"admin.txt\">txt</a></li>\n"
                ."</ul>\n";
        } else {	
           echo "<p>Other formats</p>\n"
                ."<ul>\n"
                ."<li><a href=\"admin.dvi.gz\">Dvi</a></li>\n"
                ."<li><a href=\"admin.info.gz\">info</a></li>\n"
                ."<li><a href=\"admin.lyx.gz\">lyx</a></li>\n"
                ."<li><a href=\"admin.pdf\">pdf</a></li>\n"
                ."<li><a href=\"admin.ps.gz\">postscript</a></li>\n"
                ."<li><a href=\"admin.rtf.gz\">rfc</a></li>\n"
                ."<li><a href=\"admin.sgml.gz\">sgml (original)</a></li>\n"
                ."<li><a href=\"admin.tex.gz\">tex</a></li>\n"
                ."<li><a href=\"admin.txt.gz\">txt</a></li>\n"
                ."</ul>";
        }
    }
    echo "<h1>SquirrelMail User's Manual</h1>\n";
    echo "<p>Read <a href=\"user.html\">html version</a> online</p>\n";
    if (! $htmlonly) {
        if (! $packed_manuals) {
            echo "<p>Other formats</p>\n"
                ."<ul>\n"
                ."<li><a href=\"user.dvi\">Dvi</a></li>\n"
                ."<li><a href=\"user.info\">info</a></li>\n"
                ."<li><a href=\"user.lyx\">lyx</a></li>\n"
                ."<li><a href=\"user.pdf\">pdf</a></li>\n"
                ."<li><a href=\"user.ps\">postscript</a></li>\n"
                ."<li><a href=\"user.rtf\">rfc</a></li>\n"
                ."<li><a href=\"user.sgml\">sgml (original)</a></li>\n"
                ."<li><a href=\"user.tex\">tex</a></li>\n"
                ."<li><a href=\"user.txt\">txt</a></li>\n"
                ."</ul>\n";
        } else {	
           echo "<p>Other formats</p>\n"
                ."<ul>\n"
                ."<li><a href=\"user.dvi.gz\">Dvi</a></li>\n"
                ."<li><a href=\"user.info.gz\">info</a></li>\n"
                ."<li><a href=\"user.lyx.gz\">lyx</a></li>\n"
                ."<li><a href=\"user.pdf\">pdf</a></li>\n"
                ."<li><a href=\"user.ps.gz\">postscript</a></li>\n"
                ."<li><a href=\"user.rtf.gz\">rfc</a></li>\n"
                ."<li><a href=\"user.sgml.gz\">sgml (original)</a></li>\n"
                ."<li><a href=\"user.tex.gz\">tex</a></li>\n"
                ."<li><a href=\"user.txt.gz\">txt</a></li>\n"
                ."</ul>";
        }
    }
    echo "<h1>SquirrelMail Developer's Manual</h1>\n";
    echo "<p>Read <a href=\"devel.html\">html version</a> online</p>\n";
    if (! $htmlonly) {
        if (! $packed_manuals) {
            echo "<p>Other formats</p>\n"
                ."<ul>\n"
                ."<li><a href=\"devel.dvi\">Dvi</a></li>\n"
                ."<li><a href=\"devel.info\">info</a></li>\n"
                ."<li><a href=\"devel.lyx\">lyx</a></li>\n"
                ."<li><a href=\"devel.pdf\">pdf</a></li>\n"
                ."<li><a href=\"devel.ps\">postscript</a></li>\n"
                ."<li><a href=\"devel.rtf\">rfc</a></li>\n"
                ."<li><a href=\"devel.sgml\">sgml (original)</a></li>\n"
                ."<li><a href=\"devel.tex\">tex</a></li>\n"
                ."<li><a href=\"devel.txt\">txt</a></li>\n"
                ."</ul>\n";
        } else {	
           echo "<p>Other formats</p>\n"
                ."<ul>\n"
                ."<li><a href=\"devel.dvi.gz\">Dvi</a></li>\n"
                ."<li><a href=\"devel.info.gz\">info</a></li>\n"
                ."<li><a href=\"devel.lyx.gz\">lyx</a></li>\n"
                ."<li><a href=\"devel.pdf\">pdf</a></li>\n"
                ."<li><a href=\"devel.ps.gz\">postscript</a></li>\n"
                ."<li><a href=\"devel.rtf.gz\">rfc</a></li>\n"
                ."<li><a href=\"devel.sgml.gz\">sgml (original)</a></li>\n"
                ."<li><a href=\"devel.tex.gz\">tex</a></li>\n"
                ."<li><a href=\"devel.txt.gz\">txt</a></li>\n"
                ."</ul>";
        }
    }
}
?>
</body>
</html>