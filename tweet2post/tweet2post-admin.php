<?php 
// Returns a cleaned comma separated text array
function create_clean_string($myarr){
	if(!is_array($myarr)){
		$myarr = explode(",", $myarr);
	}
	foreach ($myarr as $key => $value){
		$value = trim($value);
		$value = str_replace(" ", "", $value);
		$value = str_replace("#", "", $value);
		if (is_null($value) || $value==""){
			unset($myarr[$key]);
		} else{
			$myarr[$key] = $value;
		};
	};
	$mystr = (implode(",",array_values($myarr)));
	return $mystr;
}

// Get Options
$tw2po_consumer_key = get_option("tw2po_consumer_key");
$tw2po_consumer_secret = get_option("tw2po_consumer_secret");
$tw2po_access_token = get_option("tw2po_access_token");
$tw2po_access_token_secret = get_option("tw2po_access_token_secret");
$tw2po_scheduler = get_option("tw2po_scheduler");
$tw2po_scheduler_interval = get_option("tw2po_scheduler_interval");
$tw2po_last_tweet = get_option("tw2po_last_tweet");
$tw2po_taxonomy = get_option("tw2po_taxonomy");
$tw2po_master_hashtag = get_option("tw2po_master_hashtag");
$tw2po_default_hashtags = get_option("tw2po_default_hashtags");
$tw2po_default_category = get_option("tw2po_default_category");
$tw2po_categories = json_decode(get_option("tw2po_categories"));
$tw2po_category_hashtags = json_decode(get_option("tw2po_category_hashtags"));
$tw2po_user_roles = get_option("tw2po_user_roles");
$tw2po_remember_id = get_option("tw2po_remember_id");
$tw2po_strip_title_hashtags = get_option("tw2po_strip_title_hashtags");
$tw2po_strip_content_hashtags = get_option("tw2po_strip_content_hashtags");
$tw2po_sanitize_title_hashtags = get_option("tw2po_sanitize_title_hashtags");
$tw2po_sanitize_content_hashtags = get_option("tw2po_sanitize_content_hashtags");
$tw2po_save_tags = get_option("tw2po_save_tags");
$tw2po_deny_links = get_option("tw2po_deny_links");
$tw2po_remove_links = get_option("tw2po_remove_links");
$tw2po_require_photo = get_option("tw2po_require_photo");
$tw2po_geolocation = get_option("tw2po_geolocation");
$tw2po_progress_map = get_option("tw2po_progress_map");
$tw2po_custom_post_type = get_option("tw2po_custom_post_type");
$tw2po_custom_post_type_name = get_option("tw2po_custom_post_type_name");
$tw2po_custom_post_type_name_single = get_option("tw2po_custom_post_type_name_single");
$tw2po_custom_post_type_slug = get_option("tw2po_custom_post_type_slug");
$tw2po_need_flush = get_option("tw2po_need_flush");
$tw2po_need_reload = "";
$flash_success = "";
$flash_error = "";

// Process $_POST for form id="tw2po_options_form"
if(isset($_POST["tw2po_hidden"]) && $_POST["tw2po_hidden"] == "Y"){
	// PREPARE SETTINGS
	$tw2po_variables = array(
		"tw2po_consumer_key" => "",
		"tw2po_consumer_secret" => "",
		"tw2po_access_token" => "",
		"tw2po_access_token_secret" => "",
		"tw2po_scheduler" => "",
		"tw2po_scheduler_interval" => "",
		"tw2po_last_tweet" => "",
		"tw2po_taxonomy" => "",
		"tw2po_master_hashtag" => "",
		"tw2po_default_hashtags" => "",
		"tw2po_default_category" => "",
		"tw2po_categories" => array(),
		"tw2po_category_hashtags" => array(),
		"tw2po_user_roles" => "",
		"tw2po_remember_id" => "",
		"tw2po_strip_title_hashtags" => "",
		"tw2po_strip_content_hashtags" => "",
		"tw2po_sanitize_title_hashtags" => "",
		"tw2po_sanitize_content_hashtags" => "",
		"tw2po_save_tags" => "",
		"tw2po_deny_links" => "",
		"tw2po_remove_links" => "",
		"tw2po_require_photo" => "",
		"tw2po_geolocation" => "",
		"tw2po_progress_map" => "",
		"tw2po_custom_post_type" => "",
		"tw2po_custom_post_type_name" => "",
		"tw2po_custom_post_type_name_single" => "",
		"tw2po_custom_post_type_slug" => "",
		"import_tweets" => ""
		
	);
	$_POST = array_merge($tw2po_variables,$_POST);
	// Twitter OAuth
	$update_consumer_key = trim(str_replace(" ","",$_POST["tw2po_consumer_key"]));
	$update_consumer_secret = trim(str_replace(" ","",$_POST["tw2po_consumer_secret"]));
	$update_access_token = trim(str_replace(" ","",$_POST["tw2po_access_token"]));
	$update_access_token_secret = trim(str_replace(" ","",$_POST["tw2po_access_token_secret"]));
	// Scheduler Interval
	$update_scheduler_interval = $_POST["tw2po_scheduler_interval"];
	// Last Imported Tweet ID
	$update_last_tweet = $_POST["tw2po_last_tweet"];
	// Post Type
	$update_taxonomy = $_POST["tw2po_taxonomy"];
	// Master Hashtag
	$update_master_hashtag = trim(str_replace(" ","",$_POST["tw2po_master_hashtag"]));
	// Default Hashtags
	$update_default_hashtags = trim(str_replace(" ","",$_POST["tw2po_default_hashtags"]));
	// Default Category
	$update_default_category = $_POST["tw2po_default_category"];
	// Categories & Hashtags
	if(is_array($_POST["tw2po_categories"])){
		$update_categories = array();
		$update_category_hashtags = array();
		foreach($_POST["tw2po_categories"] AS $taxonomy_category){
			if(!empty($_POST["tw2po_category_hashtags"][$taxonomy_category])){
				$update_categories[] = $taxonomy_category;
				$update_category_hashtags[] = trim(str_replace("#","",str_replace(" ","",$_POST["tw2po_category_hashtags"][$taxonomy_category])));
			}
		}
	}else{
		$update_categories = array();
		$update_category_hashtags = array();
	}
	// User Roles
	$update_user_roles = create_clean_string($_POST["tw2po_user_roles"]);
	// Remember Tweet ID
	$update_remember_id = $_POST["tw2po_remember_id"];
	// Strip Title Hashtags
	$update_strip_title_hashtags = $_POST["tw2po_strip_title_hashtags"];
	// Strip Content Hashtags
	$update_strip_content_hashtags = $_POST["tw2po_strip_content_hashtags"];
	// Sanitize Content Hashtags
	$update_sanitize_title_hashtags = $_POST["tw2po_sanitize_title_hashtags"];
	// Sanitize Content Hashtags
	$update_sanitize_content_hashtags = $_POST["tw2po_sanitize_content_hashtags"];
	// Save Post Tags
	$update_save_tags = $_POST["tw2po_save_tags"];
	// Deny Links
	$update_deny_links = $_POST["tw2po_deny_links"];
	// Link Removal
	$update_remove_links = $_POST["tw2po_remove_links"];
	// Require Photo
	$update_require_photo = $_POST["tw2po_require_photo"];
	// Require Geolocation
	$update_geolocation = $_POST["tw2po_geolocation"];
	// Support Progress Map Plugin
	$update_progress_map = $_POST["tw2po_progress_map"];
	// Custom Post Type
	$update_custom_post_type = $_POST["tw2po_custom_post_type"];
	$update_custom_post_type_name = $_POST["tw2po_custom_post_type_name"];
	if($update_custom_post_type_name == "Posts" || $update_custom_post_type_name == "Pages"){
		$update_custom_post_type_name = "";
		
	}
	$update_custom_post_type_name_single = ucfirst($_POST["tw2po_custom_post_type_name_single"]);
	$update_custom_post_type_slug = $_POST["tw2po_custom_post_type_slug"];
	if($update_custom_post_type_slug == "post" || $update_custom_post_type_slug == "page"){
		$update_custom_post_type_slug = "";
	}
	// Scheduler
	$update_scheduler = $_POST["tw2po_scheduler"];

	// UPDATE SETTINGS
	// Twitter OAuth
	if($tw2po_consumer_key != $update_consumer_key){
		update_option("tw2po_consumer_key", $update_consumer_key);
		$tw2po_consumer_key = $update_consumer_key;
	}
	if($tw2po_consumer_secret != $update_consumer_secret){
		update_option("tw2po_consumer_secret", $update_consumer_secret);
		$tw2po_consumer_secret = $update_consumer_secret;
	}
	if($tw2po_access_token != $update_access_token){
		update_option("tw2po_access_token", $update_access_token);
		$tw2po_access_token = $update_access_token;
	}
	if($tw2po_access_token_secret != $update_access_token_secret){
		update_option("tw2po_access_token_secret", $update_access_token_secret);
		$tw2po_access_token_secret = $update_access_token_secret;
	}
	// Scheduler Interval
	if($tw2po_scheduler_interval != $update_scheduler_interval){
		update_option("tw2po_scheduler_interval", $update_scheduler_interval);
		$tw2po_scheduler_interval = $update_scheduler_interval;
		if($tw2po_scheduler == "Y"){
			$flash_success .= "<br />- Scheduler Interval updated";
			wp_clear_scheduled_hook('tw2po_run_scheduler_action');
			wp_schedule_event(time(), $tw2po_scheduler_interval, 'tw2po_run_scheduler_action'); 
		}else{
			$flash_success .= "<br />- Scheduler Interval updated";
			wp_clear_scheduled_hook('tw2po_run_scheduler_action');
		}
	}
	// Scheduler
	if($tw2po_scheduler != $update_scheduler){
		update_option("tw2po_scheduler", $update_scheduler);
		$tw2po_scheduler = $update_scheduler;
		if($tw2po_scheduler == "Y"){
			$flash_success .= "<br />- Scheduler activated";
			wp_clear_scheduled_hook('tw2po_run_scheduler_action');
			wp_schedule_event(time(), $tw2po_scheduler_interval, 'tw2po_run_scheduler_action'); 
		}else{
			$flash_success .= "<br />- Scheduler disabled";
			wp_clear_scheduled_hook('tw2po_run_scheduler_action');
		}
	}
	// Post Type
	if($tw2po_taxonomy != $update_taxonomy){
		update_option("tw2po_taxonomy", $update_taxonomy);
		update_option("tw2po_default_category", "");
		update_option("tw2po_categories", "");
		update_option("tw2po_category_hashtags", "");
		$tw2po_taxonomy = $update_taxonomy;
		$tw2po_default_category = $update_default_category = array();
		$tw2po_categories = $update_categories = "";
		$tw2po_category_hashtags = $update_category_hashtags = array();
		$flash_success .= "<br />- Post Type updated";
	}
	// Master Hashtag
	if($update_master_hashtag != ""){
		if($tw2po_master_hashtag != $update_master_hashtag){
			update_option("tw2po_master_hashtag", $update_master_hashtag);
			$tw2po_master_hashtag = $update_master_hashtag;
			$flash_success .= "<br />- Master Hashtag updated";
		}
	}else{
		update_option("tw2po_master_hashtag", "");
		$tw2po_master_hashtag = "";
	}
	// Default Hashtags
	if($tw2po_default_hashtags != $update_default_hashtags){
		update_option("tw2po_default_hashtags", $update_default_hashtags);
		$tw2po_default_hashtags = $update_default_hashtags;
	}
	// Default Category
	if($tw2po_default_category != $update_default_category){
		update_option("tw2po_default_category", $update_default_category);
		$tw2po_default_category = $update_default_category;
	}
	// Categories & Hashtags
	if($update_category_hashtags != ""){
		if($update_categories != $tw2po_categories || $update_category_hashtags != $tw2po_category_hashtags){
			update_option("tw2po_categories", json_encode($update_categories));
			update_option("tw2po_category_hashtags", json_encode($update_category_hashtags));
			$tw2po_categories = $update_categories;
			$tw2po_category_hashtags = $update_category_hashtags;
			$flash_success .= "<br />- Categories & Hashtags updated";
		}
	}else{
		update_option("tw2po_categories", "");
		update_option("tw2po_category_hashtags", "");
		$tw2po_categories = array();
		$tw2po_category_hashtags = array();
	}
	// User Roles
	if($tw2po_user_roles != $update_user_roles){
		update_option("tw2po_user_roles", $update_user_roles);
		$tw2po_user_roles = $update_user_roles;
		$flash_success .= "<br />- Active User Roles updated";
	}
	// Remember Tweet ID
	if($tw2po_remember_id != $update_remember_id){
		update_option("tw2po_remember_id", $update_remember_id);
		if($update_remember_id != "Y"){
			update_option("tw2po_last_tweet", "0");
		}
		$tw2po_remember_id = $update_remember_id;
		$flash_success .= "<br />- Remember Tweet ID updated";
	}
	// Strip Title Hashtags
	if($tw2po_strip_title_hashtags != $update_strip_title_hashtags){
		update_option("tw2po_strip_title_hashtags", $update_strip_title_hashtags);
		$tw2po_strip_title_hashtags = $update_strip_title_hashtags;
		$flash_success .= "<br />- Strip Title Hashtags updated";
	}
	// Strip Content Hashtags
	if($tw2po_strip_content_hashtags != $update_strip_content_hashtags){
		update_option("tw2po_strip_content_hashtags", $update_strip_content_hashtags);
		$tw2po_strip_content_hashtags = $update_strip_content_hashtags;
		$flash_success .= "<br />- Strip Content Hashtags updated";
	}

	// Sanitize Content Hashtags
	if($tw2po_sanitize_title_hashtags != $update_sanitize_title_hashtags){
		update_option("tw2po_sanitize_title_hashtags", $update_sanitize_title_hashtags);
		$tw2po_sanitize_title_hashtags = $update_sanitize_title_hashtags;
		$flash_success .= "<br />- Strip Content Hashtags updated";
	}
	// Sanitize Content Hashtags
	if($tw2po_sanitize_content_hashtags != $update_sanitize_content_hashtags){
		update_option("tw2po_sanitize_content_hashtags", $update_sanitize_content_hashtags);
		$tw2po_sanitize_content_hashtags = $update_sanitize_content_hashtags;
		$flash_success .= "<br />- Strip Content Hashtags updated";
	}
	// Save Post Tags
	if($tw2po_save_tags != $update_save_tags){
		update_option("tw2po_save_tags", $update_save_tags);
		$tw2po_save_tags = $update_save_tags;
		$flash_success .= "<br />- Strip Content Hashtags updated";
	}
	// Deny Links
	if($tw2po_deny_links != $update_deny_links){
		update_option("tw2po_deny_links", $update_deny_links);
		$tw2po_deny_links = $update_deny_links;
		$flash_success .= "<br />- Deny Links updated";
	}
	// Link Removal
	if($tw2po_remove_links != $update_remove_links){
		update_option("tw2po_remove_links", $update_remove_links);
		$tw2po_remove_links = $update_remove_links;
		$flash_success .= "<br />- Link Removal updated";
	}
	// Require Photo
	if($tw2po_require_photo != $update_require_photo){
		update_option("tw2po_require_photo", $update_require_photo);
		$tw2po_require_photo = $update_require_photo;
		$flash_success .= "<br />- Photo Requirement updated";
	}
	// Require Geolocation
	if($tw2po_geolocation != $update_geolocation){
		update_option("tw2po_geolocation", $update_geolocation);
		$tw2po_geolocation = $update_geolocation;
		$flash_success .= "<br />- Geolocation Requirement updated";
	}
	// Support Progress Map Plugin
	if($tw2po_progress_map != $update_progress_map){
		update_option("tw2po_progress_map", $update_progress_map);
		$tw2po_progress_map = $update_progress_map;
		$flash_success .= "<br />- Progress Map Plugin Support updated";
	}
	// Custom Post Type
	if($update_custom_post_type == "Y"){
		if($update_custom_post_type_name != "" && $update_custom_post_type_name_single != "" && $update_custom_post_type_slug != ""){
			if($tw2po_custom_post_type != $update_custom_post_type){
				$tw2po_need_reload = "Y";
				update_option("tw2po_need_flush","Y");
				update_option("tw2po_custom_post_type", $update_custom_post_type);
				$tw2po_custom_post_type = $update_custom_post_type;
				$flash_success .= "<br />- Custom Post Type created";
			}
			if($tw2po_custom_post_type_name != $update_custom_post_type_name){
				update_option("tw2po_custom_post_type_name", $update_custom_post_type_name);
				$tw2po_custom_post_type_name = $update_custom_post_type_name;
				$flash_success .= "<br />- Custom Post Type Name updated";
			}
			if($tw2po_custom_post_type_name_single != $update_custom_post_type_name_single){
				update_option("tw2po_custom_post_type_name_single", $update_custom_post_type_name_single);
				$tw2po_custom_post_type_name_single = $update_custom_post_type_name_single;
				$flash_success .= "<br />- Custom Post Type Singular Name updated";
			}
			if($tw2po_custom_post_type_slug != $update_custom_post_type_slug){
				update_option("tw2po_custom_post_type_slug", strtolower(str_replace(" ","-",$update_custom_post_type_slug)));
				update_option("tw2po_need_flush","Y");
				$tw2po_custom_post_type_slug = $update_custom_post_type_slug;
				$flash_success .= "<br />- Custom Post Type Slug updated";
			}
		}else{
			$flash_error = "You must enter a <strong>Name</strong>, a <strong>Singular Name</strong>, and a <strong>Slug</strong> to setup a Custom Post Type!<br /> You cannot user 'Posts' or 'Pages' as the <strong>Name</strong>.<br /> You cannot use 'post' or 'page' as the <strong>Slug</strong>";
		}
	}else{
		if($tw2po_custom_post_type != $update_custom_post_type){
			$tw2po_need_reload = "Y";
			update_option("tw2po_custom_post_type","");
			update_option("tw2po_custom_post_type_name","");
			update_option("tw2po_custom_post_type_name_single","");
			update_option("tw2po_custom_post_type_slug","");
			update_option("tw2po_need_flush","Y");
			$tw2po_custom_post_type == "";
			$tw2po_custom_post_type_name == "";
			$tw2po_custom_post_type_name_single == "";
			$tw2po_custom_post_type_slug == "";
			$flash_success .= "<br />- Custom Post Type deleted";
		}
	}
}
// Process $_POST for form id="tw2po_get_news_form"
if(isset($_POST["tw2po_get_tweets_hidden"]) && $_POST["tw2po_get_tweets_hidden"] == "Y"){
	$retval = tw2po_get_tweets();
	if($retval != 0){
		if($retval >= 2){$multiple = "s";}else{$multiple = "";}
		$flash_success = $retval." tweet".$multiple." imported.";
	}else{
		$flash_success = "No new tweets were found.";
	}
};	
// Get the last 10 log entries
if($tw2po_scheduler == "Y"){
	global $wpdb;
	$sql = "SELECT id, time, comment FROM ".$wpdb->prefix."tw2po_log"." ORDER BY time DESC LIMIT 5";
	$data = $wpdb->get_results($sql);
};
// Display update messages
if($flash_success != ""){
	echo "<div class='updated'><p>Settings Update Log:".$flash_success."</p></div>";
}
if($flash_error != ""){
	echo "<div class='error'><p>".$flash_error."</p></div>";
}
$x=0;
if(!empty($tw2po_categories)){
	foreach($tw2po_categories AS $tw2po_category){
		$category_hashtags[$tw2po_category]["category"] = $tw2po_categories[$x];
		$category_hashtags[$tw2po_category]["hashtags"] = $tw2po_category_hashtags[$x];
		$x++;
	}
}else{
	$tw2po_categories = array();
}
if($tw2po_need_reload == "Y"){
echo "<script>window.location.replace(window.location.pathname+'?page=tweet2post-admin');</script>";
}
?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br /></div>
<h2>Tweet2Post</h2>
<form name="tw2po_options_form" method="post" action="<?php echo str_replace("%7E", "~", $_SERVER["REQUEST_URI"]); ?>">
	<input type="hidden" name="tw2po_hidden" value="Y">
	<p>This plugin imports tweets to posts for <a href="users.php">WP Users</a> meeting the following requirements:<br /><strong>
	1. Users must have entered their twitter username in their profile.<br />
	2. Users must be assigned to one of the User Roles selected below.<br />
	3. Tweets must contain at least one matching hashtag defined below.</strong></p>
	<h3>Twitter API Settings</h3>
	<p>Insert your <a href="https://apps.twitter.com/" target="_blank">Twitter App</a> OAuth verification data below.</p>
	<table class="form-table">
		<tr>
			<th><label for="tw2po_consumer_key">Consumer Key:</label></th>
			<td><input type="text" name="tw2po_consumer_key" id="tw2po_consumer_key" value="<?php echo $tw2po_consumer_key; ?>" size="40"></input></td>
		</tr>
		<tr>
			<th><label for="tw2po_consumer_secret">Consumer Secret:</label></th>
			<td><input type="text" name="tw2po_consumer_secret" id="tw2po_consumer_secret" value="<?php echo $tw2po_consumer_secret; ?>" size="40"></input></td>
		</tr>
		<tr>
			<th><label for="tw2po_access_token">Access Token:</label></th>
			<td><input type="text" name="tw2po_access_token" id="tw2po_access_token" value="<?php echo $tw2po_access_token; ?>" size="40"></input></td>
		</tr>
		<tr>
			<th><label for="tw2po_access_token_secret">Access Token Secret:</label></th>
			<td><input type="text" name="tw2po_access_token_secret" id="tw2po_access_token_secret" value="<?php echo $tw2po_access_token_secret; ?>" size="40"></input></td>
		</tr>
	</table>
	<h3>Import Settings</h3>
	<table class="form-table">
		<tr>
			<th><label for="tw2po_scheduler">Activate Scheduler:</label></th>
			<td><input type="checkbox" name="tw2po_scheduler" id="tw2po_scheduler" value="Y"<?php if($tw2po_scheduler=="Y"){echo" checked";}?>>Import tweets automatically</td>
		</tr>
		<tr>
			<th><label for="tw2po_scheduler_interval">Scheduler Interval:</label></th>
			<td><select name="tw2po_scheduler_interval">
				<option value="1min"<?php if($tw2po_scheduler_interval == "1min"){ echo " selected";}?>>Every Minute (not recommended)</option>
				<option value="5min"<?php if($tw2po_scheduler_interval == "5min"){ echo " selected";}?>>Every 5 Minutes</option>
				<option value="15min"<?php if($tw2po_scheduler_interval == "15min"){ echo " selected";}?>>Every 15 Minutes</option>
				<option value="30min"<?php if($tw2po_scheduler_interval == "30min"){ echo " selected";}?>>Every 30 Minutes</option>
				<option value="hourly"<?php if($tw2po_scheduler_interval == "hourly"){ echo " selected";}?>>Every Hour</option>
				<option value="daily"<?php if($tw2po_scheduler_interval == "daily"){ echo " selected";}?>>Once Per Day</option>
			</select></td>
		</tr>
		<tr>
			<th><label for="tw2po_taxonomy">Post Type:</label></th>
			<td><select name="tw2po_taxonomy">
<?php
$post_types = get_post_types('', 'names' ); 
foreach($post_types as $post_type){
	if(!in_array($post_type,array("page","attachment","revision","nav_menu_item","cookielawinfo"))){
		$obj = get_post_type_object($post_type);
		if($tw2po_taxonomy == $post_type){
			$selected = " selected";
		}else{
			$selected = "";
		}
		echo "
				<option value='".$post_type."'".$selected.">".$obj->labels->name."</option>";
	}
}
?>
			</select></td>
		</tr>
		<tr>
			<th><label for="tw2po_master_hashtag">Master Hashtag:</label></th>
			<td><input type="text" name="tw2po_master_hashtag" id="tw2po_master_hashtag" value="<?php echo $tw2po_master_hashtag; ?>" size="40"></input>&nbsp;<em>expl. 'onmysite'</em><br />
			<small><em>	If a Master Hashtag is set, only tweets using it will be imported.<br />Enter hashtag without <strong>#.</strong></em></small></input></td>
		</tr>
		<tr>
			<th><label for="tw2po_default_hashtags">Default Hashtags:</label></th>
			<td><input type="text" name="tw2po_default_hashtags" id="tw2po_default_hashtags" value="<?php echo $tw2po_default_hashtags; ?>" size="40"></input><br />
			<small><em>	Tweets containing a default hashtag will be imported.<br />Enter hashtags without <strong>#.</strong></em></small></input></td>
		</tr>
		<tr>
			<th><label for="tw2po_default_category">Default Category:</label></th>
<?php
$categories = tw2po_load_terms($tw2po_taxonomy);
if(empty($categories)){
	echo "			<td>No categories were found for this post type.</td>";
}else{
?>
			<td><select name="tw2po_default_category">
<?php
			echo "				<option value=''>none</option>
";
	foreach($categories as $category){
		if($tw2po_default_category == $category->term_id){
			$selected = " selected";
		}else{
			$selected = "";
		}
			echo "				<option value='".$category->term_id."'".$selected.">".$category->name."</option>
";
	}
?>
			</select><br />
			<small><em>Used for tweets containing Master/Default Hashtag(s) but no Category Hashtag(s).</em></small></td>
<?php
}
?>
		</tr>
		<tr>
			<th><label for="tw2po_categories">Categories &amp; Hashtags:</label></th>
<?php
$categories = tw2po_load_terms($tw2po_taxonomy);
if(empty($categories)){
	echo "			<td>No categories were found for this post type.</td>";
}else{
?>
			<td>
				<table>
					<tr>
						<td style="background:#CCC">Active</td>
						<td style="background:#CCC">Category</td>
						<td style="background:#CCC">Twitter hashtags</td>
					</tr>
<?php
	$x=0;
	foreach($categories as $category){
		if(in_array($category->term_id,$tw2po_categories)){
			$checked = " checked";
		}else{
			$checked = "";
		}
		echo "					<tr>
						<td style='background:#DDD;text-align:center'><input type='checkbox' id='tax-".$category->term_id."' name='tw2po_categories[]'value='".$category->term_id."'".$checked." /></td>
						<td style='background:#DDD'>".$category->name."</td>
";
		if(isset($category_hashtags[$category->term_id]["hashtags"]) && !empty($category_hashtags[$category->term_id]["hashtags"])){
			$hashtag = $category_hashtags[$category->term_id]["hashtags"];
		}else{
			$hashtag = "";
		}
		echo "						<td style='background:#DDD'><input type='text' id='tag-".$category->term_id."' name='tw2po_category_hashtags[".$category->term_id."]' value='".$hashtag."' /></td>
					</tr>
";
		$x++;
	}
?>
				</table>
				<p><em><small>
					Tweets containing a matching hashtag will be posted into the respective category.<br />
					Separate multiple hastags with a comma (no empty spaces).<br />
					Enter hashtags without <strong>#.</strong>
				</small></em></p>
			</td>
<?php
}
?>
		</tr>
		<tr>
			<th><label for="tw2po_usernames">User Role(s):</label></th>
			<td>
<?php
global $wp_roles;
if(!isset($wp_roles)){
	$wp_roles = new WP_Roles();
}
$roles = $wp_roles->get_names();
$tw2po_user_roles = explode(",",$tw2po_user_roles);
foreach($roles as $role_value => $role_name){
	if(in_array($role_value, $tw2po_user_roles)){
		$checked = " checked";
	}else{
		$checked = "";
	}
	echo "
				<p><input type='checkbox' name='tw2po_user_roles[]' value='".$role_value."'".$checked .">".$role_name."</p>";
}
?>
				<p><em><small>
					The plugin will import tweets from <a href="users.php">WP Users</a> in the selected role(s).
				</small></em></p>
			</td>
		</tr>
	</table>
	<h3>Extra Options</h3>
	<table class="form-table">
		<tr>
			<th><label for="tw2po_deny_links">Remember Last Tweet ID:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_remember_id" id="tw2po_remember_id" value="Y"<?php if($tw2po_remember_id == "Y"){echo " checked";}?>> Tweet2Post will only search for tweets that are newer than the last imported tweet.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_strip_title_hashtags">Strip Title Hashtags:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_strip_title_hashtags" id="tw2po_strip_title_hashtags" value="Y"<?php if($tw2po_strip_title_hashtags == "Y"){echo " checked";}?>> Remove hashtags from tweets for the post title.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_strip_content_hashtags">Strip Content Hashtags:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_strip_content_hashtags" id="tw2po_strip_content_hashtags" value="Y"<?php if($tw2po_strip_content_hashtags == "Y"){echo " checked";}?>> Remove hashtags from tweets for the post content.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_sanitize_title_hashtags">No '#' in Title Hashtags:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_sanitize_title_hashtags" id="tw2po_sanitize_title_hashtags" value="Y"<?php if($tw2po_sanitize_title_hashtags == "Y"){echo " checked";}?>> Remove "#" chracter from hashtags in the post title.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_sanitize_content_hashtags">No '#' in Content Hashtags:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_sanitize_content_hashtags" id="tw2po_sanitize_content_hashtags" value="Y"<?php if($tw2po_sanitize_content_hashtags == "Y"){echo " checked";}?>> Remove "#" chracter from hashtags in the post content.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_save_tags">Hashtags as post tags:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_save_tags" id="tw2po_save_tags" value="Y"<?php if($tw2po_save_tags == "Y"){echo " checked";}?>> Add hashtags from a tweet as tags of the post.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_deny_links">No Tweets With Links:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_deny_links" id="tw2po_deny_links" value="Y"<?php if($tw2po_deny_links == "Y"){echo " checked";}?>> Tweets containing links will NOT get imported.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_remove_links">Link Removal:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_remove_links" id="tw2po_remove_links" value="Y"<?php if($tw2po_remove_links == "Y"){echo " checked";}?>> Strip links from imported tweets.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_require_photo">Require Media:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_require_photo" id="tw2po_require_photo" value="Y"<?php if($tw2po_require_photo == "Y"){echo " checked";}?>> Only import tweets that include a photo or video.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_geolocation">Require Geo Location:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_geolocation" id="tw2po_geolocation" value="Y"<?php if($tw2po_geolocation == "Y"){echo " checked";}?>> Only import tweets that include a Geo Location.</p>
				<p style="padding-left:30px"><small>
					Helpful Articles on using Geo Location:<br />
					<a href="https://support.twitter.com/articles/122236" target="_blank">Adding your location to a Tweet</a><br />
					<a href="https://support.twitter.com/articles/118492" target="_blank">Using location services on mobile devices</a>
				</small></p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_progress_map">Use Progress Map Plugin:</label></th>
			<td>
				<p><input type="checkbox" name="tw2po_progress_map" id="tw2po_progress_map" value="Y"<?php if($tw2po_progress_map == "Y"){echo " checked";}?>> Insert Geo Location data for the <a href="http://codecanyon.net/item/progress-map-wordpress-plugin/5581719?ref=vortexbased" target="_blank">Progress Map Wordpress Plugin</a>.</p>
			</td>
		</tr>
		<tr>
			<th><label for="tw2po_custom_post_type">Custom Post Type:</label></th>
			<td>
				<p>Don't want to import tweets as regular posts? Setup a separate Post Type!</p>
				<table>
					<tr>
						<td style="background:#CCC">Active</td>
						<td style="background:#CCC">Name</td>
						<td style="background:#CCC">Singular Name</td>
						<td style="background:#CCC">Slug</td>
					</tr>
					<tr>
						<td style="background:#DDD;text-align:center"><input type="checkbox" name="tw2po_custom_post_type" id="tw2po_custom_post_type" value="Y"<?php if($tw2po_custom_post_type == "Y"){echo " checked";}?>><br />&nbsp;</td>
						<td style="background:#DDD"><input type="text" name="tw2po_custom_post_type_name" id="tw2po_custom_post_type_name" value="<?php print $tw2po_custom_post_type_name;?>" style="width:150px"><br />&nbsp;<small><em>(expl. 'My Tweets')</em></small></td>
						<td style="background:#DDD"><input type="text" name="tw2po_custom_post_type_name_single" id="tw2po_custom_post_type_name_single" value="<?php print $tw2po_custom_post_type_name_single;?>" style="width:150px"><br />&nbsp;<small><em>(expl. 'Tweet')</em></small></td>
						<td style="background:#DDD"><input type="text" name="tw2po_custom_post_type_slug" id="tw2po_custom_post_type_slug" value="<?php print $tw2po_custom_post_type_slug;?>" style="width:150px"><br />&nbsp;<small><em>(expl. 'my-tweets')</em></small></td>
					</tr>
				</table>
				<p><em><small>
					The 'Name' is used in the left Admin Menu (cannot be 'Posts' or 'Pages')<br />
					The 'Slug' needs to be all lower case; No empty spaces. (cannot be 'post' or 'page')
					<?php if($tw2po_custom_post_type == "Y"){echo "<br />Copy 'archive.php' to 'taxonomy-".$tw2po_custom_post_type_slug.".php' and 'single.php' to 'single-".$tw2po_custom_post_type_slug.".php' in your theme folder, and modify<br />the newly created files to customize the 'Archive' and 'Single Post' design for this Custom Post Type.";}?> 
			</td>
		</tr>
	</table>
	<p class="submit">
		<input class="button button-primary button-large" type="submit" name="Submit" value="Update Settings" />
	</p>
</form>	
<?php
if($tw2po_scheduler == "Y"){
?>
	<h3>Update History</h3>
	<a id="#update-history"></a>
	<ul>
<?php
	$minutes = round(abs(time() - wp_next_scheduled("tw2po_run_scheduler_action")) / 60,2);
	if($minutes <= "1"){
		$minutes = abs(time() - wp_next_scheduled("tw2po_run_scheduler_action"))." seconds";
	}elseif($minutes <= "1.75"){
		$minutes = "about 1 minute";
	}elseif($minutes >= "90"){
		$minutes = "about ".round($minutes/60,2)." hours";
	}elseif($minutes >= "60"){
		$minutes = "about 1 hour";
	}else{
		$minutes = "about ".round($minutes)." minutes";
	}
	foreach ($data as $item){
		echo "<li>".$item->comment."</li>";
	}
?>
	</ul>
	<p><em>...the next update is scheduled to run in <?php print $minutes;?>. </em></p>
<form name="tw2po_get_news_form" method="post" action="<?php echo str_replace("%7E", "~", $_SERVER["REQUEST_URI"]); ?>">
	<input type="hidden" name="tw2po_import" value="manual">
	<p class="submit"><input type="submit" class="button button-primary button-large" name="import_tweets" value="Check for new tweets and import now" /></p>
</form>
<?php
}else{
?>
<form name="tw2po_get_news_form" method="post" action="<?php echo str_replace("%7E", "~", $_SERVER["REQUEST_URI"]); ?>">
	<input type="hidden" name="tw2po_import" value="test">
	<p class="submit">
		<input type="submit" class="button button-primary button-large" name="import_tweets" value="Run Test (Look for new tweets, but don't import them.)" />
	</p>
</form>	
<?php
	if(isset($_POST["tw2po_import"]) && $_POST["tw2po_import"] == "test"){
?>
<h3>List of tweets that could be imported</h3>
<table border=1 cellpadding=3 cellspacing=0>
	<tr>
		<td>#</td>
		<td>Date</td>
		<td>Twitter Username</td>
		<td>Tweet ID</td>
		<td>WP Category</td>
		<td>Tweet</td>
		<td>Hashtags</td>
		<td>Photo</td>
		<td>Location</td>
		<td>latitude</td>
		<td>longitude</td>
	</tr>
<?php
		$minutes = round(abs(time() - wp_next_scheduled("tw2po_hourly_update_action")) / 60,2);
		$data = tw2po_get_tweets();
		$x=1;
		if(!empty($data)){
			foreach ($data as $item){
				$item["category"] = implode(",",$item["category"]);
				echo "	<tr>";
				echo "		<td>".$x."</td>\n";
				echo "		<td>".$item["date"]."</td>\n";
				echo "		<td>".$item["twitter_username"]."</td>\n";
				echo "		<td>".$item["id"]."</td>\n";
				echo "		<td>".$item["category"]."</td>\n";
				echo "		<td>".$item["description_filtered"]."</td>\n";
				echo "		<td>".implode(", ",array_values($item["hashtags"]))."</td>\n";
				echo "		<td>".$item["photo"]."</td>\n";
				echo "		<td>".$item["place_name"]."</td>\n";
				echo "		<td>".$item["latitude"]."</td>\n";
				echo "		<td>".$item["longitude"]."</td>\n";
				echo "	</tr>";
				$x++;
			}
		}else{
			echo "<tr><td colspan=11>No new tweets found.</td></tr>";
		}
?>
<table>
<form name="tw2po_get_news_form" method="post" action="<?php echo str_replace("%7E", "~", $_SERVER["REQUEST_URI"]); ?>">
	<input type="hidden" name="tw2po_import" value="manual">
	<p class="submit"><input type="submit" class="button button-primary button-large" name="import_tweets" value="Import Tweets Now" /></p>
</form>
<?php
	}
}
?>

</div>
