<?php

/**
  * Copyright 2003, Paul James
  *
  * This file contains some methods from the Smarty templating engine version
  * 2.5.0 by Monte Ohrt <monte@ispi.net> and Andrei Zmievski <andrei@php.net>.
  *
  * The SquirrelMail (Foowd) template implementation.
  * Derived from the foowd template implementation and adapted
  * for squirrelmail
  * @copyright 2005-2010 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  *
  */

/**
  * The SquirrelMail PHP Template class.  Extends the base
  * Template class for use with PHP template pages.
  *
  * @author Paul James
  * @author Monte Ohrt <monte at ispi.net>
  * @author Andrei Zmievski <andrei at php.net>
  * @author Paul Lesniewski <paul at squirrelmail.org>
  * @package squirrelmail
  *
  */
class PHP_Template extends Template
{

    /**
      * The templates values array
      *
      * @var array
      *
      */
    var $values = array();


    /**
      * Constructor
      *
      * Please do not call directly.  Use Template::construct_template().
      *
      * @param string $template_id the template ID
      *
      */
    function PHP_Template($template_id) {
//FIXME: find a way to test that this is ONLY ever called 
//       from parent's construct_template() method (I doubt it
//       is worth the trouble to parse the current stack trace)
//        if (???)
//            trigger_error('Please do not use default PHP_Template() constructor.  Instead, use Template::construct_template().', E_USER_ERROR);

        parent::Template($template_id);

    }

    /**
      * Assigns values to template variables
      *
      * @param array|string $tpl_var the template variable name(s)
      * @param mixed $value the value to assign
FIXME: Proposed idea to add a parameter here that turns variable 
       encoding on, so that we can make sure output is always
       run through something like htmlspecialchars() (maybe even nl2br()?)
      *
      */
    function assign($tpl_var, $value = NULL) {

        if (is_array($tpl_var))
        {
            foreach ($tpl_var as $key => $val)
            {
                if ($key != '')
                $this->values[$key] = $val;
            }
        }
        else
        {
            if ($tpl_var != '')
            $this->values[$tpl_var] = $value;
        }

    }

    /**
      * Assigns values to template variables by reference
      *
      * @param string $tpl_var the template variable name
      * @param mixed $value the referenced value to assign
FIXME: Proposed idea to add a parameter here that turns variable 
       encoding on, so that we can make sure output is always
       run through something like htmlspecialchars() (maybe even nl2br()?)
      *
      */
    function assign_by_ref($tpl_var, &$value) {

        if ($tpl_var != '')
            $this->values[$tpl_var] = &$value;

    }

    /**
      * Clears the values of all assigned varaiables.
      *
      */
    function clear_all_assign() {

        $this->values = array();

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

        // just looking for one value
        // 
        if (!empty($varname)) {
            if (!empty($this->values[$varname]))
                return $this->values[$varname];
            else
// FIXME: this OK?  What does Smarty do?
                return NULL;
        }


        // return all variable values
        //
        return $this->values;

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
       run through something like htmlspecialchars() (maybe even nl2br()?)
      *
      */
    function append($tpl_var, $value = NULL, $merge = FALSE)
    {
        if (is_array($tpl_var))
        {
            foreach ($tpl_var as $_key => $_val)
            {
                if ($_key != '')
                {
                    if(isset($this->values[$_key]) && !is_array($this->values[$_key]))
                        settype($this->values[$_key],'array');
  
                    if($merge && is_array($_val))
                    {
                        // FIXME: Tentative testing seems to indicate that
                        //        this does not mirror Smarty behavior; Smarty
                        //        seems to append the full array as a new element
                        //        instead of merging, so this behavior is technically
                        //        more "correct", but Smarty seems to differ
                        foreach($_val as $_mkey => $_mval)
                            $this->values[$_key][$_mkey] = $_mval;
                    }
                    else
                        $this->values[$_key][] = $_val;
                }
            }
        }
        else
        {
            if ($tpl_var != '' && isset($value))
            {
                if(isset($this->values[$tpl_var]) && !is_array($this->values[$tpl_var]))
                    settype($this->values[$tpl_var],'array');
  
                if($merge && is_array($value))
                {
                    foreach($value as $_mkey => $_mval)
                        $this->values[$tpl_var][$_mkey] = $_mval;
                }
                else
                    $this->values[$tpl_var][] = $value;
            }
        }
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
       run through something like htmlspecialchars() (maybe even nl2br()?)
      *
      */
    function append_by_ref($tpl_var, &$value, $merge = FALSE)
    {
        if ($tpl_var != '' && isset($value))
        {
            if(!@is_array($this->values[$tpl_var]))
                settype($this->values[$tpl_var],'array');
  
            if ($merge && is_array($value))
            {
                foreach($value as $_key => $_val)
                    $this->values[$tpl_var][$_key] = &$value[$_key];
            }
            else
              $this->values[$tpl_var][] = &$value;
        }
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

        // place values array directly in scope
        // ($t?  let's try to be more verbose please :-) )
        //
        $t = &$this->values;

        ob_start();
        include($filepath);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;

    }

}

