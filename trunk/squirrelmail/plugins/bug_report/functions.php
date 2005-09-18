<?php

/**
 * functions for bug_report plugin
 *
 * functions/forms.php and functions/html.php have to be loaded before including this file.
 *
 * @copyright &copy; 2004-2005 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage bug_report
 */

/**
 * Creates gmane search form
 *
 * Requires html v.4.0 compatible browser
 * @return string html formated form
 */
function add_gmane_form() {
  // Start form (need target="_blank" element)
  $ret=addForm('http://search.gmane.org/search.php','get','mform');

  // Add visible options
  $ret.=html_tag('table',
                 html_tag('tr',
                          html_tag('td',_("Search for words:"),'right') .
                          html_tag('td',addInput('query','',50),'left')
                          ) .
                 html_tag('tr',
                          html_tag('td',_("Written by:") . '<br />' .
                                   '<small>' . _("Email addresses only") . '</small>','right') .
                          html_tag('td',addInput('email','',40),'left')
                          ) .
                 html_tag('tr',
                          html_tag('td',_("Mailing list:"),'right') .
                          html_tag('td',addSelect('group',array('gmane.mail.squirrelmail.user'
                                                                => _("SquirrelMail users list"),
                                                                'gmane.mail.squirrelmail.plugins'
                                                                => _("SquirrelMail plugins list"),
                                                                'gmane.mail.squirrelmail.devel'
                                                                => _("SquirrelMail developers list"),
                                                                'gmane.mail.squirrelmail.internationalization'
                                                                => _("SquirrelMail internationalization list"))
                                                  ,'gmane.mail.squirrelmail.user',true),'left')
                          ) .
                 html_tag('tr',
                          html_tag('td',_("Sort by:"),'right') .
                          html_tag('td',addSelect('sort',array('date' => _("Date"),
                                                               'relevance' => _("Relevance"))
                                                  ,'date',true),'left')
                          ) .
                html_tag('tr',
                          html_tag('td',
                                   '<button type="submit" name="submit" value="submit">' . _("Search Archives") . "</button>\n" .
                                   '<button type="reset" name="reset" value="reset">' . _("Reset Form") . "</button>\n"
                                   ,'center','','colspan="2"')
                          ),
                 'center');

  // Close form
  $ret.="</form>\n";

  // Return form
  return $ret;
}

/**
 * Creates SquirrelMail SF bugtracker search form
 *
 * Requires html v.4.0 compatible browser
 * @return string html formated form
 */
function add_sf_bug_form() {
  // Start form
  $ret=addForm('http://sourceforge.net/tracker/index.php','post');

  // Add hidden options (some input fields are hidden from end user)
  $ret.=addHidden('group_id','311') .
    addHidden('atid','100311') .
    addHidden('set','custom') .
    addHidden('_assigned_to','0') .
    addHidden('_status','100') .
    addHidden('_category','100') .
    addHidden('_group','100') .
    addHidden('by_submitter','');

  // Add visible input fields and buttons
  $ret.=html_tag('table',
                 html_tag('tr',
                          html_tag('td',_("Summary keyword:"),'right') .
                          html_tag('td',addInput('summary_keyword','',20,80),'left')
                          ) .
                 html_tag('tr',
                          html_tag('td',_("Sort By:"),'right') .
                          html_tag('td',
                                   addSelect('order',array('artifact_id' => _("ID"),
                                                           'priority' => _("Priority"),
                                                           'summary' => _("Summary"),
                                                           'open_date' => _("Open Date"),
                                                           'close_date' => _("Close Date"),
                                                           'submitted_by' => _("Submitter"),
                                                           'assigned_to' => _("Assignee")),
                                             'artifact_id',true),'left')
                          ) .
                 html_tag('tr',
                          html_tag('td',_("Order:"),'right') .
                          html_tag('td',
                                   addSelect('sort',array('ASC'=>_("Ascending"),
                                                          'DESC'=>_("Descending")),
                                             'DESC',true),
                                   'left')
                          ) .
                 html_tag('tr',
                          html_tag('td',
                                   '<button type="submit" name="submit" value="submit">' . _("Search Bugtracker") . "</button>\n" .
                                   '<button type="reset" name="reset" value="reset">' . _("Reset Form") . "</button>\n"
                                   ,'center','','colspan="2"')
                          )
                 ,'center');

  // Close form
  $ret.="</form>\n";

  // Return form
  return $ret;
}
?>