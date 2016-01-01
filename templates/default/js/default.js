/**
 * This array is used to remember mark status of rows in browse mode
 *
 * @copyright 2005-2016 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id$
 */
var marked_row = new Array;
var orig_row_colors = new Array();

/*
 * (un)Checks checkbox for the row that the current table cell is in
 * when it gets clicked.
 *
 * @param string                  The (internal) name of the checkbox
 *                                that should be (un)checked
 * @param JavaScript event object The JavaScript event associated
 *                                with this mouse click
 * @param string                  The name of the encapsulating form
 * @param string                  The (real) name of the checkbox
 *                                that should be (un)checked
 * @param string                  Any extra JavaScript (that will
 *                                be executed herein if non-empty);
 *                                must be valid JavaScript expression(s)
 */
function row_click(chkboxName, event, formName, checkboxRealName, extra) {
    var chkbox = document.getElementById(chkboxName);
    if (chkbox) {
        // initialize orig_row_color if not defined already
        if (!orig_row_colors[chkboxName]) {
            orig_row_colors[chkboxName] = chkbox.parentNode.getAttribute('bgcolor');
            if (orig_row_colors[chkboxName].indexOf("clicked_") == 0)
                orig_row_colors[chkboxName] = orig_row_colors[chkboxName].substring(8, orig_row_colors[chkboxName].length);
        }
        chkbox.checked = (chkbox.checked ? false : true);

        if (extra != '') eval(extra);
    }
}

/*
 * Gets the current class of the requested row.  This is a browser specific function.
 * Code shamelessly ripped from setPointer() below.
 */
function getCSSClass (theRow)
{
    var rowClass;
	// 3.1 ... with DOM compatible browsers except Opera that does not return
	//         valid values with "getAttribute"
	if (typeof(window.opera) == 'undefined'
		&& typeof(theRow.getAttribute) != 'undefined'
		&& theRow.getAttribute('className') ) {
		rowClass = theRow.getAttribute('className');
	}
	// 3.2 ... with other browsers
	else {
		rowClass = theRow.className;
	}
	
	return rowClass;
}

/*
 * Sets a new CSS class for the given row.  Browser-specific.
 */
function setCSSClass (obj, newClass) {
	if (typeof(window.opera) == 'undefined' && typeof(obj.getAttribute) != 'undefined' && obj.getAttribute('className') ) {
		obj.setAttribute('className', newClass, 0);
	}
	else {
		obj.className = newClass;
	}
}

/*
 * This function is used to initialize the orig_row_color array so we do not
 * need to predefine the entire array
 */
function rowOver(chkboxName) {
    var chkbox = document.getElementById(chkboxName);
    var rowClass, rowNum, overClass, clickedClass;
    if (chkbox) {
        if (!orig_row_colors[chkboxName]) {
            rowClass = getCSSClass(chkbox.parentNode.parentNode);
            if (rowClass.indexOf("clicked_") == 0)
                rowClass = rowClass.substring(8, rowClass.length);
            orig_row_colors[chkboxName] = rowClass;
        } else {
            rowClass = orig_row_colors[chkboxName];
        }
        rowNum = chkboxName.substring(chkboxName.length - 1, chkboxName.length);

/*
 * The mouseover and clicked CSS classes are always the same name!
 */        
        overClass = 'mouse_over';
        clickedClass = 'clicked';
        setPointer(chkbox.parentNode.parentNode, rowNum, 'over' , rowClass, overClass, clickedClass);
    }
}

/*
 * (un)Checks all checkboxes for the message list from a specific form
 * when it gets clicked.
 *
 * @param   string   the id of the form where all checkboxes should be (un)checked
 * @param   string   the first three characters of target checkboxes, if any
 * @param   boolean  use fancy row coloring when a checkbox is checked
 * @param   string   new color of the checked rows
 */
function toggle_all(formname, name_prefix, fancy) {
    var TargetForm = document.getElementById(formname);
    var j = 0;
    for (var i = 0; i < TargetForm.elements.length; i++) {
        if (TargetForm.elements[i].type == 'checkbox' && (name_prefix == '' || TargetForm.elements[i].name.substring(0,3) == name_prefix)) {
            if (fancy) {
                array_key = TargetForm.elements[i].getAttribute('id');
                // initialize orig_row_color if not defined already
                if (!orig_row_colors[array_key]) {
                    rowClass = getCSSClass(TargetForm.elements[i].parentNode.parentNode);
                    if (rowClass.indexOf("clicked_") == 0)
                        rowClass = rowClass.substring(8, rowClass.length);
                    orig_row_colors[array_key] = rowClass;
                }
                origClass = orig_row_colors[array_key];
                clickedClass = 'clicked';
                setPointer(TargetForm.elements[i].parentNode.parentNode, j,'click' , origClass, origClass, clickedClass);
                j++
            }
            TargetForm.elements[i].checked = !(TargetForm.elements[i].checked);
        }
    }
}

/*
 * Sets/unsets the pointer and marker in browse mode
 *
 * @param object  theRow         the table row
 * @param integer theRowNum      the row number
 * @param string  theAction      the action calling this script (over, out or click)
 * @param string  defaultClass   the default background CSS class
 * @param string  mouseoverClass the CSS class to use for mouseover
 * @param string  clickedClass   the CSS class to use for marking a row
 *
 * @return  boolean  whether pointer is set or not
 */
function setPointer(theRow, theRowNum, theAction, defaultClass, mouseoverClass, clickedClass)
{
    // 1. Pointer and mark feature are disabled or the browser can't get the
    //    row -> exits
    if ((mouseoverClass == '' && clickedClass == '')
        || typeof(theRow.className) == 'undefined') {
        return false;
    }

    // 2. Verify we can get the current row or exit
    if (typeof(document.getElementsByTagName) != 'undefined') {
		// We are ok
    }
    else if (typeof(theRow) != 'undefined') {
    	// We are ok
    }
    else {
        return false;
    }

    // 3. Gets the current CSS class...
    var newClass     = null;
    var currentClass = getCSSClass(theRow);
    if (currentClass.indexOf("clicked_") == 0)
        currentClass = 'clicked';
    
    // 4. Defines the new class
    // 4.1 Current class is the default one
    if (currentClass == ''
        || currentClass.toLowerCase() == defaultClass.toLowerCase()) {
        if (theAction == 'over' && mouseoverClass != '') {
            newClass = mouseoverClass;
        }
        else if (theAction == 'click' && clickedClass != '') {
            newClass = clickedClass;
            marked_row[theRowNum] = true;
            // deactivated onclick marking of the checkbox because it's also executed
            // when an action (clicking on the checkbox itself) on a single item is
            // performed. Then the checkbox would get deactived, even though we need
            // it activated. Maybe there is a way to detect if the row was clicked,
            // and not an item therein...
            //document.getElementById('msg[' + theRowNum + ']').checked = true;
        }
    }
    // 4.1.2 Current class is the mouseover one
    else if (currentClass.toLowerCase() == mouseoverClass.toLowerCase()
             && (typeof(marked_row[theRowNum]) == 'undefined' || !marked_row[theRowNum])) {
        if (theAction == 'out') {
            newClass = defaultClass;
        }
        else if (theAction == 'click' && clickedClass != '') {
            newClass = clickedClass;
            marked_row[theRowNum] = true;
            //document.getElementById('msg[' + theRowNum + ']').checked = true;
        }
    }
    // 4.1.3 Current color is the clicked one
    else if (currentClass.toLowerCase() == clickedClass.toLowerCase()) {
        if (theAction == 'click') {
            newClass              = (mouseoverClass != '')
                                  ? mouseoverClass
                                  : defaultClass;
            marked_row[theRowNum] = false;
            //document.getElementById('msg[' + theRowNum + ']').checked = false;
        }
    } // end 4

    // 5. Sets the new color...
    if (newClass) {
    	setCSSClass(theRow, newClass);
    }

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
    var sArg = "width=" + iWidth + ",height=" + iHeight + ",scrollbars=yes,resizable=yes,status=yes";
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
    var mdnuri=window.location+'&sendreceipt=1';
    window.location = mdnuri; 
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
                if ( document.forms[i].elements[j].type == 'text' || document.forms[i].elements[j].type == 'password' || document.forms[i].elements[j].type == 'textarea' ) {
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

function printThis()
{
    parent.frames['right'].focus();
    parent.frames['right'].print();
}

/* JS implementation of more/less links in To/CC. Could later be extended
 * show/hide other interface items */
function showhide (item, txtmore, txtless) {
    var oTemp=document.getElementById("recpt_tail_" + item);
    var oClick=document.getElementById("toggle_" + item);
    if (oTemp.style.display=="inline") {
        oTemp.style.display="none";
        oClick.innerHTML=txtmore;
    } else {
        oTemp.style.display="inline";
        oClick.innerHTML=txtless;
    }
}
