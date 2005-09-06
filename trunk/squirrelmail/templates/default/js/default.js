/**
 * This array is used to remember mark status of rows in browse mode
 */
var marked_row = new Array;
var orig_row_colors = new Array();


/*
 * (un)Checks checkbox for the row that the current table cell is in
 * when it gets clicked.
 *
 * @param   string   the name of the checkbox that should be (un)checked
 */
function row_click(chkboxName) {
    chkbox = document.getElementById(chkboxName);
    if (chkbox) {
        // initialize orig_row_color if not defined already
        if (!orig_row_colors[chkboxName]) {
            orig_row_colors[chkboxName] = chkbox.parentNode.getAttribute('bgcolor');
        }
        chkbox.checked = (chkbox.checked ? false : true);
    }
}
/*
 * This function is used to initialize the orig_row_color array so we do not
 * need to predefine the entire array
 */
function rowOver(chkboxName, overColor, clickedColor) {
    chkbox = document.getElementById(chkboxName);
    if (chkbox) {
        if (!orig_row_colors[chkboxName]) {
            bgColor = chkbox.parentNode.getAttribute('bgcolor');
            orig_row_colors[chkboxName] = bgColor;
        } else {
            bgColor = orig_row_colors[chkboxName];
        }
        j = chkbox.name.length - 1
        setPointer(chkbox.parentNode.parentNode, j,'over' , bgColor, overColor, clickedColor);
    }
}

/*
 * (un)Checks all checkboxes for the message list from a specific form
 * when it gets clicked.
 *
 * @param   string   the id of the form where all checkboxes should be (un)checked
 * @param   boolean  use fancy row coloring when a checkbox is checked
 * @param   string   new color of the checked rows
 */
function toggle_all(formname, fancy, clickedColor) {
     TargetForm = document.getElementById(formname);
     j = 0;
     for (var i = 0; i < TargetForm.elements.length; i++) {
         if (TargetForm.elements[i].type == 'checkbox' && TargetForm.elements[i].name.substring(0,3) == 'msg') {
             if (fancy) {
                array_key = TargetForm.elements[i].getAttribute('id');
                if (TargetForm.elements[i].checked == false) {
                    // initialize orig_row_color if not defined already
                    if (!orig_row_colors[array_key]) {
                        orig_row_colors[array_key] = TargetForm.elements[i].parentNode.getAttribute('bgcolor');
                    }
                }
                origColor = orig_row_colors[array_key];
                setPointer(TargetForm.elements[i].parentNode.parentNode, j,'click' , origColor, origColor, clickedColor);
                j++
            }
            TargetForm.elements[i].checked = !(TargetForm.elements[i].checked);
         }
     }
}

/*
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param   object    the table row
 * @param   integer  the row number
 * @param   string    the action calling this script (over, out or click)
 * @param   string    the default background color
 * @param   string    the color to use for mouseover
 * @param   string    the color to use for marking a row
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theRow, theRowNum, theAction, theDefaultColor, thePointerColor, theMarkColor)
{
    var theCells = null;

    // 1. Pointer and mark feature are disabled or the browser can't get the
    //    row -> exits
    if ((thePointerColor == '' && theMarkColor == '')
        || typeof(theRow.style) == 'undefined') {
        return false;
    }

    // 2. Gets the current row and exits if the browser can't get it
    if (typeof(document.getElementsByTagName) != 'undefined') {
        theCells = theRow.getElementsByTagName('td');
    }
    else if (typeof(theRow.cells) != 'undefined') {
        theCells = theRow.cells;
    }
    else {
        return false;
    }

    // 3. Gets the current color...
    var rowCellsCnt  = theCells.length;
    var domDetect    = null;
    var currentColor = null;
    var newColor     = null;
    // 3.1 ... with DOM compatible browsers except Opera that does not return
    //         valid values with "getAttribute"
    if (typeof(window.opera) == 'undefined'
        && typeof(theCells[0].getAttribute) != 'undefined') {
        currentColor = theCells[0].getAttribute('bgcolor');
        domDetect    = true;
    }
    // 3.2 ... with other browsers
    else {
        currentColor = theCells[0].style.backgroundColor;
        domDetect    = false;
    } // end 3

    // 3.3 ... Opera changes colors set via HTML to rgb(r,g,b) format so fix it
    if (currentColor.indexOf("rgb") >= 0)
    {
        var rgbStr = currentColor.slice(currentColor.indexOf('(') + 1,
                                     currentColor.indexOf(')'));
        var rgbValues = rgbStr.split(",");
        currentColor = "#";
        var hexChars = "0123456789ABCDEF";
        for (var i = 0; i < 3; i++)
        {
            var v = rgbValues[i].valueOf();
            currentColor += hexChars.charAt(v/16) + hexChars.charAt(v%16);
        }
    }

    // 4. Defines the new color
    // 4.1 Current color is the default one
    if (currentColor == ''
        || currentColor.toLowerCase() == theDefaultColor.toLowerCase()) {
        if (theAction == 'over' && thePointerColor != '') {
            newColor              = thePointerColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
            // deactivated onclick marking of the checkbox because it's also executed
            // when an action (clicking on the checkbox itself) on a single item is
            // performed. Then the checkbox would get deactived, even though we need
            // it activated. Maybe there is a way to detect if the row was clicked,
            // and not an item therein...
            //document.getElementById('msg[' + theRowNum + ']').checked = true;
        }
    }
    // 4.1.2 Current color is the pointer one
    else if (currentColor.toLowerCase() == thePointerColor.toLowerCase()
             && (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])) {
        if (theAction == 'out') {
            newColor              = theDefaultColor;
        }
        else if (theAction == 'click' && theMarkColor != '') {
            newColor              = theMarkColor;
            marked_row[theRowNum] = true;
            //document.getElementById('msg[' + theRowNum + ']').checked = true;
        }
    }
    // 4.1.3 Current color is the marker one
    else if (currentColor.toLowerCase() == theMarkColor.toLowerCase()) {
        if (theAction == 'click') {
            newColor              = (thePointerColor != '')
                                  ? thePointerColor
                                  : theDefaultColor;
            marked_row[theRowNum] = (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])
                                  ? true
                                  : null;
            //document.getElementById('msg[' + theRowNum + ']').checked = false;
        }
    } // end 4

    // 5. Sets the new color...
    if (newColor) {
        var c = null;
        // 5.1 ... with DOM compatible browsers except Opera
        if (domDetect) {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].setAttribute('bgcolor', newColor, 0);
            } // end for
        }
        // 5.2 ... with other browsers
        else {
            for (c = 0; c < rowCellsCnt; c++) {
                theCells[c].style.backgroundColor = newColor;
            }
        }
    } // end 5

    return true;
} // end of the 'setPointer()' function

function comp_in_new_form(comp_uri, button, myform, iWidth, iHeight) {
    comp_uri += "&" + button.name + "=1";
    for ( var i=0; i < myform.elements.length; i++ ) {
        if ( myform.elements[i].type == "checkbox"  && myform.elements[i].checked )
        comp_uri += "&" + myform.elements[i].name + "=1";
    }
    if (!iWidth) iWidth   =  640;
    if (!iHeight) iHeight =  550;
    sArg = "width=" + iWidth + ",height=" + iHeight + ",scrollbars=yes,resizable=yes,status=yes";
    var newwin = window.open(comp_uri, "_blank", sArg);
}

function comp_in_new(comp_uri, iWidth, iHeight) {
    if (!iWidth) iWidth   =  640;
    if (!iHeight) iHeight =  550;
    sArg = "width=" + iWidth + ",height=" + iHeight + ",scrollbars=yes,resizable=yes,status=yes";
    var newwin = window.open(comp_uri , "_blank", sArg);
}

/*
 * Reload the read_body screen on sending an mdn receipt
 */
function sendMDN() {
    mdnuri=window.location+'&sendreceipt=1';
    var newwin = window.open(mdnuri,'right');
}

var alreadyFocused = false;
function checkForm(smaction) {

    if (alreadyFocused) return;

    /*
     * this part is used for setting the focus in the compose screen
     */
    if (smaction) {
        if (smaction == "select") {
            document.forms['compose'].body.select();
        } else if (smaction == "focus") {
            document.forms['compose'].body.focus();
        }
    } else {
    /*
     * All other forms that need to set the focus
     */
        var f = document.forms.length;
        var i = 0;
        var pos = -1;
        while( pos == -1 && i < f ) {
            var e = document.forms[i].elements.length;
            var j = 0;
            while( pos == -1 && j < e ) {
                if ( document.forms[i].elements[j].type == 'text' || document.forms[i].elements[j].type == 'password' ) {
                    pos = j;
                }
                j++;
            }
        i++;
        }
        if( pos >= 0 ) {
            document.forms[i-1].elements[pos].focus();
        }
    }
}
