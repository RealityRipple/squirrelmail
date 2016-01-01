<?php

/**
 * SquirrelMail html translation table documentation
 *
 * SquirrelMail provides own implementation of htmlentities() and
 * get_html_translation_table() functions. Functions are called
 * sq_get_html_translation_table() and sq_htmlentities(). They are
 * included in functions/strings.php
 *
 * sq_htmlentities uses same syntax as functions available in php 4.1.0
 * sq_get_html_translation_table adds third option that sets charset.
 *
 * <pre>
 * string sq_htmlentities ( string string [, int quote_style [, string charset]])
 * array sq_get_html_translation_table ( int table [, int quote_style [, string charset]])
 * </pre>
 *
 * If sq_get_html_translation_table function is called with HTML_SPECIALCHARS option,
 * it returns us-ascii translation table. If it is called with HTML_ENTITIES option,
 * it returns translation table defined by charset. Function defaults to us-ascii charset
 * and not to iso-8859-1.
 *
 * Why own functions are used instead of htmlspecialchars() and
 * htmlentities() provided by php.
 *
 * <ul>
 * <li>a) removes dependency on options available only in php v.4.1+</li>
 * <li>b) default behavior of htmlentities() is disastrous in non iso-8859-1 environment.</li>
 * <li>c) provides better control of transformations.</li>
 * </ul>
 *
 * <pre>
 * --- Full list of entities (w3.org html4.01 recommendations)
 * 1. regural symbols
 * U+0022 - &quot;
 *          (replaced only when $style is not ENT_NOQUOTES or 0)
 * U+0026 - &amp;
 * U+0027 - &#39;
 *          (replaced only when $style is ENT_QUOTES or 3)
 * U+003C - &lt;
 * U+003E - &gt;
 *
 * 2. latin1 symbols (HTMLlat1.ent)
 * U+00A0 - &nbsp;    -- no-break space = non-breaking space
 * U+00A1 - &iexcl;   -- inverted exclamation mark
 * U+00A2 - &cent;    -- cent sign
 * U+00A3 - &pound;   -- pound sign
 * U+00A4 - &curren;  -- currency sign
 * U+00A5 - &yen;     -- yen sign
 * U+00A6 - &brvbar;  -- broken bar
 * U+00A7 - &sect;    -- section sign
 * U+00A8 - &uml;     -- diaeresis
 * U+00A9 - &copy;    -- copyright sign
 * U+00AA - &ordf;    -- feminine ordinal indicator
 * U+00AB - &laquo;   -- left-pointing double angle quotation mark = left pointing guillemet
 * U+00AC - &not;     -- not sign
 * U+00AD - &shy;     -- soft hyphen = discretionary hyphen
 * U+00AE - &reg;     -- registered sign = registered trade mark sign
 * U+00AF - &macr;    -- macron = spacing macron = overline = APL overbar
 * U+00B0 - &deg;     -- degree sign
 * U+00B1 - &plusmn;  -- plus-minus sign = plus-or-minus sign
 * U+00B2 - &sup2;    -- superscript two = superscript digit two = squared
 * U+00B3 - &sup3;    -- superscript three = superscript digit three = cubed
 * U+00B4 - &acute;   -- acute accent = spacing acute
 * U+00B5 - &micro;   -- micro sign
 * U+00B6 - &para;    -- pilcrow sign = paragraph sign
 * U+00B7 - &middot;  -- middle dot = Georgian comma = Greek middle dot
 * U+00B8 - &cedil;   -- cedilla = spacing cedilla
 * U+00B9 - &sup1;    -- superscript one = superscript digit one
 * U+00BA - &ordm;    -- masculine ordinal indicator
 * U+00BB - &raquo;   -- right-pointing double angle quotation mark = right pointing guillemet
 * U+00BC - &frac14;  -- vulgar fraction one quarter = fraction one quarter
 * U+00BD - &frac12;  -- vulgar fraction one half = fraction one half
 * U+00BE - &frac34;  -- vulgar fraction three quarters = fraction three quarters
 * U+00BF - &iquest;  -- inverted question mark = turned question mark
 * U+0180 - &Agrave;  -- latin capital letter A with grave = latin capital letter A grave,
 * U+0181 - &Aacute;  -- latin capital letter A with acute
 * U+0182 - &Acirc;   -- latin capital letter A with circumflex
 * U+0183 - &Atilde;  -- latin capital letter A with tilde
 * U+0184 - &Auml;    -- latin capital letter A with diaeresis
 * U+0185 - &Aring;   -- latin capital letter A with ring above = latin capital letter A ring
 * U+0186 - &AElig;   -- latin capital letter AE = latin capital ligature AE
 * U+0187 - &Ccedil;  -- latin capital letter C with cedilla
 * U+0188 - &Egrave;  -- latin capital letter E with grave
 * U+0189 - &Eacute;  -- latin capital letter E with acute
 * U+018A - &Ecirc;   -- latin capital letter E with circumflex
 * U+018B - &Euml;    -- latin capital letter E with diaeresis
 * U+018C - &Igrave;  -- latin capital letter I with grave
 * U+018D - &Iacute;  -- latin capital letter I with acute
 * U+018E - &Icirc;   -- latin capital letter I with circumflex
 * U+018F - &Iuml;    -- latin capital letter I with diaeresis
 * U+0190 - &ETH;     -- latin capital letter ETH
 * U+0191 - &Ntilde;  -- latin capital letter N with tilde
 * U+0192 - &Ograve;  -- latin capital letter O with grave
 * U+0193 - &Oacute;  -- latin capital letter O with acute
 * U+0194 - &Ocirc;   -- latin capital letter O with circumflex
 * U+0195 - &Otilde;  -- latin capital letter O with tilde
 * U+0196 - &Ouml;    -- latin capital letter O with diaeresis
 * U+0197 - &times;   -- multiplication sign
 * U+0198 - &Oslash;  -- latin capital letter O with stroke = latin capital letter O slash
 * U+0199 - &Ugrave;  -- latin capital letter U with grave
 * U+019A - &Uacute;  -- latin capital letter U with acute
 * U+019B - &Ucirc;   -- latin capital letter U with circumflex
 * U+019C - &Uuml;    -- latin capital letter U with diaeresis
 * U+019D - &Yacute;  -- latin capital letter Y with acute
 * U+019E - &THORN;   -- latin capital letter THORN
 * U+019F - &szlig;   -- latin small letter sharp s = ess-zed
 * U+01A0 - &agrave;  -- latin small letter a with grave = latin small letter a grave
 * U+01A1 - &aacute;  -- latin small letter a with acute
 * U+01A2 - &acirc;   -- latin small letter a with circumflex
 * U+01A3 - &atilde;  -- latin small letter a with tilde
 * U+01A4 - &auml;    -- latin small letter a with diaeresis
 * U+01A5 - &aring;   -- latin small letter a with ring above = latin small letter a ring
 * U+01A6 - &aelig;   -- latin small letter ae = latin small ligature ae
 * U+01A7 - &ccedil;  -- latin small letter c with cedilla
 * U+01A8 - &egrave;  -- latin small letter e with grave
 * U+01A9 - &eacute;  -- latin small letter e with acute
 * U+01AA - &ecirc;   -- latin small letter e with circumflex
 * U+01AB - &euml;    -- latin small letter e with diaeresis
 * U+01AC - &igrave;  -- latin small letter i with grave
 * U+01AD - &iacute;  -- latin small letter i with acute
 * U+01AE - &icirc;   -- latin small letter i with circumflex
 * U+01AF - &iuml;    -- latin small letter i with diaeresis
 * U+01B0 - &eth;     -- latin small letter eth
 * U+01B1 - &ntilde;  -- latin small letter n with tilde
 * U+01B2 - &ograve;  -- latin small letter o with grave
 * U+01B3 - &oacute;  -- latin small letter o with acute
 * U+01B4 - &ocirc;   -- latin small letter o with circumflex
 * U+01B5 - &otilde;  -- latin small letter o with tilde
 * U+01B6 - &ouml;    -- latin small letter o with diaeresis
 * U+01B7 - &divide;  -- division sign
 * U+01B8 - &oslash;  -- latin small letter o with stroke = latin small letter o slash,
 * U+01B9 - &ugrave;  -- latin small letter u with grave
 * U+01BA - &uacute;  -- latin small letter u with acute
 * U+01BB - &ucirc;   -- latin small letter u with circumflex
 * U+01BC - &uuml;    -- latin small letter u with diaeresis
 * U+01BD - &yacute;  -- latin small letter y with acute
 * U+01BE - &thorn;   -- latin small letter thorn,
 * U+01BF - &yuml;    -- latin small letter y with diaeresis
 *
 * 3. Special symbols (HTMLspecial.ent)
 * Latin Extended-A
 * U+0152 - &OElig;  --
 * U+0153 - &oelig;  -- latin small ligature oe
 * U+0160 - &Scaron; -- latin capital letter S with caron
 * U+0161 - &scaron; -- latin small letter s with caron
 * U+0178 - &Yuml;   -- latin capital letter Y with diaeresis
 * Spacing Modifier Letters
 * U+02C6 - &circ;   -- modifier letter circumflex accent
 * U+02DC - &tilde;  -- small tilde
 * General Punctuation
 * U+2002 - &ensp;   -- en space
 * U+2003 - &emsp;   -- em space
 * U+2009 - &thinsp; -- thin space
 * U+200C - &zwnj;   -- zero width non-joiner
 * U+200D - &zwj;    -- zero width joiner
 * U+200E - &lrm;    -- left-to-right mark
 * U+200F - &rlm;    -- right-to-left mark
 * U+2013 - &ndash;  -- en dash
 * U+2014 - &mdash;  -- em dash
 * U+2018 - &lsquo;  -- left single quotation mark
 * U+2019 - &rsquo;  -- right single quotation mark
 * U+201A - &sbquo;  -- single low-9 quotation mark
 * U+201C - &ldquo;  -- left double quotation mark
 * U+201D - &rdquo;  -- right double quotation mark
 * U+201E - &bdquo;  -- double low-9 quotation mark
 * U+2020 - &dagger; -- dagger
 * U+2021 - &Dagger; -- double dagger
 * U+2030 - &permil; -- per mille sign
 * U+2039 - &lsaquo; -- single left-pointing angle quotation mark
 * U+203A - &rsaquo; -- single right-pointing angle quotation mark
 * U+20AC - &euro;   -- euro sign
 *
 * 4. Other symbols (HTMLsymbol.ent)
 * Latin Extended-B
 * U+0192 - &fnof;   -- latin small f with hook = function = florin
 * Greek
 * U+0391 - &Alpha;  -- greek capital letter alpha
 * U+0392 - &Beta;   -- greek capital letter beta
 * U+0393 - &Gamma;  -- greek capital letter gamma
 * U+0394 - &Delta;  -- greek capital letter delta
 * U+0395 - &Epsilon; -- greek capital letter epsilon
 * U+0396 - &Zeta;   -- greek capital letter zeta
 * U+0397 - &Eta;    -- greek capital letter eta
 * U+0398 - &Theta;  -- greek capital letter theta
 * U+0399 - &Iota;   -- greek capital letter iota
 * U+039A - &Kappa;  -- greek capital letter kappa
 * U+039B - &Lambda; -- greek capital letter lambda
 * U+039C - &Mu;     -- greek capital letter mu
 * U+039D - &Nu;     -- greek capital letter nu
 * U+039E - &Xi;     -- greek capital letter xi
 * U+039F - &Omicron; -- greek capital letter omicron
 * U+03A0 - &Pi;     -- greek capital letter pi
 * U+03A1 - &Rho;    -- greek capital letter rho
 * U+03A3 - &Sigma;  -- greek capital letter sigma
 * U+03A4 - &Tau;    -- greek capital letter tau
 * U+03A5 - &Upsilon; -- greek capital letter upsilon
 * U+03A6 - &Phi;     -- greek capital letter phi
 * U+03A7 - &Chi;     -- greek capital letter chi
 * U+03A8 - &Psi;     -- greek capital letter psi
 * U+03A9 - &Omega;   -- greek capital letter omega
 * U+03B1 - &alpha;   -- greek small letter alpha
 * U+03B2 - &beta;    -- greek small letter beta
 * U+03B3 - &gamma;   -- greek small letter gamma
 * U+03B4 - &delta;   -- greek small letter delta
 * U+03B5 - &epsilon; -- greek small letter epsilon
 * U+03B6 - &zeta;    -- greek small letter zeta
 * U+03B7 - &eta;     -- greek small letter eta
 * U+03B8 - &theta;   -- greek small letter theta
 * U+03B9 - &iota;    -- greek small letter iota
 * U+03BA - &kappa;   -- greek small letter kappa
 * U+03BB - &lambda;  -- greek small letter lambda
 * U+03BC - &mu;      -- greek small letter mu
 * U+03BD - &nu;      -- greek small letter nu
 * U+03BE - &xi;      -- greek small letter xi
 * U+03BF - &omicron; -- greek small letter omicron
 * U+03C0 - &pi;      -- greek small letter pi
 * U+03C1 - &rho;     -- greek small letter rho
 * U+03C2 - &sigmaf;  -- greek small letter final sigma
 * U+03C3 - &sigma;   -- greek small letter sigma
 * U+03C4 - &tau;     -- greek small letter tau
 * U+03C5 - &upsilon; -- greek small letter upsilon
 * U+03C6 - &phi;     -- greek small letter phi
 * U+03C7 - &chi;     -- greek small letter chi
 * U+03C8 - &psi;     -- greek small letter psi
 * U+03C9 - &omega;   -- greek small letter omega
 * U+03D1 - &thetasym; -- greek small letter theta symbol
 * U+03D2 - &upsih;    -- greek upsilon with hook symbol
 * U+03D6 - &piv;      -- greek pi symbol
 *
 * General Punctuation
 * U+2022 - &bull;     -- bullet = black small circle
 * U+2026 - &hellip;   -- horizontal ellipsis = three dot leader
 * U+2032 - &prime;    -- prime = minutes = feet
 * U+2033 - &Prime;    -- double prime = seconds = inches
 * U+203E - &oline;    -- overline = spacing overscore
 * U+2044 - &frasl;    -- fraction slash
 *
 * Letterlike Symbols
 * U+2118 - &weierp;   -- script capital P = power set = Weierstrass p
 * U+2111 - &image;    -- blackletter capital I = imaginary part
 * U+211C - &real;     -- blackletter capital R = real part symbol
 * U+2122 - &trade;    -- trade mark sign
 * U+2135 - &alefsym;  -- alef symbol = first transfinite cardinal
 *
 * Arrows
 * U+2190 - &larr;     -- leftwards arrow
 * U+2191 - &uarr;     -- upwards arrow
 * U+2192 - &rarr;     -- rightwards arrow
 * U+2193 - &darr;     -- downwards arrow
 * U+2194 - &harr;     -- left right arrow
 * U+21B5 - &crarr;    -- downwards arrow with corner leftwards = carriage return
 * U+21D0 - &lArr;     -- leftwards double arrow
 * U+21D1 - &uArr;     -- upwards double arrow
 * U+21D2 - &rArr;     -- rightwards double arrow
 * U+21D3 - &dArr;     -- downwards double arrow
 * U+21D4 - &hArr;     -- left right double arrow
 *
 * Mathematical Operators
 * U+2200 - &forall;   -- for all
 * U+2202 - &part;     -- partial differential
 * U+2203 - &exist;    -- there exists
 * U+2205 - &empty;    -- empty set = null set = diameter
 * U+2207 - &nabla;    -- nabla = backward difference
 * U+2208 - &isin;     -- element of
 * U+2209 - &notin;    -- not an element of
 * U+220B - &ni;       -- contains as member
 * U+220F - &prod;     -- n-ary product = product sign
 * U+2211 - &sum;      -- n-ary sumation
 * U+2212 - &minus;    -- minus sign
 * U+2217 - &lowast;   -- asterisk operator
 * U+221A - &radic;    -- square root = radical sign
 * U+221D - &prop;     -- proportional to
 * U+221E - &infin;    -- infinity
 * U+2220 - &ang;      -- angle
 * U+2227 - &and;      -- logical and = wedge
 * U+2228 - &or;       -- logical or = vee
 * U+2229 - &cap;      -- intersection = cap
 * U+222A - &cup;      -- union = cup
 * U+222B - &int;      -- integral
 * U+2234 - &there4;   -- therefore
 * U+223C - &sim;      -- tilde operator = varies with = similar to
 * U+2245 - &cong;     -- approximately equal to
 * U+2248 - &asymp;    -- almost equal to = asymptotic to
 * U+2260 - &ne;       -- not equal to
 * U+2261 - &equiv;    -- identical to
 * U+2264 - &le;       -- less-than or equal to
 * U+2265 - &ge;       -- greater-than or equal to
 * U+2282 - &sub;      -- subset of
 * U+2283 - &sup;      -- superset of
 * U+2284 - &nsub;     -- not a subset of
 * U+2286 - &sube;     -- subset of or equal to
 * U+2287 - &supe;     -- superset of or equal to
 * U+2295 - &oplus;    -- circled plus = direct sum
 * U+2297 - &otimes;   -- circled times = vector product
 * U+22A5 - &perp;     -- up tack = orthogonal to = perpendicular
 * U+22C5 - &sdot;     -- dot operator
 *
 * Miscellaneous Technical
 * U+2308 - &lceil;    -- left ceiling = apl upstile
 * U+2309 - &rceil;    -- right ceiling
 * U+230A - &lfloor;   -- left floor = apl downstile
 * U+230B - &rfloor;   -- right floor
 * U+2329 - &lang;     -- left-pointing angle bracket = bra
 * U+232A - &rang;     -- right-pointing angle bracket = ket
 *
 * Geometric Shapes
 * U+25CA - &loz;      -- lozenge
 *
 * Miscellaneous Symbols
 * U+2660 - &spades;   -- black spade suit
 * U+2663 - &clubs;    -- black club suit = shamrock
 * U+2665 - &hearts;   -- black heart suit = valentine
 * U+2666 - &diams;    -- black diamond suit
 * </pre>
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage strings
 */
