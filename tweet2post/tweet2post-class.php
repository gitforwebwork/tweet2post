<?php	
/**
 * Tweet2Post
 *
 * Class to find tweets with selected hashtags and save them as posts.
 *
 * Based on 'Tweets As Posts' plugin by Chandesh Parekh
 *
 * @author Janos Beaumont
 **/
require_once('lib/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterImporter{
	var $options;
	var $feed;
	var $wpdb;
	function __construct($wpdb){
		$this->wpdb = $wpdb;	
		$this->get_options();
	}
	// Return an array of tweets
	public function get_twitter_feed(){
		$toa = new TwitterOAuth($this->options["consumer_key"],$this->options["consumer_secret"],$this->options["access_token"],$this->options["access_token_secret"]);
		// in 'q', you can add filters, such as: filter:videos (%20filter%3Avideos), filter:images (%20filter%3Aimages), filter:links (%20filter%3Alinks)
		if(isset($this->options["since_id"]) && $this->options["since_id"] >= 1 ){
			$since_id = $this->options["since_id"];
		}else{
			$since_id = 1;
		}
		if(!empty($this->options["category_hashtags"])){
			$category_hashtags = json_decode($this->options["category_hashtags"]);
		}else{
			$category_hashtags = "";
		}
		if(!empty($this->options["default_hashtags"])){
			$default_hashtags = explode(",",$this->options["default_hashtags"]);
		}else{
			$default_hashtags = "";
		}
		$query_str = "";
		$all_hashtags = array();
		if(!empty($this->options["master_hashtag"])){
			$query_str = "#".$this->options["master_hashtag"];
		}elseif(!empty($category_hashtags) || !empty($default_hashtags)){
			$n=0;
			if(!empty($category_hashtags)){
				foreach($category_hashtags as $category_hashtag){
					$category_hashtag = explode(",",$category_hashtag);
					foreach($category_hashtag AS $the_hashtag){
						if($n >= 1){
							$query_str .= "+OR+";   
						};
						$query_str .= urlencode("#".$the_hashtag);
						$n ++;
						$all_hashtags[] = $the_hashtag;
					}
				}
			}
			if(!empty($default_hashtags)){
				foreach($default_hashtags as $default_hashtag){
					$default_hashtag = explode(",",$default_hashtag);
					foreach($default_hashtag AS $the_hashtag){
						if($n >= 1){
							$query_str .= "+OR+";   
						};
						$query_str .= urlencode("#".$the_hashtag);
						$n ++;
						$all_hashtags[] = $the_hashtag;
					}
				}
			}
		}
		if(!empty($query_str)){
			$last_tweet = $this->options["last_tweet"];
			$query = array(
				"q" => $query_str,
				"result_type" => "recent",
				"since_id" => $since_id,
				"count" => 100,
				"since_id" => $last_tweet
			);
			echo $query;
			$results = $toa->get('search/tweets', $query);
			$feed = array();
			foreach($results->statuses as $result){
				$hashtags = "";
				$category = "";
				$matching_hashtags = "";
				if(!empty($category_hashtags)){
					if(!empty($result->entities->hashtags)){
						foreach($result->entities->hashtags AS $hashtag){
							$hashtags[] = strtolower($hashtag->text);
						}
						$hashtags = array_values($hashtags);
					}
					
					foreach($hashtags AS $hashtag){
						if(in_array($hashtag,$all_hashtags)){
							$matching_hashtags[] = $hashtag;
						}
					}
					$matching_categories = array();
					if(!empty($matching_hashtags)){
						$custom_categories = json_decode($this->options["categories"]);
						$custom_categories_hashtags = json_decode($this->options["category_hashtags"]);
						$x=0;
						foreach($custom_categories_hashtags AS $custom_category_hashtags){
							$category_found = 0;
							$custom_category_hashtags = explode(",",$custom_category_hashtags);
							foreach($custom_category_hashtags AS $custom_category_hashtag){
								if($category_found == 0){
									if(in_array($custom_category_hashtag,$matching_hashtags)){
										$matching_categories[] = $custom_categories[$x];
										$category_found = 1;
									}
								}
							}
							$x++;
						}
						$category = $matching_categories;
					}
				}
				if(empty($category) && !empty($this->options["default_category"])){
					$category = (array) $this->options["default_category"];
				}elseif(empty($category)){
					$category = array();
				}
				$feed["tweet_".$result->id] = array(
					"username" => $result->user->screen_name,
					"id" => $result->id,
					"text" => $result->text,
					"category" => $category,
					"hashtags" => $hashtags,
					"created_at" => $result->created_at,
					"geo_enabled" => $result->user->geo_enabled
				);
			}
			$results = array();
			$more = array();
			foreach($feed AS $tweet){
					$more[] = $tweet["id"];
			}
			if(!empty($more)){
				$total = count($more);
				$parts = array();
				if($total >= 61){
					$groups = array_chunk($input_array, 60);
					foreach($groups AS $group){
						$tweet_ids = implode(",",array_values($group));
						$query = array("id" => $tweet_ids);
						$parts[] = $toa->get('statuses/lookup', $query);
					}
					foreach($parts AS $tweets){
						foreach($tweets AS $tweet){
							$results["tweet_".$tweet->id] = $tweet;
						}
					}
				}else{
					$tweet_ids = implode(",",array_values($more));
					$query = array("id" => $tweet_ids);
					$tweets = $toa->get('statuses/lookup', $query);
					foreach($tweets AS $tweet){
						$results["tweet_".$tweet->id] = $tweet;
					}
				}
				foreach($results as $result){
					if($feed["tweet_".$result->id]["geo_enabled"]){
						$latitude = "";
						$longitude = "";
						if(!empty($result->coordinates) && !empty($result->coordinates->coordinates)){
							$latitude = $result->coordinates->coordinates[1];
							$longitude = $result->coordinates->coordinates[0];
						}elseif(!empty($result->place) && !empty($result->place->bounding_box)){
							$coordinates = $result->place->bounding_box->coordinates[0];
							$x=0;
							foreach($coordinates AS $ll){
								$array_locations[$x]["lat"] = $ll[1];
								$array_locations[$x]["long"] = $ll[0];
								$x++;
							}
							$latlong = tw2po_polygoncenter($array_locations);
							$latitude = $latlong[0];
							$longitude = $latlong[1];
						}
						if(!empty($result->place) && !empty($result->place->name)){
							$place_name = $result->place->name;
						}else{
							$place_name = "";
						}
						$feed["tweet_".$result->id]["latitude"] = $latitude;
						$feed["tweet_".$result->id]["longitude"] = $longitude;
						$feed["tweet_".$result->id]["place_name"] = $place_name;
					}
					if(!empty($result->extended_entities) && !empty($result->extended_entities->media) && !empty($result->extended_entities->media[0])){
						if($result->extended_entities->media[0]->type == "photo"){
							$media_type = "photo";
							$media_url = $result->extended_entities->media[0]->media_url;
							$photo = $result->extended_entities->media[0]->media_url;
						}elseif($result->extended_entities->media[0]->type == "video"){
							$media_type = "video";
							foreach($result->extended_entities->media[0]->video_info->variants AS $variant){
								if($variant->content_type == "video/webm"){
									$media_url = $variant->url;
								}
							}
							$photo = $result->extended_entities->media[0]->media_url;
						}else{
							$media_type = "";
							$media_url = "";
							$photo = "";
						}
					}else{
						$photo = "";
						$media_type = "";
						$media_url = "";
					}
					$feed["tweet_".$result->id]["media_type"] = $media_type;
					$feed["tweet_".$result->id]["media_url"] = $media_url;
					$feed["tweet_".$result->id]["photo"] = $photo;
				}
			}
			$post = array();
			$retval = array();
			$n = 0;
			$i = 0;
			$user_roles = explode(",",$this->options["user_roles"]);
			foreach($feed as $item){
				if(($this->options["require_photo"] == "Y" && $item["photo"] != "") || $this->options["require_photo"] != "Y"){
				}else{
					continue;
				}
				if($this->options["geolocation"] == "Y" && empty($item["latitude"])){
					continue;
				}
				// Check if twitter username matches one in the WP user profiles
				$user_id = $this->map_twitter_to_user($item["username"]);
				if(!$user_id==NULL){
					$user = new WP_User($user_id);
					$user_role = $user->roles[0];
					if(!in_array($user_role,$user_roles)){
						continue;
					}
					// Prepare tweet data
					if(!isset($item["latitude"])){$item["latitude"] = "";}
					if(!isset($item["longitude"])){$item["longitude"] = "";}
					if(!isset($item["place_name"])){$item["place_name"] = "";}
					if($this->options["remove_links"] == "Y"){
						$regex = "@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@";
						$item["text"] = preg_replace($regex, ' ', $item["text"]);
					}
					$post["post_title"] = $item["text"];
					if($this->options["strip_title_hashtags"] == "Y"){
						foreach($item["hashtags"] AS $hashtag){
							$post["post_title"] = str_replace("#".$hashtag,"",$post["post_title"]);
						}
					}
					$post["post_title"] = preg_replace('/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', $post["post_title"]);
					if($this->options["sanitize_title_hashtags"] == "Y"){
						$post["post_title"] = $this->sanitize_hashtags($post["post_title"], $item["hashtags"]);
					}
					$post["description"] = $item["text"];
					if($this->options["remove_links"] == "Y"){
						$post["description"] = preg_replace('/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', $post["description"]);
					}
					if($this->options["strip_content_hashtags"] == "Y"){
						foreach($item["hashtags"] AS $hashtag){
							$post["description"]= str_replace("#".$hashtag,"",$item["description"]);
						}
						$post["description"] = trim(str_replace("  ","",$post["description"]));
					}
					if($this->options["sanitize_content_hashtags"] == "Y"){
						$post["description"] = $this->sanitize_hashtags($post["description"], $item["hashtags"]);
					}
					$post["post_slug"] 				= sanitize_title_with_dashes($post["post_title"]);
					$post["user_id"]				= (int)$user_id;
					$post["date"]					= date("Y-m-d H:i:s",strtotime($item["created_at"]));
					$post["link"]					= "https://twitter.com/".$item["username"]."/status/".$item["id"];
					$post["id"]					 	= $item["id"];
					$post["hashtags"]				= $item["hashtags"];
					$post["category"]				= $item["category"];
					$post["twitter_username"]		= $item["username"];
					$post["twitter_username_link"]  = $this->create_twitter_link($item["username"]);
					$post["photo"]					= $item["photo"];
					$post["geo_enabled"]			= $item["geo_enabled"];
					$post["latitude"]				= $item["latitude"];
					$post["longitude"]				= $item["longitude"];
					$post["place_name"]				= $item["place_name"];
					$post["media_type"]				= $item["media_type"];
					$post["media_url"]				= $item["media_url"];

					// Save the tweet as a post?
					if($this->options["scheduler"] == "Y" || (!empty($_POST["tw2po_import"]) && $_POST["tw2po_import"] == "manual")){
						if($this->add_item_as_post($post)){
							if($i == 0){
								$last_tweet = $item["id"];
							}
							$i++;
						};
					};
					// Add the tweet to the return array
					$retval[] = $post;
					$n++;
				}
			}
			if($this->options["remember_id"] == "Y"){
				update_option("tw2po_last_tweet", $last_tweet);
			}
			// Return correct values depending on the $add_news_to_db boolean
			if($this->options["scheduler"] == "Y"){
				return $i;
			}else{
				return $retval;
			}
		}
	}
	// Strip hash tags (#'s) from the Tweet string
	private function sanitize_hashtags($tweet, $hashes = array()){
		if(is_array($hashes)){
			foreach ($hashes as $hash){
				$tweet = str_replace("#".$hash, "", $tweet);
			};
		};
		return $tweet;
	}

	// Create a href to the Twitter users Twitter homepage
	private function create_twitter_link($twitter_username){
		return "<a href='http://twitter.com/".$twitter_username."' target='_blank'>@".$twitter_username."</a>";
	}

	// Add Tweet as a WP (custom) post
	// Additionally adds post meta data to the post
	private function add_item_as_post($item = array()){
		// Check to see if the post already exists
		// truncate first
		$charsLength = 70;
		if (strlen($new_post_title) > $charsLength){
			$new_post_title = substr($new_post_title, 0, $charsLength);
			$new_post_title = $new_post_title . "...";
		}
		// Check if a post for this tweet already exists
		$sql = "SELECT meta_id FROM ".$this->wpdb->postmeta." WHERE meta_value = '".$item['id']."' AND meta_key = 'tw2po_status_id'";
		if($this->wpdb->get_var($sql) == NULL){
			$new_post = array();
			$new_post["post_date"]			= $item["date"]; 
			$new_post["post_title"]		= $item["post_title_filtered"];
			$new_post["post_content"]		= $item["description"];
			$new_post["post_name"]			= $item["post_slug"];
			$new_post["post_status"]		= "publish";
			$new_post["post_author"]		= (int)($item["user_id"]);
			if($this->options["taxonomy"]	!= "posts"){
				$new_post["post_type"]		= $this->options["taxonomy"];
			}else{
				$new_post["post_category"]	= $item["category"];
			}
			// Insert the post into the database and get the new post id
			$post_id = wp_insert_post($new_post);
			// Add Custom Post Type Category/Categories
			if($this->options["taxonomy"] != "posts" && !empty($item["category"])){
				wp_set_post_terms($post_id,$item["category"],$this->options["taxonomy"]."_category");
			}
			if($this->options["save_tags"] == "Y"){
			wp_set_post_tags( $post_ID, $tags, $append );
			}
			// Add Tweet Details via post meta
			add_post_meta($post_id,"tw2po_status",$item["description"],$unique=TRUE);
			add_post_meta($post_id,"tw2po_status_id",$item["id"],$unique=TRUE);
			add_post_meta($post_id,"tw2po_status_href",$item["link"],$unique=TRUE);
			add_post_meta($post_id,"tw2po_twitter_username",$item["twitter_username"],$unique=TRUE); 
			add_post_meta($post_id,"tw2po_twitter_username_link",$item["twitter_username_link"],$unique=TRUE);
			// Add Media Details via post meta
			if(!empty($item["media_type"])){
				add_post_meta($post_id,"tw2po_media_type",$item["media_type"],$unique=TRUE); 
				add_post_meta($post_id,"tw2po_media_url",$item["media_url"],$unique=TRUE); 
			}
			// Add Geo Location Details via post meta
			if(!empty($item["latitude"])){
				add_post_meta($post_id,"tw2po_lat",$item["latitude"],$unique=TRUE);
				add_post_meta($post_id,"tw2po_long",$item["longitude"],$unique=TRUE);
				$url="http://api.geonames.org/countryCodeJSON?formatted=true&lat=".$item["latitude"]."&lng=".$item["longitude"]."&username=vortexbased&style=full";
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL,$url);
				$result=curl_exec($ch);
				curl_close($ch);
				$geo_data = json_decode($result, true);
				add_post_meta($post_id,"social_country",strtolower($geo_data["countryName"]),$unique=TRUE);
			}
			// Add Place Name via post meta
			if(!empty($item["place_name"])){
					add_post_meta($post_id,"tw2po_place_name",$item["place_name"], $unique=TRUE);
			}
			// Add Codespacing Progress Map plugin support
			if($this->options["progress_map"] == "Y"){
				if(class_exists("CodespacingProgressMap") && isset($item["latitude"], $item["longitude"])){			
					$ProgressMapClass = CodespacingProgressMap::this();
					$ProgressMapClass->cspm_save_frontend_location(array(
						'post_id' => $post_id,
						'latitude' => esc_attr($item["latitude"]),
						'longitude' => esc_attr($item["longitude"]),
						'post_type' => $this->options["taxonomy"]
					));	
				}
			}
			// Add Featured Image if a photo is available
			if(!empty($item["photo"])){
				tw2po_generate_featured_image($item["photo"],$post_id);
			}
			return TRUE;
		}else{
			return FALSE;
		};
	}

	// Checks user profiles for Twitter User ID and return WP User ID
	private function map_twitter_to_user($twitter_username){
		$sql = "SELECT user_id FROM ".$this->wpdb->usermeta." WHERE meta_key='tw2po_twitter' AND meta_value='".$twitter_username."'";
		$user_id = $this->wpdb->get_var($sql);
		return $user_id;
	}

	// Get required Tweet2Post options
	private function get_options(){
		$options = array();
		$options["consumer_key"]		= get_option("tw2po_consumer_key");
		$options["consumer_secret"]		= get_option("tw2po_consumer_secret");
		$options["access_token"]		= get_option("tw2po_access_token");
		$options["access_token_secret"]	= get_option("tw2po_access_token_secret");
		$options["scheduler"]			= get_option("tw2po_scheduler");
		$options["last_tweet"]			= get_option("tw2po_last_tweet");
		$options["taxonomy"]			= get_option("tw2po_taxonomy");
		$options["master_hashtag"]		= get_option("tw2po_master_hashtag");
		$options["default_hashtags"]	= get_option("tw2po_default_hashtags");
		$options["default_category"]	= get_option("tw2po_default_category");
		$options["categories"]			= get_option("tw2po_categories");
		$options["category_hashtags"]	= get_option("tw2po_category_hashtags");
		$options["user_roles"]			= get_option("tw2po_user_roles");
		$options["remember_id"]			= get_option("tw2po_remember_id");
		$options["strip_title_hashtags"]	= get_option("tw2po_strip_title_hashtags");
		$options["strip_content_hashtags"]	= get_option("tw2po_strip_content_hashtags");
		$options["sanitize_title_hashtags"] = get_option("tw2po_sanitize_title_hashtags");
		$options["sanitize_content_hashtags"] = get_option("tw2po_sanitize_content_hashtags");
		$options["save_tags"]			= get_option("tw2po_save_tags");
		$options["deny_links"]			= get_option("tw2po_deny_links");
		$options["remove_links"]		= get_option("tw2po_remove_links");
		$options["require_photo"]		= get_option("tw2po_require_photo");
		$options["geolocation"]			= get_option("tw2po_geolocation");
		$options["progress_map"]		= get_option("tw2po_progress_map");
		$options["custom_post_type"]	= get_option("tw2po_custom_post_type");
		$options["custom_post_type_name"]	= get_option("tw2po_custom_post_type_name");
		$options["custom_post_type_name_single"]	= get_option("tw2po_custom_post_type_name_single");
		$options["custom_post_type_slug"]	= get_option("tw2po_custom_post_type_slug");
		$this->options = $options;
	}
}
