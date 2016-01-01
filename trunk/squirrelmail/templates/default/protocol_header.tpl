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
 *      $page_title    - current page title string
 *      $header_tags   - string containing text of any tags to be rendered
 *                       in the page header (meta tags, style links,
 *                       javascript links, etc.)
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);


if ($frames) { 
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
    <?php
} else { 
    ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <?php 
}
if (empty($lang)) {
    ?>
<html>
    <?php
} else {
    ?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $lang; ?>" lang="<?php echo $lang; ?>">
    <?php
}
?>
<head>
<?php
if (!empty($page_title)) {
    ?>
<title><?php echo $page_title; ?></title>
    <?php
}
?>
<?php
if (!empty($header_tags)) {
    echo $header_tags;
} 
?>
</head>


