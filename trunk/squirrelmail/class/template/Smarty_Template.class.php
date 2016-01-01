<?php

/**
  * Smarty_Template.class.php
  *
  * This file contains a Template subclass intended as a bridge between
  * SquirrelMail and Smarty.  All abstract methods from the Template class
  * are implemented here.
  *
  * @copyright 2003-2016 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage Template
  * @since 1.5.2
  *
  */

/**
  * The SquirrelMail Smarty Template class.  Extends the base
  * Template class for use with Smarty template pages.
  *
  * @author Paul Lesniewski <paul at squirrelmail.org>
  * @package squirrelmail
  *
  */
class Smarty_Template extends Template
{

    /**
      * The Smarty template object
      *
      * @var object
      *
      */
    var $smarty_template = null;


    /**
      * Constructor
      *
      * Please do not call directly.  Use Template::construct_template().
      *
      * @param string $template_id the template ID
      *
      */
    function Smarty_Template($template_id) {
//FIXME: find a way to test that this is ONLY ever called 
//       from parent's construct_template() method (I doubt it
//       is worth the trouble to parse the current stack trace)
//        if (???)
//            trigger_error('Please do not use default Smarty_Template() constructor.  Instead, use Template::construct_template().', E_USER_ERROR);

        parent::Template($template_id);


        // load smarty settings
        //
        // instantiate and set up Smarty object
        //
        $smarty_path 
            = Template::get_template_config($this->template_set_id, 'smarty_path');
        require($smarty_path);
        $this->smarty_template = new Smarty();
        $this->smarty_template->compile_dir 
            = Template::get_template_config($this->template_set_id, 'smarty_compile_dir');
        $this->smarty_template->cache_dir 
            = Template::get_template_config($this->template_set_id, 'smarty_cache_dir');
        $this->smarty_template->config_dir 
            = Template::get_template_config($this->template_set_id, 'smarty_config_dir');

        // note that we do not use Smarty's template_dir 
        // because SquirrelMail has its own method of 
        // determining template file paths
        //
        //$this->smarty_template->template_dir = 

    }

    /**
      * Assigns values to template variables
      *
      * @param array|string $tpl_var the template variable name(s)
      * @param mixed $value the value to assign
FIXME: Proposed idea to add a parameter here that turns variable
       encoding on, so that we can make sure output is always
       run through something like sm_encode_html_special_chars() (maybe even nl2br()?)
      *
      */
    function assign($tpl_var, $value = NULL) {

        $this->smarty_template->assign($tpl_var, $value);

    }

    /**
      * Assigns values to template variables by reference
      *
      * @param string $tpl_var the template variable name
      * @param mixed $value the referenced value to assign
FIXME: Proposed idea to add a parameter here that turns variable
       encoding on, so that we can make sure output is always
       run through something like sm_encode_html_special_chars() (maybe even nl2br()?)
      *
      */
    function assign_by_ref($tpl_var, &$value) {

        $this->smarty_template->assign_by_ref($tpl_var, $value);

    }

    /**
      * Clears the values of all assigned varaiables.
      *
      */
    function clear_all_assign() {

        $this->smarty_template->clear_all_assign();

    }

    /**
      * Returns assigned variable value(s).
      *
      * @param string $varname If given, the value of that variable
      *                        is returned, assuming it has been
      *                        previously assigned.  If not specified
      *                        an array of all assigned variables is
      *                        returned. (optional)
      *
      * @return mixed Desired single variable value or list of all 
      *               assigned variable values.
      *
      */
    function get_template_vars($varname=NULL) {

        return $this->smarty_template->get_template_vars($varname);

    }

    /**
      * Appends values to template variables
      *
      * @param array|string $tpl_var the template variable name(s)
      * @param mixed $value the value to append
      * @param boolean $merge when $value is given as an array, 
      *                       this indicates whether or not that 
      *                       array itself should be appended as 
      *                       a new template variable value or if 
      *                       that array's values should be merged 
      *                       into the existing array of template 
      *                       variable values
FIXME: Proposed idea to add a parameter here that turns variable
       encoding on, so that we can make sure output is always
       run through something like sm_encode_html_special_chars() (maybe even nl2br()?)
      *
      */
    function append($tpl_var, $value = NULL, $merge = FALSE) {

        $this->smarty_template->append($tpl_var, $value, $merge);

    }

    /**
      * Appends values to template variables by reference
      *
      * @param string $tpl_var the template variable name
      * @param mixed $value the referenced value to append
      * @param boolean $merge when $value is given as an array, 
      *                       this indicates whether or not that 
      *                       array itself should be appended as 
      *                       a new template variable value or if 
      *                       that array's values should be merged 
      *                       into the existing array of template 
      *                       variable values
FIXME: Proposed idea to add a parameter here that turns variable
       encoding on, so that we can make sure output is always
       run through something like sm_encode_html_special_chars() (maybe even nl2br()?)
      *
      */
    function append_by_ref($tpl_var, &$value, $merge = FALSE) {

        $this->smarty_template->append_by_ref($tpl_var, $value, $merge);

    }

    /**
      * Applys the template and generates final output destined
      * for the user's browser
      *
      * @param string $filepath The full file path to the template to be applied
      *
      * @return string The output for the given template
      *
      */
    function apply_template($filepath) {

        // if being passed a raw .css or .js file, default 
        // Smarty delimiters will cause errors
        //
        if (strrpos($filepath, '.css') === (strlen($filepath) - 4)
         || strrpos($filepath, '.js') === (strlen($filepath) - 3)) {
            $this->smarty_template->left_delimiter = '{=';
            $this->smarty_template->right_delimiter = '=}';
        }

        // Smarty wants absolute paths
        //
        if (strpos($filepath, '/') === 0)
            return $this->smarty_template->fetch('file:' . $filepath);
        else
            return $this->smarty_template->fetch('file:' . getcwd() . '/' . $filepath);

    }

}

