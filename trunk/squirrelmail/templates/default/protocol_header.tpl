<?php

/**
 * protocol_header.tpl
 *
 * Template to create the HTML header for each page.
 *
 * The following variables are avilable in this template:
 *      $frames        - boolean value indicating if the page being 
 *                       rendered is a frameset or not
 *      $lang          - string indicating current SM interface language 
 *      $title         - current page title string
 *      $header_tags   - string containing text of any tags to be rendered
 *                       in the page header (meta tags, style links,
 *                       javascript links, etc.)
 *
 * @copyright &copy; 1999-2006 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);


if ($frames) { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
 "http://www.w3.org/TR/1999/REC-html401-19991224/frameset.dtd">
<?php } else { ?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/1999/REC-html401-19991224/loose.dtd">
<?php } ?>
<html<?php if (!empty($lang)) { ?> lang="<?php echo $lang; ?>"<?php } ?>>
<head>
<title><?php if (!empty($title)) { ?><?php echo $title ?><?php } ?></title>
<?php if (!empty($header_tags)) { ?><?php echo $header_tags ?><?php } ?>
</head>


