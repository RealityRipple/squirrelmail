<?php
/**
 * Command line spellcheck class
 *
 * $params = array();
 * $params['spell_command'] = 'ispell -d american -a';
 * $params['use_proc_open'] = false; // (check_php_version(4,3))
 * $params['temp_dir'] = '/tmp/'; // $attachment_dir
 * $params['userdic'] = array(); // user's dictionary
 * $params['debug'] = true;
 * 
 * $spell = new cmd_spell($params);
 * // check $spell->error buffer
 * 
 * $text = "Quick brownn fox brownn\n\nbrownn squirrel.\ntwentytwo owttnewt";
 * 
 * $results = $spell->check_text($text);
 * // check $spell->error buffer
 * // parse $results
 *
 * @copyright 1999-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/**
 * Command line spellcheck class, compatible with ispell and aspell.
 * @package plugins
 * @subpackage squirrelspell
 */
class cmd_spell extends squirrelspell {
    /**
     * @var string
     */
    var $spell_command = '';
    var $userdic = array();
    /**
     * Controls which function is used to execute ispell. proc_open() 
     * should be used in PHP 4.3+. exec() can be used in older PHP versions.
     * @var boolean
     */
    var $use_proc_open = false;
    /**
     * @var string
     */
    var $temp_dir = '';
    /**
     */
    var $debug = false;

    var $missed_words = array();

    /**
     * Constructor function
     * @param array $aParams
     */
    function cmd_spell($aParams=array()) {
        if (! isset($aParams['spell_command'])) {
            return $this->set_error('Spellcheck command is not set.');
        } else {
            $this->spell_command = $aParams['spell_command'];
        }

        if (isset($aParams['userdic'])) {
            $this->userdic = $aParams['userdic'];
        }

        if (isset($aParams['use_proc_open'])) {
            $this->use_proc_open = (bool) $aParams['use_proc_open'];
        }

        if (isset($aParams['temp_dir'])) {
            $this->temp_dir = $aParams['temp_dir'];
            // add slash to attachment directory, if it does not end with slash. 
            if (substr($this->temp_dir, -1) != '/') {
                $this->temp_dir = $this->temp_dir . '/';
            }
        } elseif (!$this->use_proc_open) {
            return $this->set_error('Temporally directory is not set.');
        }

        if (isset($aParams['debug']) && (bool) $aParams['debug']) {
            $this->debug = true;
            error_reporting(E_ALL);
            ini_set('display_errors',1);
        }

    }

    /**
     * @param string $sText
     * @return mixed array with command output or false.
     */
    function proc_open_spell($sText) {
        $descriptorspec = array(
             0 => array('pipe', 'r'),  // stdin is a pipe that the child will read from
             1 => array('pipe', 'w'),  // stdout is a pipe that the child will write to
             2 => array('pipe', 'w'), // stderr is a pipe that the child will write to
             );

        if ($this->debug) {
            $spell_proc = proc_open($this->spell_command, $descriptorspec, $pipes);
        } else {
            $spell_proc = @proc_open($this->spell_command, $descriptorspec, $pipes);
        }

        if ( ! is_resource($spell_proc) ) {
            return $this->set_error(sprintf(_("Could not run the spellchecker command (%s)."),
                                            $this->spell_command));
        }

        if ( ! @fwrite($pipes[0],$sText) ) {
            $this->set_error(_("Error while writing to pipe."));
            // close all three $pipes here.
            for($i=0; $i<=2; $i++) {
                // disable all fclose error messages
                @fclose($pipes[$i]);
            }
            return false;
        }

        fclose($pipes[0]);

        $sqspell_output = array();
        for($i=1; $i<=2; $i++) {
            while(!feof($pipes[$i])) {
                array_push($sqspell_output, rtrim(fgetss($pipes[$i],999),"\r\n"));
            }
            fclose($pipes[$i]);
        }

        if (proc_close($spell_proc)) {
            $error = '';
            foreach ($sqspell_output as $line) {
                $error.= $line . "\n";
            }
            return $this->set_error($error);
        } else {
            return $sqspell_output;
        }
    }

    /**
     * @param string $sText
     * @return mixed array with command output or false.
     */
    function exec_spell($sText) {
        // find unused file in attachment directory
        do {
            $floc = $this->temp_dir . md5($sText . microtime());
        } while (file_exists($floc));

        if ($this->debug) {
            $fp = fopen($floc, 'w');
        } else {
            $fp = @fopen($floc, 'w');
        }
        if ( ! is_resource($fp) ) {
            return $this->set_error(sprintf(_("Could not open temporary file '%s'."),
                                     $floc) );
        }

        if ( ! @fwrite($fp, $sText) ) {
            $this->set_error(sprintf(_("Error while writing to temporary file '%s'."),
                                     $floc) );
            // close file descriptor
            fclose($fp);
            return false;
        }
        fclose($fp);

        exec("$this->spell_command < $floc 2>&1", $sqspell_output, $exitcode);

        unlink($floc);

        if ($exitcode) {
            $error = '';
            foreach ($sqspell_output as $line) {
                $error.= $line . "\n";
            }
            return $this->set_error($error);
        } else {
            return $sqspell_output;
        }
    }

    /**
     * Prepares string for ispell/aspell parsing
     * 
     * Function adds an extra space at the beginning of each line. This way
     * ispell/aspell don't treat these as command characters.
     * @param string $sText
     * @return string
     */
    function prepare_text($sText) {
        // prepend space to every sqspell_new_text line
        $sText = str_replace("\r\n","\n",$sText);
        $ret = '';
        foreach (explode("\n",$sText) as $line) {
            $ret.= ' ' . $line . "\n";
        }
        return $ret;
    }

    /**
     * Checks block of text
     * @param string $sText text
     * @return array
     */
    function check_text($sText) {
        $this->missed_words = array();

        $sText = $this->prepare_text($sText);

        if ($this->use_proc_open) {
            $sqspell_output = $this->proc_open_spell($sText);
        } else {
            $sqspell_output = $this->exec_spell($sText);
        }

        /**
         * Define some variables to be used during the processing.
         */
        $current_line=0;
        /**
         * Now we process the output of sqspell_command (ispell or aspell in
         * ispell compatibility mode, whichever). I'm going to be scarce on
         * comments here, since you can just look at the ispell/aspell output
         * and figure out what's going on. ;) The best way to describe this is
         * "Dark Magic".
         */
        for ($i=0; $i<sizeof($sqspell_output); $i++){
            switch (substr($sqspell_output[$i], 0, 1)){
            /**
             * Line is empty.
             * Ispell adds empty lines when an end of line is reached
             */
            case '':
                $current_line++;
                break;
            /**
             * Line begins with "&".
             * This means there's a misspelled word and a few suggestions.
             */
            case '&':
                list($left, $right) = explode(": ", $sqspell_output[$i]);
                $tmparray = explode(" ", $left);
                $sqspell_word=$tmparray[1];
                /**
                 * Check if the word is in user dictionary.
                 */
                if (! in_array($sqspell_word,$this->userdic)){
                    $sqspell_symb=intval($tmparray[3])-1;
                    // add suggestions
                    if (!isset($this->missed_words[$sqspell_word])) {
                        foreach(explode(',',$right) as $word) {
                            $this->missed_words[$sqspell_word]['suggestions'][] = trim($word);
                        }
                    }
                    // add location
                    $this->missed_words[$sqspell_word]['locations'][] = "$current_line:$sqspell_symb";
                }
                break;
            /**
             * Line begins with "#".
             * This means a misspelled word and no suggestions.
             */
            case '#':
                $tmparray = explode(" ", $sqspell_output[$i]);
                $sqspell_word=$tmparray[1];
                /**
                 *
                 * Check if the word is in user dictionary.
                 */
                if (!in_array($sqspell_word,$this->userdic)){
                    $sqspell_symb=intval($tmparray[2])-1;
                    // no suggestions
                    $this->missed_words[$sqspell_word]['suggestions'] = array();
                    // add location
                    $this->missed_words[$sqspell_word]['locations'][] = "$current_line:$sqspell_symb";
                }
                break;
            }
        }
        return $this->missed_words;
    }
}


/**
 * Define the command used to spellcheck the document.
 */
#$sqspell_command=$SQSPELL_APP[$sqspell_use_app];
