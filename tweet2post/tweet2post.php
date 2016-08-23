<?php
defined('ABSPATH') OR exit;
/*
Plugin Name: Tweet2Post
Plugin URI: https://wordpress.org/plugins/tweet2post/
Description: <strong>Tweet2Post</strong> searches for tweets with specified hashtags and saves them as posts. Tweets can be imported manually or automatically. There are several ways to filter what tweets will be imported, you have the option to use a Custom Post Type, and you can link hastags to post categories.
Version: 1.1.5
Author: Janos Beaumont
Author URI: http://www.tweet2post.com
*/

//DEBUG STUFF
//update_option("tw2po_last_tweet","0"); //reset last tweet ID for debugging

$tw2po_version = "1.1.5";
require_once("tweet2post-class.php");

// Returns taxonomy terms
function tw2po_load_term($term_id, $taxonomy){
	global $wpdb;
	if($taxonomy == "post"){
		$taxonomy = "category";
	}else{
		$taxonomy = $taxonomy."_category";
	}
	$query = "SELECT DISTINCT t.slug FROM ".$wpdb->prefix."terms t INNER JOIN ".$wpdb->prefix."term_taxonomy tax ON tax.term_id = t.term_id WHERE t. term_id = '".$term_id."' AND tax.taxonomy = '".$taxonomy."' LIMIT 1";
	$result =  $wpdb->get_results($query);
	return $result[0]->slug;
}

// Returns taxonomy terms
function tw2po_load_terms($taxonomy){
	global $wpdb;
	if($taxonomy == "post"){
		$taxonomy = "category";
	}else{
		$taxonomy = $taxonomy."_category";
	}
	$query = "SELECT DISTINCT t.name, t.term_id FROM ".$wpdb->prefix."terms t INNER JOIN ".$wpdb->prefix."term_taxonomy tax ON tax.term_id = t.term_id WHERE tax.taxonomy = '".$taxonomy."'";
	$result =  $wpdb->get_results($query);
	return $result;
}
// Calculate centroid of geolocation polygon
function tw2po_polygoncenter($array_locations){ // $array_locations = array(array('lat' => '100.8097494','long' => '15.4812276'),array('lat' => '101.3601236','long' => '15.4812276'),array('lat' => '101.3601236','long' => '15.8454382'),array('lat' => '100.8097494','long' => '15.8454382'));
	$minlat = false;
	$minlng = false;
	$maxlat = false;
	$maxlng = false;
	foreach($array_locations as $geolocation){
		if($minlat === false){$minlat = $geolocation["lat"];}else{$minlat = ($geolocation["lat"] < $minlat) ? $geolocation["lat"] : $minlat;}
		if($maxlat === false){$maxlat = $geolocation["lat"];}else{$maxlat = ($geolocation["lat"] > $maxlat) ? $geolocation["lat"] : $maxlat;}
		if($minlng === false){$minlng = $geolocation["long"];}else{$minlng = ($geolocation["long"] < $minlng) ? $geolocation["long"] : $minlng;}
		if($maxlng === false){$maxlng = $geolocation["long"];}else{$maxlng = ($geolocation["long"] > $maxlng) ? $geolocation["long"] : $maxlng;}
	}
	$lat = $maxlat - (($maxlat - $minlat) / 2);
	$lon = $maxlng - (($maxlng - $minlng) / 2);
	return array($lat,$lon); //Array([0] => 101.0849365, [1] => 15.6633329) 
}

// Use curl to return content of an URL
function tw2po_file_get_contents($url){
	$options = array(
		CURLOPT_AUTOREFERER		=> true,
		CURLOPT_HEADER			=> false,
		CURLOPT_FOLLOWLOCATION	=> true,
		CURLOPT_RETURNTRANSFER	=> true,
		CURLOPT_ENCODING		=> '',
		CURLOPT_CONNECTTIMEOUT	=> 120,
		CURLOPT_TIMEOUT			=> 120,
		CURLOPT_MAXREDIRS		=> 10,
		CURLOPT_SSL_VERIFYPEER	=> 1,
		CURLOPT_CAINFO			=> dirname(__FILE__).'/lib/cacert.pem',
		CURLOPT_USERAGENT		=> 'Mozilla/5.0 (Mobile; rv:26.0) Gecko/26.0 Firefox/26.0'
	);
	$ch = curl_init($url);
	curl_setopt_array($ch,$options);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

// Import image to media library and set it as featured image for a post
// tw2po_generate_featured_image($image_url,$post_id);
function tw2po_generate_featured_image($image_url,$post_id){
	$upload_dir = wp_upload_dir();
	$image_data = tw2po_file_get_contents($image_url);
	$filename = basename($image_url);
	if(wp_mkdir_p($upload_dir["path"])){
		$file = $upload_dir["path"]."/".$filename;
	}else{
		$file = $upload_dir["basedir"]."/".$filename;
	}
	file_put_contents($file, $image_data);
	$post_author = get_post_field('post_author',$post_id);
	$wp_filetype = wp_check_filetype($filename, null );
	$attachment = array(
		"post_author" => $post_author,
		"post_mime_type" => $wp_filetype["type"],
		"post_title" => sanitize_file_name($filename),
		"post_content" => "",
		"post_status" => "inherit"
	);
	$attach_id = wp_insert_attachment($attachment,$file,$post_id);
	require_once(ABSPATH."wp-admin/includes/image.php");
	$attach_data = wp_generate_attachment_metadata($attach_id,$file);
	$res1 = wp_update_attachment_metadata($attach_id,$attach_data);
	$res2 = set_post_thumbnail($post_id,$attach_id);
}

// Get new tweets as an array and (maybe) import them
function tw2po_get_tweets(){
	global $wpdb;
	$tap = new TwitterImporter($wpdb);
	$retval = $tap->get_twitter_feed();
	if($tap->options["scheduler"] == "Y"){
		$comment = "Tweet2Post ran at: ".date("l jS \of F Y - H:i:s", time());
		$sql = "INSERT INTO ".$wpdb->prefix."tw2po_log"." (time, comment) VALUES ('".time()."','".esc_sql($comment)."')";
		$wpdb->query($sql);
	}
	return $retval;	
}

//Manual Update
if(!empty($_POST["tw2po_import"]) && $_POST["tw2po_import"] == "manual"){
	add_action("init","tw2po_get_tweets",10);
}

// Run Scheduler
add_action("tw2po_run_scheduler_action","tw2po_run_scheduler");
function tw2po_run_scheduler() {
	if(get_option("tw2po_scheduler") == "Y"){
		tw2po_get_tweets();
	}
}

// Create extra cron run times
function cron_add_tw2po_times($schedules){
	if(!isset($schedules["1min"])){
		$schedules["1min"] = array('interval' => 60,'display' => __('Once every minute'));
	}
	if(!isset($schedules["5min"])){
		$schedules["5min"] = array('interval' => 5*60,'display' => __('Once every 5 minutes'));
	}
	if(!isset($schedules["15min"])){
		$schedules["15min"] = array('interval' => 15*60,'display' => __('Once every 15 minutes'));
	}
	if(!isset($schedules["30min"])){
		$schedules["30min"] = array('interval' => 30*60,'display' => __('Once every 30 minutes'));
	}
	return $schedules;
}
add_filter("cron_schedules","cron_add_tw2po_times");

// Add Tweet2Post admin section
function tw2po_admin_actions() {
	add_options_page("Tweet2Post","Tweet2Post","activate_plugins","tweet2post-admin","tw2po_admin");
}
add_action("admin_menu", "tw2po_admin_actions");
function tw2po_admin() {
	include("tweet2post-admin.php");
}

// Add Tweet2Post plugin meta links
function tw2po_meta($links, $file) {
	$plugin = plugin_basename(__FILE__);
	if(strpos($file,$plugin) !== false){
		$new_links = array(
						'<a href="http://www.tweet2post.com" target="_blank">How to use</a>',
						'<a href="http://www.tweet2post.com" target="_blank">Donate</a>'
		);
		$links = array_merge( $links, $new_links );
	}
	
	return $links;
}
add_filter('plugin_row_meta', 'tw2po_meta', 10, 2);

// Plugin activation
register_activation_hook(__FILE__,"tw2po_activation");
function tw2po_activation() {
	tw2po_db_install();
	wp_schedule_event(time(),get_option("tw2po_scheduler_interval"),"tw2po_run_scheduler_action"); 
}

// Plugin deactivation
register_deactivation_hook(__FILE__,"tw2po_deactivation");
function tw2po_deactivation() {
	wp_clear_scheduled_hook("tw2po_run_scheduler_action");
}

// Create tw2po_log table to the database
function tw2po_db_install() {
	global $wpdb, $tw2po_version;
	$table_name = $wpdb->prefix."tw2po_log";
	if($wpdb->get_var("show tables like '".$table_name."'") != $table_name){
		$sql =  "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			time bigint(11) DEFAULT '0' NOT NULL,
			comment text NOT NULL,
			UNIQUE KEY id (id)
			);";
		require_once(ABSPATH."wp-admin/includes/upgrade.php");
		dbDelta($sql);
		add_option("tw2po_version", $tw2po_version);
		add_option("tw2po_taxonomy","post");
		add_option("tw2po_need_flush","Y");
		add_option("tw2po_scheduler_interval","hourly");
		add_option("tw2po_last_tweet","0");
	}else{
		update_option("tw2po_version", $tw2po_version);
	}
}

// Add a twitter field to user profiles
function tw2po_profile_fields($user){
	print "		<table class='form-table'>
		<tr>
			<th><label for='twitter'>Twitter</label></th>
			<td>
				<input type='text' name='tw2po_twitter' id='tw2po_twitter' value='".esc_attr(get_the_author_meta("tw2po_twitter",$user->ID))."' class='regular-text' /><br />
				<span class='description'>Please enter your Twitter username.</span>
			</td>
		</tr>
	</table>\n";
}
function tw2po_add_profile_fields($user_id){
	if(current_user_can("edit_user",$user_id) || current_user_can("manage_options")){
		update_user_meta($user_id,"tw2po_twitter",$_POST["tw2po_twitter"]);
	}else{
		return false;
	}
}
if(!isset($_POST["action"])){
	add_action("user_register","tw2po_profile_fields");
	add_action("user_new_form","tw2po_profile_fields");
	add_action("show_user_profile","tw2po_profile_fields");
	add_action("edit_user_profile","tw2po_profile_fields");
}
add_action("user_register", "tw2po_add_profile_fields");
add_action("edit_user_profile_update", "tw2po_add_profile_fields");
add_action("personal_options_update", "tw2po_add_profile_fields");

// Tweet Widget
function tw2po_widget_css() {
    wp_register_style('tw2pocss',plugins_url('widget.css', __FILE__ ));
    wp_enqueue_style('tw2pocss');
}
add_action('wp_enqueue_scripts','tw2po_widget_css');

// Creating the widget 
class tw2po_widget extends WP_Widget{
	function __construct(){
		parent::__construct(
			// Base ID of your widget
			'tw2po_widget', 
			// Widget name will appear in UI
			__('Tweet2Post Latest Tweets','tw2po_widget_domain'), 
			// Widget description
			array( 'description' => __('Widget to display latest imported Tweets','tw2po_widget_domain'),) 
		);
	}
	// Creating widget front-end
	// This is where the action happens
	public function widget($args,$instance){
		$title = apply_filters('widget_title',$instance['title']);
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if(!empty($title)){
			echo $args['before_title'].$title.$args['after_title'];
		}
		// This is the widget content
		$args = array(
			'post_type' => get_option("tw2po_taxonomy"),
			'posts_per_page' => $instance["amount"],
			'meta_query' => array(
				array(
					'key' => 'tw2po_status',
					'value'   => array(''),
					'compare' => 'NOT IN'
				)
			)
		);
		$queryObject = new WP_Query($args);
		$count = $queryObject->found_posts;
		if($queryObject->have_posts()){
?>
	<ul class="tw2po-widget-list">
<?php
			$x=0;
			while($queryObject->have_posts()){
				$x++;
				$queryObject->the_post();
				$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a href=\"\\0\" target=\"_blank\">\\0</a>",get_post_meta(get_the_id(),'tw2po_status',true));
?>
		<li class="tw2po-widget-item<?php if($count == $x){echo " last";}?>"><div><?php echo $text;?></div></li>
<?php
			}
?>
	</ul>
<?php
		}
		echo $args['after_widget'];
	}
	public function form($instance){
		$defaults = array('title' => __('New title','tw2po_widget_domain'),'amount' => '5','category' => 'all');
		$instance = wp_parse_args((array)$instance,$defaults);
		$title = $instance['title'];
		$amount = $instance['amount'];
		$category = $instance['category'];
?>
<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id('amount'); ?>"><?php _e('Number of Tweets to show:'); ?></label> 
	<input class="tiny-text" id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="number" step="1" min="1" value="<?php echo esc_attr($amount); ?>" size="3" />
</p>
<p>
	<label for="<?php echo $this->get_field_id('category');?>">Category:</label>
	<select id="<?php echo $this->get_field_id('category');?>" name="<?php echo $this->get_field_name('category');?>" class="widefat" style="width:100%;">
		<option value="all" <?php selected($category,"all");?>>All Categories</option>
		<?php foreach(tw2po_load_terms(get_option("tw2po_taxonomy")) as $taxonomy){?>
			<option value="<?php echo $taxonomy->name;?>" <?php selected($category,$taxonomy->name);?>><?php echo $taxonomy->name;?></option>
		<?php } ?>
	</select>
</p>
<?php 
	}
	// Updating widget replacing old instances with new
	public function update($new_instance, $old_instance){
		$instance = array();
		$instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']):'';
		$instance['amount'] = (!empty($new_instance['amount'])) ? strip_tags($new_instance['amount']):'';
		$instance['category'] = (!empty($new_instance['category'])) ? strip_tags($new_instance['category']):'';
		return $instance;
	}
}
// Register and load the widget
function tw2po_load_widget(){
	register_widget('tw2po_widget');
}
add_action('widgets_init','tw2po_load_widget');

// Everything below is for the Custom Post Type
// Custom Post Type class
class Tw2poCPT {
	public function custom_post_type($name,$single,$slug){
		$labels = array(
			'name'					=> _x($name,'post type general name'),
			'singular_name'			=> _x($single,'post type singular name'),
			'add_new'				=> _x('Add New','book'),
			'add_new_item'			=> __('Add New '.$single),
			'edit_item'				=> __('Edit'.$single),
			'new_item'				=> __('New '.$single),
			'all_items'				=> __('All '.$name),
			'view_item'				=> __('View '.$single),
			'search_items'			=> __('Search '.$name),
			'not_found'				=> __('No '.$single.' found'),
			'not_found_in_trash'	=> __('No '.$single.' found in the Trash'), 
			'parent_item_colon'		=> '',
			'menu_name'				=> $name
		);
		$args = array(
			'labels'			=> $labels,
			'description'		=> 'Holds '.$name.' and '.$single.' specific data',
			'public'			=> true,
			'menu_position'		=> 4,
			'supports'			=> array('title', 'editor', 'thumbnail', 'excerpt', 'comments','custom-fields'),
			'menu_icon'			=> 'dashicons-twitter',
			'has_archive'		=> $slug,
			'taxonomies'		=> array('post_tag',$slug."_category"),
			'rewrite'			=> array('slug' => $slug."/%".$slug."_category%",'with_front' => false),
			'query_var'			=> true,

		);
		register_post_type($slug, $args);
	}
	public function taxonomies($slug,$need_flush) {
		$labels = array(
			'name'				=> _x('Categories','taxonomy general name'),
			'singular_name'		=> _x('Category','taxonomy singular name'),
			'search_items'		=> __('Search Categories'),
			'all_items'			=> __('All Categories'),
			'parent_item'		=> __('Parent Category'),
			'parent_item_colon'	=> __('Parent Category:'),
			'edit_item'			=> __('Edit Category'), 
			'update_item'		=> __('Update Category'),
			'add_new_item'		=> __('Add New Category'),
			'new_item_name'		=> __('New Category'),
			'menu_name'			=> __('Categories')
		);
		$args = array(
			'labels'			=> $labels,
			'hierarchical'		=> true,
			'query_var'			=> true,
			'rewrite'			=> array('slug' => $slug,'with_front' => false),
		);
		register_taxonomy($slug."_category",$slug,$args);
		register_taxonomy_for_object_type($slug."_category",$slug);
		if($need_flush == "Y"){
			flush_rewrite_rules();
			update_option("tw2po_need_flush","N");
		}
		flush_rewrite_rules();
	}
}
// Run Custom Post Type class
function tw2po_custom_post_type() {
	global $tw2po_cpt;
	$my_class = new Tw2poCPT();
	$my_class->custom_post_type($tw2po_cpt["name"],$tw2po_cpt["single"],$tw2po_cpt["slug"]);
	$my_class->taxonomies($tw2po_cpt["slug"],$tw2po_cpt["need_flush"]);
}
// Setup new Custom Post Type category display in list
function manage_tw2po_category_columns($existing_columns){
	global $typenow;
	$existing_columns[$typenow."_category"] = "Category";
	unset($existing_columns["categories"]);
	return $existing_columns;
}
function print_tw2po_category_column($column_name, $post_id){
	global $typenow;
	if($column_name == $typenow."_category"){
		$terms = get_the_terms($post_id,$typenow."_category");
		if(!empty($terms)){
			$out = array();
			foreach($terms as $term){
				$out[] = "<a href='edit.php?post_type=".$typenow."&".$typenow."_category=".$term->slug."'>".esc_html(sanitize_term_field("name", $term->name, $term->term_id, $typenow."_category", "display"))."</a>";
			}
			print join(', ',$out);
		}else{
			print "No Category.";
		}
	}
}
// Setup Custom Post Type category slug
function tw2po_term_permalink($post_link, $post){
	global $tw2po_cpt;
	if( false !== strpos($post_link,"%".$tw2po_cpt["slug"]."_category%")){
		$glossary_letter = get_the_terms( $post->ID, $tw2po_cpt["slug"]."_category");
		if(!empty($glossary_letter)){
			$post_link = str_replace("%".$tw2po_cpt["slug"]."_category%",array_pop($glossary_letter)->slug,$post_link);
		}else{
			$post_link = str_replace("/%".$tw2po_cpt["slug"]."_category%","",$post_link);
		}
	}
	return $post_link;
}
// Load Custom Post Type single template file
function tw2po_post_template($template){
	global $post,$tw2po_cpt;
	$slug = $tw2po_cpt["slug"];
	if ($post->post_type == $slug){
		$plugin_path = plugin_dir_path( __FILE__ );
		$single_template = "single-".$slug.".php";
		if(file_exists(get_stylesheet_directory()."/".$single_template)){
			return get_stylesheet_directory()."/".$single_template;
		}else{
			return $template;
		}
	}
	return $template;
}
// Load Custom Post Type archive template file
function tw2po_archive_template($template){
	global $post,$tw2po_cpt;
	$slug = $tw2po_cpt["slug"];
	if ($post->post_type == $slug){
		$plugin_path = plugin_dir_path( __FILE__ );
		$archive_template = "taxonomy-".$slug.".php";
		if(file_exists(get_stylesheet_directory()."/".$archive_template)){
			return get_stylesheet_directory()."/".$archive_template;
		}else{
			return $template;
		}
	}
	return $template;
}
// Check if Custom Post Type is used and run the required stuff
$tw2po_cpt["type"] = get_option("tw2po_custom_post_type");
if($tw2po_cpt["type"] == "Y"){
	$tw2po_cpt["name"] = get_option("tw2po_custom_post_type_name");
	$tw2po_cpt["single"] = get_option("tw2po_custom_post_type_name_single");
	$tw2po_cpt["slug"] = get_option("tw2po_custom_post_type_slug");
	$tw2po_cpt["need_flush"] = get_option("tw2po_need_flush");
	add_filter("single_template", "tw2po_post_template");
	add_filter("archive_template", "tw2po_archive_template");
	add_filter('post_type', 'tw2po_term_permalink', 1, 2);
	add_filter('post_type_link', 'tw2po_term_permalink', 1, 2);
	add_filter("manage_".$tw2po_cpt["slug"]."_posts_columns", "manage_tw2po_category_columns");
	add_action("manage_".$tw2po_cpt["slug"]."_posts_custom_column", "print_tw2po_category_column", 10, 2);
	add_action("init", "tw2po_custom_post_type",0);
}
