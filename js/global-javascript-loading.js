var js_change = 0;
var editor = CodeMirror.fromTextArea(document.getElementById("global_js_js"), {
	mode: "text/javascript", lineNumbers: true, indentUnit:2,
	onChange: function(){
		js_change++;
		if(js_change > 1)
		{
		 jQuery("#unsaved_changes").show();
		
		}
	
	}
	});
var lastPos = null, lastQuery = null, marked = [];	
function unmark() {
  for (var i = 0; i < marked.length; ++i) marked[i]();
  marked.length = 0;
}

function search() {
  unmark();                     
  var text = document.getElementById("query").value;
  if (!text) return;
  for (var cursor = editor.getSearchCursor(text); cursor.findNext();)
	marked.push(editor.markText(cursor.from(), cursor.to(), "searched"));

  if (lastQuery != text) lastPos = null;
  var cursor = editor.getSearchCursor(text, lastPos || editor.getCursor());
  if (!cursor.findNext()) {
	cursor = editor.getSearchCursor(text);
	if (!cursor.findNext()) return;
  }
  editor.setSelection(cursor.from(), cursor.to());
  lastQuery = text; lastPos = cursor.to();
}

function replace1() {
  unmark();
  var text = document.getElementById("query").value,
	  replace = document.getElementById("replace").value;
  if (!text) return;
  for (var cursor = editor.getSearchCursor(text); cursor.findNext();)
	editor.replaceRange(replace, cursor.from(), cursor.to());
}