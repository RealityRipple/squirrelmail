<?php

/**
 * rfc822address.php
 *
 * Contains rfc822 email address function parsing functions.
 *
 * @copyright 2004-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 * @package squirrelmail
 */


/**
 * parseRFC822Address: function for parsing RFC822 email address strings and store
 *               them in an address array
 *
 * @param string  $address The email address string to parse
 * @param integer $iLimit stop on $iLimit parsed addresses
 * @public
 * @author Marc Groot Koerkamp
 *
 **/
function parseRFC822Address($sAddress,$iLimit = 0) {

    $aTokens = _getAddressTokens($sAddress);
    $sPersonal = $sEmail = $sComment = $sGroup = '';
    $aStack = $aComment = $aAddress = array();
    foreach ($aTokens as $sToken) {
        if ($iLimit && $iLimit == count($aAddress)) {
            return $aAddress;
        }
        $cChar = $sToken{0};
        switch ($cChar)
        {
        case '=':
        case '"':
        case ' ':
            $aStack[] = $sToken;
            break;
        case '(':
            $aComment[] = substr($sToken,1,-1);
            break;
        case ';':
            if ($sGroup) {
                $aAddress[] = _createAddressElement($aStack,$aComment,$sEmail);
                $aAddr = end($aAddress);
                if(!$aAddr || ((isset($aAddr)) && !$aAddr[SQM_ADDR_MAILBOX] && !$aAddr[SQM_ADDR_PERSONAL])) {
                    $sEmail = $sGroup . ':;';
                }
                $aAddress[] = _createAddressElement($aStack,$aComment,$sEmail);
                $sGroup = '';
                $aStack = $aComment = array();
                break;
            }
        case ',':
            $aAddress[] = _createAddressElement($aStack,$aComment,$sEmail);
            break;
        case ':':
            $sGroup = trim(implode(' ',$aStack));
            $sGroup = preg_replace('/\s+/',' ',$sGroup);
            $aStack = array();
            break;
        case '<':
            $sEmail = trim(substr($sToken,1,-1));
            break;
        case '>':
            /* skip */
            break;
        default: $aStack[] = $sToken; break;
        }
    }
    /* now do the action again for the last address */
    $aAddress[] = _createAddressElement($aStack,$aComment,$sEmail);
    return $aAddress;
}


/**
 * Do the address array to string translation
 *
 * @param array $aAddressList list with email address arrays
 * @param array  $aProps  associative array with properties
 * @return string
 * @public
 * @see parseRFC822Address
 * @author Marc Groot Koerkamp
 *
 **/
function getAddressString($aAddressList,$aProps) {
    $aPropsDefault = array (
                            'separator' => ', ',     // address separator
                            'limit'  => 0,          // limits returned addresses
                            'personal' => true,     // show persnal part
                            'email'    => true,     // show email part
                            'best'     => false,    // show personal if available
                            'encode'   => false,    // encode the personal part
                            'unique'   => false,    // make email addresses unique.
                            'exclude'  => array()   // array with exclude addresses
                                                    // format of address: mailbox@host
                            );

    $aProps = is_array($aProps) ? array_merge($aPropsDefault,$aProps) : $aPropsDefault;

    $aNewAddressList = array();
    $aEmailUnique = array();
    foreach ($aAddressList as $aAddr) {
        if ($aProps['limit'] && count($aNewAddressList) == $aProps['limit']) {
            break;
        }
        $sPersonal = (isset($aAddr[SQM_ADDR_PERSONAL])) ? $aAddr[SQM_ADDR_PERSONAL] : '';
        $sMailbox  = (isset($aAddr[SQM_ADDR_MAILBOX]))  ? $aAddr[SQM_ADDR_MAILBOX]  : '';
        $sHost     = (isset($aAddr[SQM_ADDR_HOST]))     ? $aAddr[SQM_ADDR_HOST]     : '';

        $sEmail    = ($sHost) ? "$sMailbox@$sHost": $sMailbox;

        if (in_array($sEmail,$aProps['exclude'],true)) {
            continue;
        }

        if ($aProps['unique']) {
            if  (in_array($sEmail,$aEmailUnique,true)) {
                continue;
            } else {
                $aEmailUnique[] = $sEmail;
            }
        }

        $s = '';
        if ($aProps['best']) {
            $s .= ($sPersonal) ? $sPersonal : $sEmail;
        } else {
            if ($aProps['personal'] && $sPersonal) {
                if ($aProps['encode']) {
                    $sPersonal = encodeHeader($sPersonal);
                }
                $s .= $sPersonal;
            }
            if ($aProps['email'] && $sEmail) {
               $s.= ($s) ? ' <'.$sEmail.'>': '<'.$sEmail.'>';
            }
        }
        if ($s) {
            $aNewAddressList[] = $s;
        }
    }
    return implode($aProps['separator'],$aNewAddressList);
}


/**
 * Do after address parsing handling. This is used by compose.php and should
 * be moved to compose.php.
 * The AddressStructure objetc is now obsolete and dependent parts of that will
 * be adapted so that it can make use of this function
 * After that we can remove the parseAddress method from the Rfc822Header class completely
 * so we achieved 1 single instance of parseAddress instead of two like we have now.
 *
 * @param array $aAddressList list with email address arrays
 * @param array  $aProps  associative array with properties
 * @return string
 * @public
 * @see parseRFC822Address
 * @see Rfc822Header
 * @author Marc Groot Koerkamp
 *
 **/
function processAddressArray($aAddresses,$aProps) {
    $aPropsDefault = array (
                            'domain' => '',
                            'limit'  => 0,
                            'abooklookup' => false);

    $aProps = is_array($aProps) ? array_merge($aPropsDefault,$aProps) : $aPropsDefault;
    $aProcessedAddress = array();

    foreach ($aAddresses as $aEntry) {
        /*
         * if the emailaddress does not contain the domainpart it can concern
         * an alias or local (in the same domain as the user is) email
         * address. In that case we try to look it up in the addressbook or add
         * the local domain part
         */
        if (!$aEntry[SQM_ADDR_HOST]) {
            if ($cbLookup) {
                $aAddr = call_user_func_array($cbLookup,array($aEntry[SQM_ADDR_MAILBOX]));
                if (isset($aAddr['email'])) {
                    /*
                     * if the returned email address concerns multiple email
                     * addresses we have to process those as well
                     */
                    if (strpos($aAddr['email'],',')) { /* multiple addresses */
                        /* add the parsed addresses to the processed address array */
                        $aProcessedAddress = array_merge($aProcessedAddress,parseAddress($aAddr['email']));
                        /* skip to next address, all processing is done */
                        continue;
                    } else { /* single address */
                        $iPosAt = strpos($aAddr['email'], '@');
                        $aEntry[SQM_ADDR_MAILBOX] = substr($aAddr['email'], 0, $iPosAt);
                        $aEntry[SQM_ADDR_HOST] = substr($aAddr['email'], $iPosAt+1);
                        if (isset($aAddr['name'])) {
                            $aEntry[SQM_ADDR_PERSONAL] = $aAddr['name'];
                        } else {
                            $aEntry[SQM_ADDR_PERSONAL] = encodeHeader($sPersonal);
                        }
                    }
                }
            }
            /*
             * append the domain
             *
             */
            if (!$aEntry[SQM_ADDR_MAILBOX]) {
                $aEntry[SQM_ADDR_MAILBOX] = trim($sEmail);
            }
            if ($sDomain && !$aEntry[SQM_ADDR_HOST]) {
                $aEntry[SQM_ADDR_HOST] = $sDomain;
            }
        }
        if ($aEntry[SQM_ADDR_MAILBOX]) {
            $aProcessedAddress[] = $aEntry;
        }
    }
    return $aProcessedAddress;
}

/**
 * Internal function for creating an address array
 *
 * @param array $aStack
 * @param array $aComment
 * @param string $sEmail
 * @return array $aAddr array with personal (0), adl(1), mailbox(2) and host(3) info
 * @private
 * @author Marc Groot Koerkamp
 *
 **/

function _createAddressElement(&$aStack,&$aComment,&$sEmail) {
    if (!$sEmail) {
        while (count($aStack) && !$sEmail) {
            $sEmail = trim(array_pop($aStack));
        }
    }
    if (count($aStack)) {
        $sPersonal = trim(implode('',$aStack));
    } else {
        $sPersonal = '';
    }
    if (!$sPersonal && count($aComment)) {
        $sComment = trim(implode(' ',$aComment));
        $sPersonal .= $sComment;
    }
    $aAddr = array();
//        if ($sPersonal && substr($sPersonal,0,2) == '=?') {
//            $aAddr[SQM_ADDR_PERSONAL] = encodeHeader($sPersonal);
//        } else {
        $aAddr[SQM_ADDR_PERSONAL] = $sPersonal;
//        }

    $iPosAt = strpos($sEmail,'@');
    if ($iPosAt) {
        $aAddr[SQM_ADDR_MAILBOX] = substr($sEmail, 0, $iPosAt);
        $aAddr[SQM_ADDR_HOST] = substr($sEmail, $iPosAt+1);
    } else {
        $aAddr[SQM_ADDR_MAILBOX] = $sEmail;
        $aAddr[SQM_ADDR_HOST] = false;
    }
    $sEmail = '';
    $aStack = $aComment = array();
    return $aAddr;
}

/**
 * Tokenizer function for parsing the RFC822 email address string
 *
 * @param string $address The email address string to parse
 * @return array $aTokens
 * @private
 * @author Marc Groot Koerkamp
 *
 **/

function _getAddressTokens($address) {
    $aTokens = array();
    $aSpecials = array('(' ,'<' ,',' ,';' ,':');
    $aReplace =  array(' (',' <',' ,',' ;',' :');
    $address = str_replace($aSpecials,$aReplace,$address);
    $iCnt = strlen($address);
    $i = 0;
    while ($i < $iCnt) {
        $cChar = $address{$i};
        switch($cChar)
        {
        case '<':
            $iEnd = strpos($address,'>',$i+1);
            if (!$iEnd) {
                $sToken = substr($address,$i);
                $i = $iCnt;
            } else {
                $sToken = substr($address,$i,$iEnd - $i +1);
                $i = $iEnd;
            }
            $sToken = str_replace($aReplace, $aSpecials,$sToken);
            if ($sToken) $aTokens[] = $sToken;
            break;
        case '"':
            $iEnd = strpos($address,$cChar,$i+1);
            if ($iEnd) {
                // skip escaped quotes
                $prev_char = $address{$iEnd-1};
                while ($prev_char === '\\' && substr($address,$iEnd-2,2) !== '\\\\') {
                    $iEnd = strpos($address,$cChar,$iEnd+1);
                    if ($iEnd) {
                        $prev_char = $address{$iEnd-1};
                    } else {
                        $prev_char = false;
                    }
                }
            }
            if (!$iEnd) {
                $sToken = substr($address,$i);
                $i = $iCnt;
            } else {
                // also remove the surrounding quotes
                $sToken = substr($address,$i+1,$iEnd - $i -1);
                $i = $iEnd;
            }
            $sToken = str_replace($aReplace, $aSpecials,$sToken);
            if ($sToken) $aTokens[] = $sToken;
            break;
        case '(':
            array_pop($aTokens); //remove inserted space
            $iEnd = strpos($address,')',$i);
            if (!$iEnd) {
                $sToken = substr($address,$i);
                $i = $iCnt;
            } else {
                $iDepth = 1;
                $iComment = $i;
                while (($iDepth > 0) && (++$iComment < $iCnt)) {
                    $cCharComment = $address{$iComment};
                    switch($cCharComment) {
                        case '\\':
                            ++$iComment;
                            break;
                        case '(':
                            ++$iDepth;
                            break;
                        case ')':
                            --$iDepth;
                            break;
                        default:
                            break;
                    }
                }
                if ($iDepth == 0) {
                    $sToken = substr($address,$i,$iComment - $i +1);
                    $i = $iComment;
                } else {
                    $sToken = substr($address,$i,$iEnd - $i + 1);
                    $i = $iEnd;
                }
            }
            // check the next token in case comments appear in the middle of email addresses
            $prevToken = end($aTokens);
            if (!in_array($prevToken,$aSpecials,true)) {
                if ($i+1<strlen($address) && !in_array($address{$i+1},$aSpecials,true)) {
                    $iEnd = strpos($address,' ',$i+1);
                    if ($iEnd) {
                        $sNextToken = trim(substr($address,$i+1,$iEnd - $i -1));
                        $i = $iEnd-1;
                    } else {
                        $sNextToken = trim(substr($address,$i+1));
                        $i = $iCnt;
                    }
                    // remove the token
                    array_pop($aTokens);
                    // create token and add it again
                    $sNewToken = $prevToken . $sNextToken;
                    if($sNewToken) $aTokens[] = $sNewToken;
                }
            }
            $sToken = str_replace($aReplace, $aSpecials,$sToken);
            if ($sToken) $aTokens[] = $sToken;
            break;
        case ',':
        case ':':
        case ';':
        case ' ':
            $aTokens[] = $cChar;
            break;
        default:
            $iEnd = strpos($address,' ',$i+1);
            if ($iEnd) {
                $sToken = trim(substr($address,$i,$iEnd - $i));
                $i = $iEnd-1;
            } else {
                $sToken = trim(substr($address,$i));
                $i = $iCnt;
            }
            if ($sToken) $aTokens[] = $sToken;
        }
        ++$i;
    }
    return $aTokens;
}
