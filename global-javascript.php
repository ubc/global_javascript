<?php
/*
Plugin Name: Global Javascript
Plugin URI: https://github.com/psmagicman/ctlt_wp_global_javascript
Description: Allows the creation and editing of Javascript on Wordpress powered sites
Version: 0.15
Author: Julien Law, CTLT
Author URI: https://github.com/ubc/ctlt_wp_global_javascript

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


class Global_Javascript {
	
	public $path = null;
	
	public $options = array();
	
	public $file;
		
	/***************
	 * Constructor *
	 ***************/
	function __construct() {
		
		$this->path = plugin_basename( dirname( __FILE__ ) );
		$this->file = plugin_basename( __FILE__ );
		
		//echo plugins_url( __FILE__ ) . '<br/>';


		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'wp_footer', array( $this,  'print_scripts' ) );
		
	
		// load the plugin
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		
		// Override the edit link, the default link causes a redirect loop
		add_filter( 'get_edit_post_link', array( $this, 'revision_post_link' ) );
	}
	
	function register_scripts(){
		if( !is_admin() ) {
			$global_javascript_upload_dir = wp_upload_dir();
			$gj_temp_link = trailingslashit( $global_javascript_upload_dir['basedir'] );
			if( file_exists( $gj_temp_link . '/global-javascript-actual.js' ) ):
				$global_javascript_minified_time = filemtime( $gj_temp_link . '/global-javascript-actual.js' );
				$global_javascript_minified_file = trailingslashit( $global_javascript_upload_dir['baseurl'] ) . filemtime( $gj_temp_link . '/global-javascript-actual.js' ) . '-global-javascript.min.js';
				$global_javascript_actual_file =  trailingslashit( $global_javascript_upload_dir['baseurl'] ) . 'global-javascript-actual.js';
				if( WP_DEBUG == false ):
					wp_register_script( 'add-global-javascript', $global_javascript_minified_file, null, null, true );
				else:
					echo 'You are currently in debug mode...<br/>';
					wp_register_script( 'add-global-javascript', $global_javascript_actual_file, null, null, true );
				endif;
			endif;
		}
	}
	
	function print_scripts(){
		wp_enqueue_script( 'add-global-javascript' );
	}
	
	public function add_menu() {
		$page =  add_theme_page ( 'Global Javascript', 'Global Javascript', 8, __FILE__, array( $this, 'admin_page' ) );
		add_action('admin_print_scripts-' . $page, array( $this, 'admin_scripts' ) );
	}
	 
	/**
	 * register_admin_styles function.
	 * adds styles to the admin page
	 * @access public
	 * @return void
	 */
	public function admin_scripts() {
		
		wp_enqueue_style( 'global-javascript-admin-styles', plugins_url( $this->path . '/css/admin.css' ) );
		
		wp_register_script( 'acejs', plugins_url( '/ace/ace.js', __FILE__ ), '', '1.0', 'true' );
		wp_enqueue_script( 'acejs' );
		
		wp_register_script( 'aceinit', plugins_url( '/js/admin.js', __FILE__ ), array('acejs', 'jquery-ui-resizable'), '1.1', 'true' );
		wp_enqueue_script( 'aceinit' );
		
		
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
		
		if ( isset( $post ) && ( 's-global-javascript' == $post->post_type ) )
			if ( strstr( $post_link, 'action=edit' ) && !strstr( $post_link, 'revision=' ) )
				$post_link = 'themes.php?page=' . $this->file;
		
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
		
		register_post_type( 's-global-javascript', array(
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
		if ( !$safejs_post = $this->get_js() ) {
			$post = array();
			$post['post_content'] = $js;
			$post['post_title']   = 'Global Javascript Editor';
			$post['post_status']  = 'publish';
			$post['post_type']    = 's-global-javascript';
			
			$post_id = wp_insert_post( $post );
			
			return $post_id;
		} // there is a javascript store in the custom post type
		
		$safejs_post['post_content'] = $js;
		
		wp_update_post( $safejs_post );
		$this->save_to_external_file( $js );
		return true;
	}
	
	function save_dependency($post_id, $js_dependancies) {
	
		add_post_meta($post_id, 'dependency', $js_dependancies, true) or update_post_meta($post_id,'dependency', $js_dependancies);
	
	}
	
	/**
	 * save_to_external_file function
	 * This function will be called to save the javascript to an external .js file
	 * @access private
	 * @return void
	 */
	private function save_to_external_file( $js_to_save ) {
		// lets minify the javascript to save first to solve timing issues
		$minified_global_js = $this->filter( $js_to_save );
		WP_Filesystem();
		
		$global_js_upload_directory = wp_upload_dir();
		
		// do some uploads directory stuff
		$global_js_temp_directory = $global_js_upload_directory['basedir'];
		$global_js_filename = trailingslashit( $global_js_temp_directory ) . 'global-javascript-actual.js';
		$global_js_minified_file = trailingslashit( $global_js_temp_directory ) . time() . '-global-javascript.min.js';
		
		//global $wp_filesystem;
		if ( !file_put_contents( $global_js_filename, $js_to_save ) || !file_put_contents( $global_js_minified_file, $minified_global_js ) ):
			echo "Error in uploading"; // return an error upon failure
		else:
			if( $global_js_handle = opendir( trailingslashit( $global_js_upload_directory['basedir'] ) ) ):
				$global_js_newest_filetime = filemtime( $global_js_filename );
				//echo $global_js_newest_filetime . '<br/>';
				while( false !== ( $global_js_files = readdir( $global_js_handle ) ) ):
					$global_js_filelastcreated = filemtime( $global_js_temp_directory . '/' . $global_js_files );
					//echo $global_js_files . '<br/>';
					if( $global_js_filelastcreated < $global_js_newest_filetime && preg_match( '/-global-javascript.min.js/i', $global_js_files ) ):
						//echo $global_js_files . '<br/>';
						unlink( $global_js_temp_directory . '/' . $global_js_files );
					endif;
				endwhile;
				closedir( $global_js_handle );
			endif;
		endif;
	}
	
	/**
	 * get_js function.
	 * Get the custom js from posts table 
	 * @access public
	 * @return void
	 */
	public function get_js() {
	
		if ( $a = array_shift( get_posts( array( 'numberposts' => 1, 'post_type' => 's-global-javascript', 'post_status' => 'publish' ) ) ) )
			$safejs_post = get_object_vars( $a );
		else
			$safejs_post = false;
	
		return $safejs_post;
	}
	
	public function admin_page() { 
		$this->update_js();
		$js = $this->get_js();
		$this->add_metabox($js);
		
		$dependency = get_post_meta( $js->ID, 'dependency', true);
		if( !is_array($dependency) )
			$dependency = array();
		
	
		?>
		
		<div class="wrap">
		
			<div id="icon-themes" class="icon32"></div>
			<h2>Global Javascript Editor</h2>
			
			<form action="themes.php?page=<?php echo  $this->file; ?>" method="post" id="global-javascript-form" >
				<?php wp_nonce_field( 'update_global_js_js','update_global_js_js_field' ); ?>
				<div class="metabox-holder has-right-sidebar">
					
					<div class="inner-sidebar">
			
						<div class="postbox">
							<h3><span>Publish</span></h3>
							<div class="inside">
								<input class="button-primary" type="submit" name="publish" value="<?php _e( 'Save Javascript' ); ?>" /> 
							</div>
						</div>
						<div class="postbox">
							<h3><span>Dependency</span></h3>
							<div class="inside">
								<?php foreach($this->get_all_dependencies() as $dep => $dep_array): ?>
								<label><input type="checkbox" name="dependency[]" value="<?php echo $dep; ?>" <?php checked( in_array($dep ,$dependency ), true ); ?> /> <?php echo $dep_array['name']; ?> </label><br />
								<?php endforeach; ?>
								
							</div>
						</div>
						<!-- ... more boxes ... -->
						<?php do_meta_boxes( 's-global-javascript', 'normal', $js ); ?>
						
					</div> <!-- .inner-sidebar -->
			
					<div id="post-body">
						<div id="post-body-content">
							<div id="global-editor-shell">
							<textarea  style="width:100%; height: 360px; resize: none;" id="global-javascript" class="wp-editor-area" name="global-javascript"><?php echo $js['post_content']; ?></textarea>
							</div>
						</div> <!-- #post-body-content -->
					</div> <!-- #post-body -->
					
				</div> <!-- .metabox-holder -->
			</form>
		</div> <!-- .wrap -->
		
	<?php 
	}
	
	/**
	 * add_metabox function.
	 * 
	 * @access public
	 * @param mixed $js
	 * @return void
	 */
	function add_metabox($js){
		
		if ( 0 < $js['ID'] && wp_get_post_revisions( $js['ID'] ) ) {
				
			add_meta_box( 'revisionsdiv', __( 'JS Revisions', 'safejs' ), array($this, 'post_revisions_meta_box'), 's-global-javascript', 'normal' );
			
		}
	}
	
	/**
	 * get_all_dependencies function.
	 * 
	 * @access public
	 * @return void
	 */
	function get_all_dependencies(){
	
		return array( 
		'jquery' => array(
			'name'=> 'jQuery',
		)
		,'backbone' => array('name' => 'Backbone')
		,'modernizer' => array(
			'name'=>'Modernizer',
			'load_in_head' => true,
			'url' => ''
		)
		);
		
	}
	
	function post_revisions_meta_box( $safejs_post ) {
		
		// Specify numberposts and ordering args
		$args = array( 'numberposts' => 5, 'orderby' => 'ID', 'order' => 'DESC' );
		// Remove numberposts from args if show_all_rev is specified
		if ( isset( $_GET['show_all_rev'] ) )
			unset( $args['numberposts'] );

		wp_list_post_revisions( $safejs_post['ID'], $args );
	}

	
	function update_js(){
		
		
		$updated = false;
		
		// the form has been submited save the options 
		if ( !empty( $_POST ) && check_admin_referer( 'update_global_js_js','update_global_js_js_field' ) ):
			
			
			$js_form = stripslashes ( $_POST ['global-javascript'] );
			$post_id = $this->save_revision( $js_form );
			$js_val[0] = $js_form;
			$updated = true;
			$message_number = 1; 
			
			$this->save_dependency($post_id, $_POST['dependency']);
			
		endif; // end of update  
			
		if( isset( $_GET['message'] ) )
			$message_number = (int) $_GET['message'];
				
		if( $message_number ):
			
			$messages['s-global-javascript'] = array(
			 1 => "Global Javascript Saved",
			 5 => isset( $_GET['revision'] ) ? sprintf( __( 'Global Javascript restored to revision from %s, <em>Save Changes for the revision to take effect</em>'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false
			 );
			 $messages = apply_filters( 'post_updated_messages', $messages );
			 ?>
			<div class="updated"><p><strong><?php echo $messages['s-global-javascript'][$message_number]; ?></strong></p></div>		
			<?php 
		endif;

	}
    
    function filter( $_content ) {
		require_once ( 'min/lib/Minify/JS/ClosureCompiler.php' );
		$_return = Minify_JS_ClosureCompiler::minify( $_content, array( 'compilation_level' => 'SIMPLE_OPTIMIZATIONS' ) );
		return $_return;
	}
}
$global_javascript_object = new Global_Javascript();
