<?php
/**
 * Default SquirrelMail translate plugin configuration
 *
 * Copyright (c) 2004 The SquirrelMail Project Team
 * Licensed under the GNU GPL. For full terms see the file COPYING.
 *
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */

/** */
global $translate_default_engine;
$translate_default_engine='babelfish';

/**
 *
 */
global $translate_babelfish_enabled;
$translate_babelfish_enabled=true;

/**
 *
 */
global $translate_go_enabled;
$translate_go_enabled=false;

/**
 *
 */
global $translate_dictionary_enabled;
$translate_dictionary_enabled=true;

/**
 *
 */
global $translate_google_enabled;
$translate_google_enabled=true;

/**
 *
 */
global $translate_intertran_enabled;
$translate_intertran_enabled=true;

/**
 *
 */
global $translate_promt_enabled;
$translate_promt_enabled=true;

/**
 *
 */
global $translate_otenet_enabled;
$translate_otenet_enabled=true;

/**
 *
 */
global $translate_gpltrans_enabled;
$translate_gpltrans_enabled=true;

/**
 * Sets URL to custom GPLTrans server CGI.
 *
 * Original URL (http://www.translator.cx/cgi-bin/gplTrans)
 * is no longer valid. If string is empty, GPLTrans is disabled
 * regardless of $translate_gpltrans_enabled setting.
 * @global string $translate_gpltrans_url
 */
global $translate_gpltrans_url;
$translate_gpltrans_url='';

/**
 *
 */
global $disable_compose_translate;
$disable_compose_translate=true;

/** Custom translation engine setup */

/**
 * Controls inclusion of custom translation engines.
 *
 * If you enable custon translation engines, you must include 
 * translate_custom(), translate_custom_showtrad() and 
 * $translate_custom_showoption() functions in your config.
 * @example config-sample.php
 * @global bool $translate_custom_enabled
 */
global $translate_custom_enabled;
$translate_custom_enabled=false;
?>