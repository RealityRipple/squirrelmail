<?php
/**
 * SquirrelMail CSS template
 *
 * Template is used by style.php script to generate css file used by 
 * SquirrelMail scripts.
 * 
 * Available constants
 *
 * Color codes used by selected theme:
 * <ul>
 *   <li>SQM_BACKGROUND - background color
 *   <li>SQM_BACKGROUND_LEFT - background of folder tree
 *   <li>SQM_TEXT_STANDARD - text color
 *   <li>SQM_TEXT_STANDARD_LEFT - text color of folder tree
 *   <li>todo: other constants should be documented here
 * </ul>
 *
 * Optional template variables
 * <ul>
 *   <li>fontfamily - string with list of fonts used by selected style.
 *   <li>fontsize - integer with selected font size value.
 * </ul>
 * Variables are set to empty string, when value is not set.
 *
 * @copyright &copy; 1999-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 * @subpackage templates
 */

/* retrieve the template vars */
extract($t);

?>
body, td, th, dd, dt, h1, h2, h3, h4, h5, h6, p, ol, ul, li {
<?php if($fontfamily) echo '  font-family: '.$fontfamily.";\n";?>
}
body, small {
<?php if($fontsize) echo '  font-size: '.($fontsize-2)."pt;\n";?>
}

body {
    color:  <?php echo SQM_TEXT_STANDARD; ?>;
    background-color: <?php echo SQM_BACKGROUND; ?>;
}
body.leftmain {
    color:  <?php echo SQM_TEXT_STANDARD_LEFT; ?>;
    background-color: <?php echo SQM_BACKGROUND_LEFT; ?>;
}

/* right links (a:link, a:visited, a:hover, a:active) */
a {
    color: <?php echo SQM_LINK; ?>;
}
/* left links */
/* TODO: recheck link css */
a.leftmain {
    color:  <?php echo SQM_LINK_LEFT; ?>;
}

td, th {
<?php if($fontsize) echo '  font-size: '.$fontsize."pt;\n";?>
}
textarea, pre {
  font-family: monospace;
<?php if($fontsize) echo '  font-size: '.($fontsize-1)."pt;\n";?>
}

/* formating of error template */
.thead_caption {
    font-weight: bold;
    text-align: center;
}

.error_list {
}
.error_table {
    color: <?php echo $color[14]; ?>;
    border: 2px solid <?php echo $color[0]; ?>;
    background-color: <?php echo $color[3]; ?>;
    width: 100%;
}
.error_thead {
    background-color: <?php echo $color[10]; ?>;
}
.error_thead_caption {
    background-color: <?php echo $color[10]; ?>;
}
.error_row {
    color: <?php echo $color[14]; ?>;
}
.error_val {
    color: <?php echo $color[8]; ?>;
    width: 80%;
    border: 2px solid <?php echo $color[0]; ?>;

}
.error_key {
    width: 20%;
    border: 2px solid <?php echo $color[0]; ?>;
    color: <?php echo $color[14]; ?>;
    font-weight: bold;
    font-style: italic;
    background-color: <?php echo $color[0]; ?>;
}

/* form fields */
input.sqmtextfield{
}
input.sqmpwfield {
}
input.sqmcheckbox {
}
input.sqmradiobox {
}
input.sqmhiddenfield {
}
input.sqmsubmitfield {
}
input.sqmresetfield {
}
input.sqmtextarea {
}
