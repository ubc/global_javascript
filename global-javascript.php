<?php
/*
Plugin Name: Improved Simpler CSS
Plugin URI: 
Description: Simplifies custom CSS on WordPress.
Version: 1.01
Author: CTLT Dev
Author URI: http://ctlt.ubc.ca
Forked from Jeremiah Orem's Custom User CSS plugin and then Frederick Ding http://simplerplugins.wordpress.com/
*/

/*  Copyright 2011  Enej 

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
	add_action('admin_print_scripts-appearance_page_imporved-simpler-css/improved-simpler-css', 'improved_simpler_css_admin_print_scripts');
endif;

add_action( 'admin_print_scripts-appearance_page_imporved-simpler-css/improved-simpler-css', 'improved_simpler_css_admin_enqueue_scripts');
add_action( 'admin_print_styles-appearance_page_imporved-simpler-css/improved-simpler-css', 'improved_simpler_css_admin_print_styles');
add_action( 'admin_init', 'improved_simpler_css_init' );
add_action( 'wp_before_admin_bar_render', 'improved_simpler_css_admin_bar_render' );


// Override the edit link, the default link causes a redirect loop
add_filter('get_edit_post_link', 'improved_simpler_css_revision_post_link');

/**
 * improved_simpler_css_admin_print_styles function.
 * adds styles to the admin page
 * @access public
 * @return void
 */
function improved_simpler_css_admin_print_styles() {
	wp_enqueue_style('simpler-css', plugins_url('/imporved-simpler-css/css/styles.css'));
}
/**
 * improved_simpler_css_admin_enqueue_scripts function.
 * adds the pos
 * @access public
 * @return void
 */
function improved_simpler_css_admin_enqueue_scripts() {
	// wp_enqueue_script( 'postbox' );
}
/**
 * improved_simpler_css_admin_print_scripts function.
 * 
 * @access public
 * @return void
 */
function improved_simpler_css_admin_print_scripts() {
	wp_enqueue_script('simpler-css', plugins_url('/imporved-simpler-css/js/codemirror.js'));
}



/**
 * improved_simpler_css_admin_bar_render function.
 * Add the menu to the admin bar
 * @access public
 * @return void
 */
function improved_simpler_css_admin_bar_render() {
	global $wp_admin_bar;
	// we can remove a menu item, like the COMMENTS link
    // just by knowing the right $id
	
	// we can add a submenu item too
	$wp_admin_bar->add_menu( array(
        'parent' => 'appearance',
        'id' => 'custom-css',
        'title' => __('Custom CSS'),
        'href' => admin_url( 'themes.php?page=imporved-simpler-css/improved-simpler-css.php')
    ) );
}




/**
 * improved_simpler_css_revision_post_link function.
 * Override the edit link, the default link causes a redirect loop
 * @access public
 * @param mixed $post_link
 * @return void
 */
function improved_simpler_css_revision_post_link($post_link)
{
	global $post;
	
	if ( isset( $post ) && ( 's-custom-css' == $post->post_type ) )
		if ( strstr( $post_link, 'action=edit' ) && !strstr( $post_link, 'revision=' ) )
			$post_link = 'themes.php?page=imporved-simpler-css/improved-simpler-css.php';
	
	return $post_link;

}


/**
 * improved_simpler_css_init function.
 * Init plugin options to white list our options
 * @access public
 * @return void
 */
function improved_simpler_css_init(){
	/*
	register_setting( 'simpler_css_options', 'simpler_css_css');
	*/
	$args = array(
	    'public' => false,
	    'query_var' => true,
	    'capability_type' => 'nav_menu_item',
	    'supports' 		=> array('revisions')
  	); 
 	register_post_type( 's-custom-css', array(
		'supports' => array( 'revisions' )
	) );
	
}

/**
 * improved_simpler_css_save_revision function.
 * safe the revisoin 
 * @access public
 * @param mixed $css
 * @return void
 */
function improved_simpler_css_save_revision( $css ) {

	// If null, there was no original safecss record, so create one
	if ( !$safecss_post = improved_simpler_css_get_css() ) {
		$post = array();
		$post['post_content'] = $css;
		$post['post_title']   = 'Custom CSS';
		$post['post_status']  = 'publish';
		$post['post_type']    = 's-custom-css';
		
		// check if there are any settings data 
		$simpler_css_css = get_option ( 'simpler_css_css' );
		if($simpler_css_css): // option settings exist 
			if(!is_array($simpler_css_css))
				$simpler_css_css = array($simpler_css_css);
			
			array_reverse($simpler_css_css);
			$count = 0;
			foreach($simpler_css_css  as $css_from_option):
				$post['post_content'] = $css_from_option;
				if($count == 0):
					$post_id = wp_insert_post( $post );
				else:	
  					$post['ID'] = $post_id;
  					wp_update_post( $post );
				endif;
				
				
				$count++; // increment the count 
			endforeach;
			
			// ok lets update the post for real this time
			$post['post_content'] = $css; // really update the stuff
			wp_update_post( $post );
			
			// time to delete the options that exits
			delete_option('simpler_css_css');
			
		else: // there is no settins data lets save this stuff
			$post_id = wp_insert_post( $post );
		endif;
		
		return true;
	} // there is a styles store in the custom post type
	
	$safecss_post['post_content'] = $css;
	
	wp_update_post( $safecss_post );
	return true;
}
/**
 * improved_simpler_css_get_css function.
 * Get the custom css from posts table 
 * @access public
 * @return void
 */
function improved_simpler_css_get_css()
{
	if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-custom-css', 'post_status' => 'publish' ) ) ) )
		$safecss_post = get_object_vars( $a );
	else
		$safecss_post = false;

	return $safecss_post;
}
/**
 * improved_simpler_css_always_get_css function.
 * return the 
 * @access public
 * @return void
 */
function improved_simpler_css_always_get_css()
{
	
	if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-custom-css', 'post_status' => 'publish' ) ) ) ):
		$safecss_post = get_object_vars( $a );
		return $safecss_post['post_content'];
	// if there is no 
	else:
	
		$simpler_css_css = get_option ( 'simpler_css_css' );
		if(!empty($simpler_css_css)):
			if(!is_array($simpler_css_css))
				$simpler_css_css = array($simpler_css_css);
		
			return $simpler_css_css[0];
		else:
			// return an empty string 
			return false;
		endif;
	endif; 

}



function improved_simpler_css_addcss() {
	$simpler_css_css = get_option ( 'simpler_css_css' );
	if(!is_array($simpler_css_css))
		$simpler_css_css = array($simpler_css_css);
		
	echo '<style type="text/css">' . "\n";
	echo improved_simpler_css_filter ( improved_simpler_css_always_get_css() ) . "\n";
	echo '</style>' . "\n";
}

function improved_simpler_css_filter($_content) {
	$_return = preg_replace ( '/@import.+;( |)|((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/))/i', '', $_content );
	$_return = htmlspecialchars ( strip_tags($_return), ENT_NOQUOTES, 'UTF-8' );
	return $_return;
}

function improved_simpler_css_menu() {
	add_theme_page ( 'Custom CSS', 'Custom CSS', 8, __FILE__, 'improved_simpler_css_options' );
}

function improved_simpler_css_options() {
	
$simpler_css_default = '/* Welcome to Improved Simpler CSS!

If you are familiar with CSS, you may delete these comments and get started. CSS (Cascading Style Sheets) is a kind of code that tells the browser how to render a web page. Here\'s an example:

img { border: 1px dotted red; }

That line basically means "give images a dotted red border one pixel thick."

CSS is not very hard to learn. There are many free references to help you get started, like http://www.w3schools.com/css/default.asp

We hope you enjoy developing your custom CSS. Here are a few things to keep in mind:
 - You cannot edit the stylesheets of your theme. Your stylesheet will be loaded after the theme stylesheets, which means that your rules can take precedence and override the theme CSS rules.
 - CSS comments will be stripped from your stylesheet when outputted. */

/* This is a comment.*/

/*
Things we strip out include:
 * HTML code
 * @import rules
 * comments (upon output)

Things we encourage include:
 * testing in several browsers!
 * trying things out!

(adapted from WordPress.com)
*/';


	$updated = false;
	$opt_name = 'simpler_css_css';

	
	$css_val = improved_simpler_css_always_get_css();
	if (!$css_val)
		$css_val = array($simpler_css_default);
	elseif(!is_array($css_val))
		$css_val = array($css_val);
	
	// the form has been submited save the options 
	if (!empty($_POST) && check_admin_referer('update_simpler_css_css','update_simpler_css_css_field') ):
		
		
		$css_form = stripslashes ( $_POST [$opt_name] );
		improved_simpler_css_save_revision( $css_form );
		$css_val[0] = $css_form;
		$updated = true;
		$message_number = 1; 
		?>
		
<?php endif; // end of update  
		
	if(isset($_GET['message']))
		$message_number = (int) $_GET['message'];
			
	if($message_number):
		
		$messages['s-custom-css'] = array(
		 1 => "Custom CSS Saved",
		 5 => isset($_GET['revision']) ? sprintf( __('Custom CSS restored to revision from %s, <em>Save Changes for the revision to take effect</em>'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false
		 );
		 $messages = apply_filters( 'post_updated_messages', $messages );
		 ?>
		<div class="updated"><p><strong><?php echo $messages['s-custom-css'][$message_number]; ?></strong></p></div>		
	<?php endif ?>


<div class="wrap">
<div class="icon32" id="icon-themes"><br></div>
<h2>Custom CSS</h2>
<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
<ul class="subsubsub">
	<?php if($_GET['code'] !="none" ): ?>
	<li ><strong>Advanced Editor</strong> switch to: <a  href="?page=imporved-simpler-css/improved-simpler-css.php&code=none">simpler</a></li>
	<?php else: ?>
	<li ><strong>Simple Editor</strong> switch to: <a  href="?page=imporved-simpler-css/improved-simpler-css.php">advance</a></li>
	<?php endif; ?>
</ul>
<div id="code-version-toggle">
<h3 style="clear:both;">Edit CSS</h3>
	
	<?php if($_GET['code'] !="none"): ?>
		<p class="search-box"><input type='text' style="width: 15em" id='query' value=''> <input type="button" onclick="search()" value="Search" class="button">  and replace with <input type='text' style="width: 15em" id="replace">
		<input onclick="replace1();" type="button" class="button" value="Replace All"> 
		</p>
	<?php endif; ?>
	<form method="post" action="themes.php?page=imporved-simpler-css/improved-simpler-css.php<?php if($_GET['code'] == "none") {echo "&code=none"; } ?>">
		<?php settings_fields('simpler_css_options'); ?>
		<?php wp_nonce_field( 'update_simpler_css_css','update_simpler_css_css_field' ); ?>
		<textarea cols="80" rows="25" id="simpler_css_css" name="<?php echo $opt_name; ?>"><?php echo $css_val[0]; ?></textarea>
		<input type="hidden" name="action" value="update" /> <input type="hidden" name="page_options" value="<?php echo $opt_name?>" />
		<p class="submit"><input type="submit" class="button-primary" value="<?php _e ( 'Save Changes' )?>" /> <span id="unsaved_changes" <?php if(!isset($_GET['revision'])) { ?>style="display:none;"<?php } ?> >There are some unsaved changes</span> </p>
	</form>
	</div>

<?php if($_GET['code'] !="none"): ?>
<script>
	var css_change = 0;
	var editor = CodeMirror.fromTextArea(document.getElementById("simpler_css_css"), {
		mode: "text/css", lineNumbers: true, indentUnit:2,
		onChange: function(){
			css_change++;
			if(css_change > 1)
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

$safecss_post = improved_simpler_css_get_css();

if ( 0 < $safecss_post['ID'] && wp_get_post_revisions( $safecss_post['ID'] ) ) {
	function post_revisions_meta_box( $safecss_post ) {
		// Specify numberposts and ordering args
		$args = array( 'numberposts' => 5, 'orderby' => 'ID', 'order' => 'DESC' );
		// Remove numberposts from args if show_all_rev is specified
		if ( isset( $_GET['show_all_rev'] ) )
			unset( $args['numberposts'] );

		wp_list_post_revisions( $safecss_post['ID'], $args );
	}

	add_meta_box( 'revisionsdiv', __( 'CSS Revisions', 'safecss' ), 'post_revisions_meta_box', 's-custom-css', 'normal' );
	do_meta_boxes( 's-custom-css', 'normal', $safecss_post );
}
?>
</div></div>
<?php 
}

add_action ( 'admin_menu', 'improved_simpler_css_menu' );
add_action ( 'wp_head', 'improved_simpler_css_addcss' );


