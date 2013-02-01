<?php
/*
Plugin Name: Global Javascript
Plugin URI: https://github.com/psmagicman/ctlt_wp_global_javascript
Description: Allows the creation and editing of Javascript on Wordpress powered sites
Version: 0.9.2
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


class GlobalJavascript {
	static $path = null;
	
	/***************
	 * Constructor *
	 ***************/
	function __construct() {
		/*if( $_GET['code'] != 'none' ):
			add_action('admin_print_scripts-appearance_page_global-javascript/global-javascript', 'global_javascript_admin_print_scripts');
		endif;*/
		
		self::$path = plugin_basename( dirname( __FILE__ ) );
		if ( !class_exists( 'Minify' ) ) {
			require plugin_dir_path( __FILE__ ) . '/lib/Minify.php';
		}
		else {
			echo 'class already exists<br/>';
		}
		// register admin styles and scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts') );
		add_action( 'admin_print_styles', array( $this, 'register_admin_styles' ) );
		
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'admin_bar_render' ) );
		// Override the edit link, the default link causes a redirect loop
		add_filter('get_edit_post_link', array( $this, 'revision_post_link' ) );
		add_action ( 'admin_menu', array( $this, 'gj_menu' ) );
		add_action ( 'wp_head', array( $this, 'add_js' ) );
	}
	 
	/**
	 * register_admin_styles function.
	 * adds styles to the admin page
	 * @access public
	 * @return void
	 */
	public function register_admin_styles() {
		wp_enqueue_style( 'global-javascript-admin-styles', plugins_url( self::$path . '/css/styles.css' ) );
	}
	
	/**
	 * register_admin_scripts function.
	 * adds the pos
	 * @access public
	 * @return void
	 */
	/*function global_javascript_admin_enqueue_scripts() {
		// wp_enqueue_script( 'postbox' );
	}*/
	/**
	 * global_javascript_admin_print_scripts function.
	 * 
	 * @access public
	 * @return void
	 */
	public function register_admin_scripts() {
		wp_enqueue_script( 'global-javascript-admin-script', plugins_url( self::$path . '/js/codemirror.js' ) );
	}
	
	
	/**
	 * global_javascript_admin_bar_render function.
	 * Add the menu to the admin bar
	 * @access public
	 * @return void
	 */
	public function admin_bar_render() {
		global $wp_admin_bar;
		// we can remove a menu item, like the COMMENTS link
		// just by knowing the right $id
		
		// we can add a submenu item too
		$wp_admin_bar->add_menu( array(
			'parent' => 'appearance',
			'id' => 'custom-js',
			'title' => __( 'Custom JS' ),
			'href' => admin_url( 'themes.php?page=' . self::$path . '/global-javascript.php' )
		) );
	}
	
	/**
	 * revision_post_link function.
	 * Override the edit link, the default link causes a redirect loop
	 * @access public
	 * @param mixed $post_link
	 * @return void
	 */
	public function revision_post_link( $post_link ) {
		global $post;
		
		if ( isset( $post ) && ( 's-custom-js' == $post->post_type ) )
			if ( strstr( $post_link, 'action=edit' ) && !strstr( $post_link, 'revision=' ) )
				$post_link = 'themes.php?page=' . self::$path . '/global-javascript.php';
		
		return $post_link;
	
	}
	
	
	/**
	 * init function.
	 * Init plugin options to white list our options
	 * @access public
	 * @return void
	 */
	public function init(){
		/*
		register_setting( 'global_js_options', 'global_js_js');
		*/
		$args = array(
			'public' => false,
			'query_var' => true,
			'capability_type' => 'nav_menu_item',
			'supports' 		=> array( 'revisions' )
		); 
		register_post_type( 's-custom-js', array(
			'supports' => array( 'revisions' )
		) );
		
	}
	
	/**
	 * save_revision function.
	 * safe the revisoin 
	 * @access public
	 * @param mixed $js
	 * @return void
	 */
	public function save_revision( $js ) {
	
		// If null, there was no original safejs record, so create one
		if ( !$safejs_post = $this->gj_get_js() ) {
			$post = array();
			$post['post_content'] = $js;
			$post['post_title']   = 'Custom JS';
			$post['post_status']  = 'publish';
			$post['post_type']    = 's-custom-js';
			
			// check if there are any settings data 
			$global_js_js = get_option ( 'global_js_js' );
			if( $global_js_js ): // option settings exist 
				if( !is_array( $global_js_js ) )
					$global_js_js = array( $global_js_js );
				
				array_reverse( $global_js_js );
				$count = 0;
				foreach( $global_js_js  as $js_from_option ):
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
				delete_option( 'global_js_js' );
				
			else: // there is no settins data lets save this stuff
				$post_id = wp_insert_post( $post );
			endif;
			
			return true;
		} // there is a javascript store in the custom post type
		
		$safejs_post['post_content'] = $js;
		
		wp_update_post( $safejs_post );
		$this->save_to_external_file( $js );
		return true;
	}
	
	/**
	 * save_to_external_file function
	 * This function will be called to save the javascript to an external .js file
	 * @access private
	 * @return void
	 */
	private function save_to_external_file( $js_to_save ) {
		$url = wp_nonce_url('themes.php?page=' . self::$path);
		$method = '';
		if ( false === ($creds = request_filesystem_credentials($url, $method, false, false, null ) ) ) {
			// don't have credentials yet
			// so stop processing
			return true;
		}
		// got the creds
		if ( !WP_Filesystem($creds) ) {
			//creds no good, ask user for them again
			request_file_system_credentials($url, method, true, false, null);
			return true;
		}
		
		$global_js_current_blog_id = get_current_blog_id();
		$global_js_upload_directory = wp_upload_dir();
		
		// do some uploads directory stuff
		/*$temp_target = trailingslashit( $global_js_upload_directory['basedir'] ) . 'global-javascript';
		if( !is_dir( $temp_target ) ):
			if( wp_mkdir_p( $temp_target ) ):
				echo '<script>alert("Directory created at $temp_target");</script>';
			else:
				echo '<script>alert("Error: Cannot create directory");</script>';
			endif;
		endif;
		$temp_target = trailingslashit( $temp_target ) . $global_js_current_blog_id;
		if( !is_dir( $temp_target ) ):
			if( wp_mkdir_p( $temp_target ) ):
				echo '<script>alert("Directory created at $temp_target");</script>';
			else:
				echo '<script>alert("Error: Cannot create directory");</script>';
			endif;
		endif;
		
		$global_js_upload_directory['basedir'] = trailingslashit($temp_target);*/
		//echo $global_js_upload_directory['basedir'] . '<br/>';
		$global_js_filename = trailingslashit($global_js_upload_directory['basedir']) . 'global-javascript-actual.js';
		$global_js_minified = trailingslashit($global_js_upload_directory['basedir']) .'minified-' . time() . '-global-javascript-actual.js';
		//echo $global_js_filename . '<br/>';
		
		global $wp_filesystem;
		$minified_global_js = $this->gj_filter( $js_to_save );
		if ( !$wp_filesystem->put_contents( $global_js_filename, $js_to_save, FS_CHMOD_FILE ) || !!$wp_filesystem->put_contents( $global_js_minified, $minified_global_js, FS_CHMOD_FILE ) ) {
			echo "<script>Error saving the file...</script>";
		}
		
	}
	
	/**
	 * gj_get_js function.
	 * Get the custom js from posts table 
	 * @access public
	 * @return void
	 */
	public function gj_get_js() {
	
		if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-custom-js', 'post_status' => 'publish' ) ) ) )
			$safejs_post = get_object_vars( $a );
		else
			$safejs_post = false;
	
		return $safejs_post;
	}
	/**
	 * always_get_js function.
	 * return the 
	 * @access public
	 * @return void
	 */
	public function always_get_js() {
		
		if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-custom-js', 'post_status' => 'publish' ) ) ) ):
			$safejs_post = get_object_vars( $a );
			return $safejs_post['post_content'];
		// if there is no 
		else:
		
			$global_js_js = get_option ( 'global_js_js' );
			if( !empty( $global_js_js ) ):
				if( !is_array( $global_js_js ) )
					$global_js_js = array( $global_js_js );
			
				return $global_js_js[0];
			else:
				// return an empty string 
				return false;
			endif;
		endif; 
	
	}
	
	
	
	public function add_js() {
		$global_js_js = get_option ( 'global_js_js' );
		if(!is_array($global_js_js))
			$global_js_js = array( $global_js_js );
		
		$global_javascript_blog_id = get_current_blog_id();
		$global_javascript_upload_dir = wp_upload_dir();
		
		/*$temp_target = trailingslashit( $global_javascript_upload_dir['basedir'] ) . 'global-javascript';
		if( !is_dir( $temp_target ) ):
			if( wp_mkdir_p( $temp_target ) ):
				echo '<script>alert("Directory created at $temp_target");</script>';
			else:
				echo '<script>alert("Error: Cannot create directory");</script>';
			endif;
		endif;
		$temp_target = trailingslashit( $temp_target ) . $global_javascript_blog_id;
		if( !is_dir( $temp_target ) ):
			if( wp_mkdir_p( $temp_target ) ):
				echo '<script>alert("Directory created at $temp_target");</script>';
			else:
				echo '<script>alert("Error: Cannot create directory");</script>';
			endif;
		endif;*/
		
		$global_javascript_filename = trailingslashit($global_javascript_upload_dir['baseurl']) . 'global-javascript-actual.js';
		
		echo '<script type="text/javascript" src="' . $global_javascript_filename . '">' . '</script>' . "\n";
		//echo '</script>' . "\n";
			
		/*echo '<script type="text/javascript">' . "\n";
		echo $this->gj_filter( $this->always_get_js() ) . "\n";
		echo '</script>' . "\n";*/
	}
	
	public function gj_filter( $_content ) {
		// remove contents
		$_content = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!' , '', $_content);
		// remove white space
		$_return = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $_content);
		return $_return;
	}
	
	public function gj_menu() {
		add_theme_page ( 'Custom JS', 'Custom JS', 8, __FILE__, array( $this, 'gj_options' ) );
	}
	
	public function gj_options() {
		
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
	
		
		$js_val = $this->always_get_js();
		if ( !$js_val )
			$js_val = array( $global_js_default );
		elseif( !is_array( $js_val ) )
			$js_val = array( $js_val );
		
		// the form has been submited save the options 
		if ( !empty( $_POST ) && check_admin_referer( 'update_global_js_js','update_global_js_js_field' ) ):
			
			
			$js_form = stripslashes ( $_POST [$opt_name] );
			$this->save_revision( $js_form );
			$js_val[0] = $js_form;
			$updated = true;
			$message_number = 1; 
			?>
			
	<?php endif; // end of update  
			
		if( isset( $_GET['message'] ) )
			$message_number = (int) $_GET['message'];
				
		if( $message_number ):
			
			$messages['s-custom-js'] = array(
			 1 => "Custom JS Saved",
			 5 => isset( $_GET['revision'] ) ? sprintf( __( 'Custom JS restored to revision from %s, <em>Save Changes for the revision to take effect</em>'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false
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
		<li ><strong>Advanced Editor</strong> switch to: <?php echo '<a  href="?page=' . self::$path . '/global-javascript.php&code=none">simpler</a>'?></li>
		<?php else: ?>
		<li ><strong>Simple Editor</strong> switch to: <?php echo '<a  href="?page=' . self::$path . '/global-javascript.php">advance</a>'?></li>
		<?php endif; ?>
	</ul>
	<div id="code-version-toggle">
	<h3 style="clear:both;">Edit JS</h3>
		
		<?php if($_GET['code'] !="none"): ?>
			<p class="search-box"><input type='text' style="width: 15em" id='query' value=''> <input type="button" onclick="search()" value="Search" class="button">  and replace with <input type='text' style="width: 15em" id="replace">
			<input onclick="replace1();" type="button" class="button" value="Replace All"> 
			</p>
		<?php endif; ?>
		<?php echo '<form method="post" action="themes.php?page=' . self::$path . '/global-javascript.php'?><?php if($_GET['code'] == "none") {echo "&code=none"; } ?>">
			<?php settings_fields('global_js_options'); ?>
			<?php wp_nonce_field( 'update_global_js_js','update_global_js_js_field' ); ?>
			<textarea cols="80" rows="25" id="global_js_js" name="<?php echo $opt_name; ?>"><?php echo $js_val[0]; ?></textarea>
			<input type="hidden" name="action" value="update" /> <input type="hidden" name="page_options" value="<?php echo $opt_name?>" />
			<p class="submit"><input type="submit" class="button-primary" value="<?php _e ( 'Save Changes' )?>" /> <span id="unsaved_changes" <?php if(!isset($_GET['revision'])) { ?>style="display:none;"<?php } ?> >There are some unsaved changes</span> </p>
		</form>
		</div>
	
	<?php 
		if($_GET['code'] !="none"): 
			wp_enqueue_script( 'global-javascript-loading', plugins_url( self::$path . '/js/global-javascript-loading.js' ) );
		endif; 
	
		$safejs_post = $this->gj_get_js();
	
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

}

$global_javascript_init_var = new GlobalJavascript();
