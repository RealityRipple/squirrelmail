/**
  * Library functions for resizing widgets
  *
  */



// event handler temporary storage variables for IE 4
//
var old_mousemove_handler;
var old_mouseup_handler;



// define variables used in vertical resize handlers
//
var element_being_vert_resized;
var element_being_vert_resized_orig_opacity;
var min_vert_resize_height;
var max_vert_resize_height;
var original_vert_click_pos;
var original_vert_height;



/**
  * Begin a (vertical only) resize of some element
  *
  * Note that this event handler is only intended
  * to be called for an event (usually mousedown)
  * that is registered as a tag attribute (not with
  * addEventListener() and ilk).
  *
  * @param evt            The JavaScript event object
  * @param resize_element The element being resized
  * @param min_height     The minimum allowable resize height
  * @param max_height     The maximum allowable resize height
  * @param handle_element The handle element that was clicked 
  *                       upon to initiate the resize
  *
  */
function startVertResizeDrag(evt, resize_element, min_height, 
                             max_height, handle_element)
{

    // assign variables used in other handlers
    //
    element_being_vert_resized = resize_element;
    min_vert_resize_height = min_height;
    max_vert_resize_height = max_height;


    // dim element to emphasize effect
    //
    element_being_vert_resized_orig_opacity = element_being_vert_resized.style.opacity;
    element_being_vert_resized.style.opacity = 0.25;


    // detemine original vertical click coordinates
    //
    original_vert_click_pos = evt.clientY;
    original_vert_height = element_being_vert_resized.offsetHeight;


    // add drag and finish drag listeners
    //
    if (document.addEventListener)    // DOM Level 2 Event Model
    {
        document.addEventListener('mousemove', continueVertResizeDrag, true);
        document.addEventListener('mouseup', finishVertResizeDrag, true);
    }
    else if (document.attachEvent)    // IE 5+ Event Model
    {
        document.attachEvent("onmousemove", continueVertResizeDrag);
        document.attachEvent("onmouseup", finishVertResizeDrag);
    }
    else    // IE 4 Event Model
    {
        old_mousemove_handler = document.onmousemove;
        old_mouseup_handler = document.onmouseup;
        document.onmousemove = continueVertResizeDrag;
        document.onmouseup = finishVertResizeDrag;
    }


    // indicate that the event has been handled
    //
    if (evt.stopPropagation) evt.stopPropagation();    // DOM Level 2
    else evt.cancelBubble = true;    // IE


    // don't let any default action happen
    //
    if (evt.preventDefault) evt.preventDefault();    // DOM Level 2
    else evt.returnValue = false;    // IE


    // to top it all off, return false too 
    //
    return false;

}



/**
  * Continue a (vertical only) resize of some element
  *
  * @param evt The JavaScript event object
  *
  */
function continueVertResizeDrag(evt)
{

    // IE blows
    //
    if (!evt) evt = window.event;
    if (!evt) return true;


    // adjust height of resize item according to current mouse position
    //
    delta_resize = original_vert_click_pos - evt.clientY;
    new_height = original_vert_height - delta_resize;
    if (new_height >= min_vert_resize_height && new_height <= max_vert_resize_height)
        element_being_vert_resized.style.height = new_height + 'px';
    

    // indicate that the event has been handled
    //
    if (evt.stopPropagation) evt.stopPropagation();    // DOM Level 2
    else evt.cancelBubble = true;    // IE


    // to top it all off, return false too 
    //
    return false;

}



/**
  * Finish a (vertical only) resize of some element
  *
  * @param evt The JavaScript event object
  *
  */
function finishVertResizeDrag(evt)
{

    // IE blows
    //
    if (!evt) evt = window.event;
    if (!evt) return true;


    // restore element's original opacity
    //
    element_being_vert_resized.style.opacity = element_being_vert_resized_orig_opacity;


    // unregister all event listeners
    //
    if (document.removeEventListener)    // DOM Event Model
    {
        document.removeEventListener("mousemove", continueVertResizeDrag, true);
        document.removeEventListener("mouseup", finishVertResizeDrag, true);
    }
    else if (document.detachEvent)    // IE 5+ Event Model
    {
        document.detachEvent("onmousemove", continueVertResizeDrag);
        document.detachEvent("onmouseup", finishVertResizeDrag);
    }
    else    // IE 4 Event Model
    {
        document.onmousemove = old_mousemove_handler;
        document.onmouseup = old_mouseup_handler;
    }


    // indicate that the event has been handled
    //
    if (evt.stopPropagation) evt.stopPropagation();    // DOM Level 2
    else evt.cancelBubble = true;    // IE


    // to top it all off, return false too 
    //
    return false;

}



