<?php
/*
Plugin Name: Global Javascript
Plugin URI: https://github.com/psmagicman/ctlt_wp_global_javascript
Description: Allows the creation and editing of Javascript on Wordpress powered sites
Version: 0.6
Author: Julien Law, CTLT
Author URI: https://github.com/psmagicman/ctlt_wp_global_javascript
Based on the Improved Simpler CSS plugin by CTLT which was forked from Jeremiah Orem's Custom CSS User plugin
and then Frederick Ding http://simplerplugins.wordpress.com
*/

/*  Copyright 2013  Julien Law

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
if($_GET['code'] != 'none'):
	add_action('admin_print_scripts-appearance_page_global-javascript/global-javascript', 'global_javascript_admin_print_scripts');
endif;

add_action( 'admin_print_scripts-appearance_page_global-javascript/global-javascript', 'global_javascript_admin_enqueue_scripts');
add_action( 'admin_print_styles-appearance_page_global-javascript/global-javascript', 'global_javascript_admin_print_styles');
add_action( 'admin_init', 'global_javascript_init' );
add_action( 'wp_before_admin_bar_render', 'global_javascript_admin_bar_render' );


// Override the edit link, the default link causes a redirect loop
add_filter('get_edit_post_link', 'global_javascript_revision_post_link');

/**
 * global_javascript_admin_print_styles function.
 * adds styles to the admin page
 * @access public
 * @return void
 */
function global_javascript_admin_print_styles() {
	wp_enqueue_style('global-js', plugins_url('/global-javascript/css/styles.css'));
}
/**
 * global_javascript_admin_enqueue_scripts function.
 * adds the pos
 * @access public
 * @return void
 */
function global_javascript_admin_enqueue_scripts() {
	// wp_enqueue_script( 'postbox' );
}
/**
 * global_javascript_admin_print_scripts function.
 * 
 * @access public
 * @return void
 */
function global_javascript_admin_print_scripts() {
	wp_enqueue_script('global-js', plugins_url('/global-javascript/js/codemirror.js'));
}



/**
 * global_javascript_admin_bar_render function.
 * Add the menu to the admin bar
 * @access public
 * @return void
 */
function global_javascript_admin_bar_render() {
	global $wp_admin_bar;
	// we can remove a menu item, like the COMMENTS link
    // just by knowing the right $id
	
	// we can add a submenu item too
	$wp_admin_bar->add_menu( array(
        'parent' => 'appearance',
        'id' => 'custom-js',
        'title' => __('Custom JS'),
        'href' => admin_url( 'themes.php?page=global-javascript/global-javascript.php')
    ) );
}




/**
 * global_javascript_revision_post_link function.
 * Override the edit link, the default link causes a redirect loop
 * @access public
 * @param mixed $post_link
 * @return void
 */
function global_javascript_revision_post_link($post_link)
{
	global $post;
	
	if ( isset( $post ) && ( 's-custom-js' == $post->post_type ) )
		if ( strstr( $post_link, 'action=edit' ) && !strstr( $post_link, 'revision=' ) )
			$post_link = 'themes.php?page=global-javascript/global-javascript.php';
	
	return $post_link;

}


/**
 * global_javascript_init function.
 * Init plugin options to white list our options
 * @access public
 * @return void
 */
function global_javascript_init(){
	/*
	register_setting( 'global_js_options', 'global_js_js');
	*/
	$args = array(
	    'public' => false,
	    'query_var' => true,
	    'capability_type' => 'nav_menu_item',
	    'supports' 		=> array('revisions')
  	); 
 	register_post_type( 's-custom-js', array(
		'supports' => array( 'revisions' )
	) );
	
}

/**
 * global_javascript_save_revision function.
 * safe the revisoin 
 * @access public
 * @param mixed $css
 * @return void
 */
function global_javascript_save_revision( $js ) {

	// If null, there was no original safecss record, so create one
	if ( !$safejs_post = global_javascript_get_js() ) {
		$post = array();
		$post['post_content'] = $js;
		$post['post_title']   = 'Custom JS';
		$post['post_status']  = 'publish';
		$post['post_type']    = 's-custom-js';
		
		// check if there are any settings data 
		$global_js_js = get_option ( 'global_js_js' );
		if($global_js_js): // option settings exist 
			if(!is_array($global_js_js))
				$global_js_js = array($global_js_js);
			
			array_reverse($global_js_js);
			$count = 0;
			foreach($global_js_js  as $js_from_option):
				$post['post_content'] = $js_from_option;
				if($count == 0):
					$post_id = wp_insert_post( $post );
				else:	
  					$post['ID'] = $post_id;
  					wp_update_post( $post );
				endif;
				
				
				$count++; // increment the count 
			endforeach;
			
			// ok lets update the post for real this time
			$post['post_content'] = $js; // really update the stuff
			wp_update_post( $post );
			
			// time to delete the options that exits
			delete_option('global_js_js');
			
		else: // there is no settins data lets save this stuff
			$post_id = wp_insert_post( $post );
		endif;
		
		return true;
	} // there is a styles store in the custom post type
	
	$safejs_post['post_content'] = $js;
	
	wp_update_post( $safejs_post );
	return true;
}
/**
 * global_javascript_get_js function.
 * Get the custom js from posts table 
 * @access public
 * @return void
 */
function global_javascript_get_js()
{
	if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-custom-js', 'post_status' => 'publish' ) ) ) )
		$safejs_post = get_object_vars( $a );
	else
		$safejs_post = false;

	return $safejs_post;
}
/**
 * global_javascript_always_get_js function.
 * return the 
 * @access public
 * @return void
 */
function global_javascript_always_get_js()
{
	
	if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-custom-js', 'post_status' => 'publish' ) ) ) ):
		$safejs_post = get_object_vars( $a );
		return $safejs_post['post_content'];
	// if there is no 
	else:
	
		$global_js_js = get_option ( 'global_js_js' );
		if(!empty($global_js_js)):
			if(!is_array($global_js_js))
				$global_js_js = array($global_js_js);
		
			return $global_js_js[0];
		else:
			// return an empty string 
			return false;
		endif;
	endif; 

}



function global_javascript_addjs() {
	$global_js_js = get_option ( 'global_js_js' );
	if(!is_array($global_js_js))
		$global_js_js = array($global_js_js);
		
	echo '<script type="text/javascript">' . "\n";
	echo global_javascript_filter ( global_javascript_always_get_js() ) . "\n";
	echo '</script>' . "\n";
}

function global_javascript_filter($_content) {
	$_return = preg_replace ( '/@import.+;( |)|((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/))/i', '', $_content );
	$_return = htmlspecialchars ( strip_tags($_return), ENT_NOQUOTES, 'UTF-8' );
	return $_return;
}

function global_javascript_menu() {
	add_theme_page ( 'Custom JS', 'Custom JS', 8, __FILE__, 'global_javascript_options' );
}

function global_javascript_options() {
	
$global_js_default = '/* Welcome to Global Javascript!

If you are familiar with Javascript, you may delete these comments and get started. Javascript allows websites to have dynamic content. Here\'s an example:

alert("Hello");

That line will display a popup message box that says "Hello" without the quotes.

Javascript is not very hard to learn. There are many free references to help you get started, like http://www.w3schools.com/js/default.asp

We hope you enjoy developing your custom JS. Here are a few things to keep in mind:
 - You cannot edit the Javascript of your themes and other plugins. The Javascript you create will be loaded after all the other Javascript is loaded.
 - Anything inside Javascript comments will not be outputted */

/* This 
   is 
   a 
   comment
   block.
*/
	
// This is a single line comment

/*
Things we strip out include:
 * HTML code
 * comments (upon output)

Things we encourage include:
 * testing in several browsers!
 * trying things out!

(adapted from WordPress.com)
*/';


	$updated = false;
	$opt_name = 'global_js_js';

	
	$js_val = global_javascript_always_get_js();
	if (!$js_val)
		$js_val = array($global_js_default);
	elseif(!is_array($js_val))
		$js_val = array($js_val);
	
	// the form has been submited save the options 
	if (!empty($_POST) && check_admin_referer('update_global_js_js','update_global_js_js_field') ):
		
		
		$js_form = stripslashes ( $_POST [$opt_name] );
		global_javascript_save_revision( $js_form );
		$js_val[0] = $js_form;
		$updated = true;
		$message_number = 1; 
		?>
		
<?php endif; // end of update  
		
	if(isset($_GET['message']))
		$message_number = (int) $_GET['message'];
			
	if($message_number):
		
		$messages['s-custom-js'] = array(
		 1 => "Custom JS Saved",
		 5 => isset($_GET['revision']) ? sprintf( __('Custom JS restored to revision from %s, <em>Save Changes for the revision to take effect</em>'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false
		 );
		 $messages = apply_filters( 'post_updated_messages', $messages );
		 ?>
		<div class="updated"><p><strong><?php echo $messages['s-custom-js'][$message_number]; ?></strong></p></div>		
	<?php endif ?>


<div class="wrap">
<div class="icon32" id="icon-themes"><br></div>
<h2>Custom JS</h2>
<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
<ul class="subsubsub">
	<?php if($_GET['code'] !="none" ): ?>
	<li ><strong>Advanced Editor</strong> switch to: <a  href="?page=global-javascript/global-javascript.php&code=none">simpler</a></li>
	<?php else: ?>
	<li ><strong>Simple Editor</strong> switch to: <a  href="?page=global-javascript/global-javascript.php">advance</a></li>
	<?php endif; ?>
</ul>
<div id="code-version-toggle">
<h3 style="clear:both;">Edit JS</h3>
	
	<?php if($_GET['code'] !="none"): ?>
		<p class="search-box"><input type='text' style="width: 15em" id='query' value=''> <input type="button" onclick="search()" value="Search" class="button">  and replace with <input type='text' style="width: 15em" id="replace">
		<input onclick="replace1();" type="button" class="button" value="Replace All"> 
		</p>
	<?php endif; ?>
	<form method="post" action="themes.php?page=global-javascript/global-javascript.php<?php if($_GET['code'] == "none") {echo "&code=none"; } ?>">
		<?php settings_fields('global_js_options'); ?>
		<?php wp_nonce_field( 'update_global_js_js','update_global_js_js_field' ); ?>
		<textarea cols="80" rows="25" id="global_js_js" name="<?php echo $opt_name; ?>"><?php echo $js_val[0]; ?></textarea>
		<input type="hidden" name="action" value="update" /> <input type="hidden" name="page_options" value="<?php echo $opt_name?>" />
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e ( 'Save Changes' )?>" /> <span id="unsaved_changes" <?php if(!isset($_GET['revision'])) { ?>style="display:none;"<?php } ?> >There are some unsaved changes</span> </p>
	</form>
	</div>

<?php if($_GET['code'] !="none"): ?>
<script>
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

</script>

<?php endif; 

$safejs_post = global_javascript_get_js();

if ( 0 < $safejs_post['ID'] && wp_get_post_revisions( $safejs_post['ID'] ) ) {
	function post_revisions_meta_box( $safejs_post ) {
		// Specify numberposts and ordering args
		$args = array( 'numberposts' => 5, 'orderby' => 'ID', 'order' => 'DESC' );
		// Remove numberposts from args if show_all_rev is specified
		if ( isset( $_GET['show_all_rev'] ) )
			unset( $args['numberposts'] );

		wp_list_post_revisions( $safejs_post['ID'], $args );
	}

	add_meta_box( 'revisionsdiv', __( 'JS Revisions', 'safejs' ), 'post_revisions_meta_box', 's-custom-js', 'normal' );
	do_meta_boxes( 's-custom-js', 'normal', $safejs_post );
}
?>
</div></div>
<?php 
}

add_action ( 'admin_menu', 'global_javascript_menu' );
add_action ( 'wp_head', 'global_javascript_addjs' );


