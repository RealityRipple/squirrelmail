/**
   CHECK_ME.JS
   ------------
   This JavaScript app is the driving power of the SquirrelSpell's
   main spellchecker window. Hope you have as much pain figuring
   it out as it took to write. ;))
   								**/

var CurrentError=0;
var CurrentLocation=0;

var CurrentLine;
var CurrentSymbol;
var ChangesMade=false;

function populateSqspellForm(){
  // this function loads error data into the form.
  CurrentWord=Word=misses[CurrentError];
  WordLocations = locations[CurrentError].split(", ");
  CurrentLoc = WordLocations[CurrentLocation];
  if(CurrentLocation==WordLocations.length-1) {
    CurrentLocation=0;
  } else {
    CurrentLocation++;
  }
       
  tmp = CurrentLoc.split(":");
  CurrentLine=parseInt(tmp[0]);
  CurrentSymbol=parseInt(tmp[1]);
  document.forms[0].sqspell_error.value=Word;
  LineValue=sqspell_lines[CurrentLine];
  StartWith=0;
  NewLineValue="";
  if (CurrentSymbol > 40){
    StartWith=CurrentSymbol-40;
    NewLineValue = "...";
  }
  EndWith=LineValue.length;
  EndLine="";
  if (EndWith > CurrentSymbol + 40){
    EndWith=CurrentSymbol+40;
    EndLine="...";
  }
  NewLineValue+=LineValue.substring(StartWith, CurrentSymbol) + "*" + Word + "*" + LineValue.substring(CurrentSymbol + Word.length, EndWith) + EndLine;
  document.forms[0].sqspell_line_area.value=NewLineValue;
       
  if (suggestions[CurrentError]){
    WordSuggestions = suggestions[CurrentError].split(", ");
    for (i=0; i<WordSuggestions.length; i++){
      document.forms[0].sqspell_suggestion.options[i] = new Option(WordSuggestions[i], WordSuggestions[i]);
    }
  } else {
    document.forms[0].sqspell_suggestion.options[0] = new Option("No Suggestions", "_NONE");
    document.forms[0].sqspell_oruse.value=Word;
    document.forms[0].sqspell_oruse.focus();
    document.forms[0].sqspell_oruse.select();
  }
       
  document.forms[0].sqspell_suggestion.selectedIndex=0;
  if (!document.forms[0].sqspell_oruse.value)
    document.forms[0].sqspell_oruse.value=document.forms[0].sqspell_suggestion.options[document.forms[0].sqspell_suggestion.selectedIndex].value;
  occursTimes = WordLocations.length;
  if (CurrentLocation) occursTimes += CurrentLocation-1;
  document.forms[0].sqspell_likethis.value=occursTimes;
}

function updateLine(lLine, lSymbol, lWord, lNewWord){
  // This function updates the line with new word value
  sqspell_lines[lLine] = sqspell_lines[lLine].substring(0, lSymbol) + lNewWord + sqspell_lines[lLine].substring(lSymbol+lWord.length, sqspell_lines[lLine].length);
  if (lWord.length != lNewWord.length)
    updateSymbol(lLine, lSymbol, lNewWord.length-lWord.length);
  if (!ChangesMade) ChangesMade=true;
}
     
function sqspellRemember(){
  // This function adds the word to the field in the form to be later
  // submitted and added to the user dictionary.
  CurrentWord = misses[CurrentError] + "%";
  document.forms[0].words.value += CurrentWord;
  sqspellIgnoreAll();
}

     
function sqspellChange(){
  // Called when pressed the "Change" button
  CurrentWord = misses[CurrentError];
  NewWord=document.forms[0].sqspell_oruse.value;
  updateLine(CurrentLine, CurrentSymbol, CurrentWord, NewWord);
  proceed();
}

function sqspellChangeAll(){
  // Called when pressed the "Change All" button
  allLoc = locations[CurrentError].split(", ");
  if (allLoc.length==1) {
    // There's no need to "change all", only one occurance.
    sqspellChange();
    return;
  }
       
  NewWord=document.forms[0].sqspell_oruse.value;
  CurrentWord = misses[CurrentError];
  for (z=CurrentLocation-1; z<allLoc.length; z++){
    tmp = allLoc[z].split(":");
    lLine = parseInt(tmp[0]);  lSymbol = parseInt(tmp[1]);
    updateLine(lLine, lSymbol, CurrentWord, NewWord);
    // Load it again to reflect the changes in symbol data
    allLoc = locations[CurrentError].split(", ");
  }
       
  CurrentLocation=0;
  proceed();
}

function sqspellIgnore(){
  // Only here for consistency. Called when pressed the "Ignore" button
  proceed();
}

function sqspellIgnoreAll(){
  // Called when pressed the "Ignore All" button
  CurrentLocation=0;
  proceed();
}

function clearSqspellForm(){
  // Clears the options in selectbox "sqspell_suggestions"
  for (i=0; i<document.forms[0].sqspell_suggestion.length; i++){
    document.forms[0].sqspell_suggestion.options[i]=null;
  }
       
  // Now, I've been instructed by the Netscape Developer docs to call
  // history.go(0) to refresh the page after I've changed the options.
  // However, that brings so many pains with it that I just decided not
  // to do it. It works like it is in Netscape 4.x. If there are problems
  // in earlier versions of Netscape, then oh well. I'm not THAT anxious
  // to have it working on all browsers... ;)

  document.forms[0].sqspell_oruse.value="";
}

function proceed(){
  // Goes on to the next error if any, or finishes.
  if (!CurrentLocation) CurrentError++;
  if (misses[CurrentError]){
    clearSqspellForm();
    populateSqspellForm();
  } else {
    if (ChangesMade || document.forms[0].words.value){
      if (confirm("SpellCheck complete. Commit Changes?"))
	sqspellCommitChanges();
	else self.close();
    } else {
      confirm ("No changes were made.");
      self.close();
    }
  }
}

function updateSymbol(lLine, lSymbol, difference){
  // Now, I will admit that this is not the best way to do stuff,
  // However that's the solution I've come up with.
  // This function updates the symbol locations after there have been
  // word length changes in the lines. Otherwise SquirrelSpell barfs all
  // over your message... ;)
  //
  // If you are wondering why I didn't use two-dimensional arrays instead,
  // well, sometimes there will be a long line with an error close to the
  // end of it, so the coordinates would be something like 2,98 and 
  // some Javascript implementations will create 98 empty members of an 
  // array just to have a filled number 98. This is too resource-wasteful 
  // and I have decided to go with the below solution instead. It takes 
  // a little more processing, but it saves a lot on memory.
       
  for (i=0; i<misses.length; i++){
    if(locations[i].indexOf(lLine + ":") >= 0){
      allLoc = locations[i].split(", ");
      for (j=0; j<allLoc.length; j++){
	if (allLoc[j].indexOf(lLine+":")==0){
	  tmp = allLoc[j].split(":");
	  tmp[0] = parseInt(tmp[0]); tmp[1] = parseInt(tmp[1]);
	  if (tmp[1] > lSymbol){
	    tmp[1] = tmp[1] + difference;
	    allLoc[j] = tmp.join(":");
	  }
	}
      }
      locations[i] = allLoc.join(", ");
    }
  }
}

function sqspellCommitChanges(){
  // Write the changes back into the compose form
  if (navigator.appName.indexOf("Microsoft")==0){
    // MSIE doesn't have array.shift()
    newSubject = sqspell_lines[0];
    newBody = "";
    for (i=1; i<sqspell_lines.length; i++){
      if (i!=1) newBody+="\r\n";
      newBody += sqspell_lines[i];
    }
  } else {
    newSubject = sqspell_lines.shift();
    newBody = sqspell_lines.join("\n");
  }

  opener.document.forms[0].subject.value=newSubject;
  opener.document.forms[0].body.value=newBody;
       
  // See if any words were added to the dictionary.
  if (document.forms[0].words.value){
    // yeppers
    document.forms[0].sqspell_line_area.value="Now saving your personal dictionary... Please wait.";
    // pass focus to the parent so we can do background save.
    window.opener.focus();
    document.forms[0].submit();
  } else {
     self.close();
  }
}
