<?php

/**
 * Default SquirrelMail translate plugin configuration
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage translate
 */

/**
 * Default translation engine
 * @global string $translate_default_engine
 */
global $translate_default_engine;
$translate_default_engine='babelfish';

/**
 * Babelfish translation engine controls
 * @global boolean $translate_babelfish_enabled
 */
global $translate_babelfish_enabled;
$translate_babelfish_enabled=true;

/**
 * Go.com translation engine controls
 *
 * Translation is no longer available
 * @global boolean $translate_go_enabled
 */
global $translate_go_enabled;
$translate_go_enabled=false;

/**
 * Dictionary.com translation engine controls
 * @global boolean $translate_dictionary_enabled
 */
global $translate_dictionary_enabled;
$translate_dictionary_enabled=true;

/**
 * Google translation engine controls
 * @global boolean $translate_google_enabled
 */
global $translate_google_enabled;
$translate_google_enabled=true;

/**
 * Intertran translation engine controls
 * @global boolean $translate_intertran_enabled
 */
global $translate_intertran_enabled;
$translate_intertran_enabled=true;

/**
 * Promt translation engine controls
 * @global boolean $translate_promt_enabled
 */
global $translate_promt_enabled;
$translate_promt_enabled=true;

/**
 * Otenet translation engine controls
 * @global boolean $translate_otenet_enabled
 */
global $translate_otenet_enabled;
$translate_otenet_enabled=true;

/**
 * Gpltrans translation engine controls
 * @global boolean $translate_gpltrans_enabled
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
 * Translation in compose controls
 *
 * Currently unimplemened and disabled
 * @global boolean $disable_compose_translate
 */
global $disable_compose_translate;
$disable_compose_translate=true;

/** Custom translation engine setup */

/**
 * Controls inclusion of custom translation engine.
 *
 * If you enable custom translation engines, you must include
 * translate_form_custom(), translate_custom_showtrad() and
 * $translate_custom_showoption() functions in your config.
 * @example plugins/translate/config_sample.php
 * @global bool $translate_custom_enabled
 */
global $translate_custom_enabled;
$translate_custom_enabled=false;
