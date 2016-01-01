<?php
/**
 * PHP pspell spellcheck class functions
 * @copyright 2006-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package plugins
 * @subpackage squirrelspell
 */

/**
 * PHP Pspell class
 * @package plugins
 * @subpackage squirrelspell
 */
class php_pspell extends squirrelspell {
    //
    var $dict = 'en';
    var $subdict = '';
    var $jargon = '';
    var $charset = 'utf-8';
    var $mode = null;
    var $userdic = array();

    /**
     */
    var $missed_words = array();

    /**
     * Error buffer
     * @var string
     */
    var $error = '';
    /**
     */
    var $dictionary_link = null;

    /**
     * Constructor function
     * @param array $aParams
     */
    function php_pspell($aParams=array()) {
        if (! extension_loaded('pspell')) {
            return $this->set_error('Pspell extension is not available');
        }
        //
        if (isset($aParams['dictionary'])) {
            $aDict = explode(',',$aParams['dictionary']);
            if (isset($aDict[0])) $this->dict = trim($aDict[0]);
            if (isset($aDict[1])) $this->subdict = trim($aDict[1]);
            if (isset($aDict[2])) $this->jargon = trim($aDict[2]);
        }
        if (isset($aParams['charset'])) {
            $this->charset = $aParams['charset'];
        }
        if (isset($aParams['userdic'])) {
            $this->userdic = $aParams['userdic'];
        }
        if (isset($aParams['mode'])) {
            $this->mode = $aParams['mode'];
        } else {
            $this->mode = PSPELL_FAST;
        }
        // dict, subdict, jargon, charset, spellcheck_type
        $this->dictionary_link = pspell_new($this->dict,$this->subdict,$this->jargon,$this->charset,$this->mode);
    }

    // private functions
    function check_word($sWord) {
        return pspell_check($this->dictionary_link,$sWord);
    }

    function suggest($sWord) {
        return pspell_suggest($this->dictionary_link,$sWord);
    }

    // public function

    /**
     * Check block of text
     * @return array
     */
    function check_text($sText) {
        // resets missed words array
        $this->missed_words = array();

        $line = 0;
        $start = 0;
        $position = 0;
        $word = '';
        // parse text. sq_* functions are used in order to work with characters and not with bytes
        for ($i = 0; $i <= sq_strlen($sText,$this->charset); $i++) {
            if ($i == sq_strlen($sText,$this->charset)) {
                // add space in order to check last $word.
                $char = ' ';
            } else {
                $char = sq_substr($sText,$i,1,$this->charset);
            }
            // Current
            switch($char) {
            case ' ':
            case '.':
            case ';':
            case "\t":
            case "\r":
            case "\n":
                if (!empty($word)) {
                    if (isset($this->missed_words[$word]) || !$this->check_word($word)) {
                        if (! isset($this->missed_words[$word]['suggestions'])) {
                            $this->missed_words[$word]['suggestions'] = $this->suggest($word);
                        }
                        $this->missed_words[$word]['locations'][] = "$line:$start";
                    }
                    $word = '';
                }
                if ($char == "\n") {
                    $position = 0;
                    $line++;
                } else {
                    $position++;
                }
                break;
            default:
                // a-zA-Z0-9' + 8bit chars (nbspace and other spaces excluded, depends on charset)
                // add char to word
                if(empty($word)) {
                    $start = $position; // squirrelspell adds one space to checked text
                }
                $position++;
                $word.=$char;
            }
        }
        return $this->missed_words;
    }
}
