<?php
/*
Plugin Name: CyberSyn
Version: 1.2
Author: CyberSEO.net
Author URI: http://www.cyberseo.net/
Plugin URI: http://www.cyberseo.net/cybersyn/
Description: CyberSyn is simple and lightweight but very powerful Atom/RSS syndicating plugin for WordPress.
*/

$csyn_version_id = "1.2";

define ( 'CSYN_AUTOUPDATE_INTERVAL', 300 );
define ( 'CSYN_LAST_AUTOUPDATE', 'cxxx_last_autoupdate' );
define ( 'CSYN_SYNDICATED_FEEDS', 'cxxx_syndicated_feeds' );
define ( 'CSYN_DISABLE_ENCODING', 'cxxx_disable_encoding' );
define ( 'CSYN_RSS_PULL_MODE', 'cxxx_rss_pull_mode' );
define ( 'CSYN_CRON_MAGIC', 'cxxx_cron_magic' );
define ( 'CSYN_FEED_OPTIONS', 'cxxx_feed_options' );

if (! function_exists ( "get_option" ) || ! function_exists ( "add_filter" )) {
	die ();
}
if (! @is_admin () && (time () - ( int ) get_option ( CSYN_LAST_AUTOUPDATE )) > CSYN_AUTOUPDATE_INTERVAL) {
	csyn_set_option ( CSYN_LAST_AUTOUPDATE, time (), '', 'yes' );
	$csyn_update_feeds_now = true;
} else {
	$csyn_update_feeds_now = false;
}

function csyn_get_url_scheme($url) {
	$res = parse_url ( $url );
	return $res ['scheme'];
}

function csyn_get_url_path($url) {
	$res = parse_url ( $url );
	return $res ['path'];
}

function csyn_get_url_query($url) {
	$res = parse_url ( $url );
	if (isset ( $res ['query'] )) {
		return $res ['query'];
	} else {
		return '';
	}
}

function csyn_file_get_contents($url, $asarray = false) {
	$res = false;
	if (function_exists ( 'curl_init' ) && csyn_get_url_scheme ( $url ) != "") {
		$ch = curl_init ();
		if ($ch !== false) {
			if (csyn_get_url_query ( $url ) == "" && strpos ( csyn_get_url_path ( $url ), "." ) === false && $url [strlen ( $url ) - 1] !== "/") {
				$url .= "/";
			}
			curl_setopt ( $ch, CURLOPT_URL, $url );
			curl_setopt ( $ch, CURLOPT_HEADER, 0 );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt ( $ch, CURLOPT_USERAGENT, 'cURL' );
			@curl_setopt ( $ch, CURLOPT_AUTOREFERER, true );
			$res = curl_exec ( $ch );
			curl_close ( $ch );
			if (strlen ( $res ) > 8) {
				if ($asarray) {
					$res = @explode ( "\n", trim ( $res ) );
				}
			} else {
				$res = false;
			}
		}
	}
	if ($res === false) {
		if ($asarray) {
			$res = @file ( $url );
		} else {
			$res = @file_get_contents ( $url );
		}
	}
	return $res;
}

function csyn_preset_options() {
	if (get_option ( CSYN_FEED_OPTIONS ) === false) {
		$csyn_global_feed_options = array ();
		$csyn_global_feed_options ['interval'] = 1440;
		$csyn_global_feed_options ['max_items'] = 1;
		$csyn_global_feed_options ['post_status'] = 'publish';
		$csyn_global_feed_options ['comment_status'] = 'open';
		$csyn_global_feed_options ['ping_status'] = 'closed';
		$csyn_global_feed_options ['base_date'] = 'post';
		$csyn_global_feed_options ['duplicate_check_method'] = 'guid';
		$csyn_global_feed_options ['undefined_category'] = 'use_default';
		$csyn_global_feed_options ['create_tags'] = '';
		$csyn_global_feed_options ['post_category'] = array ();
		$csyn_global_feed_options ['date_min'] = 0;
		$csyn_global_feed_options ['date_max'] = 0;
		csyn_set_option ( CSYN_FEED_OPTIONS, $csyn_global_feed_options, '', 'yes' );
	}
	if (get_option ( CSYN_SYNDICATED_FEEDS ) === false) {
		csyn_set_option ( CSYN_SYNDICATED_FEEDS, array (), '', 'yes' );
	}
	if (get_option ( CSYN_DISABLE_ENCODING ) === false) {
		csyn_set_option ( CSYN_DISABLE_ENCODING, '', '', 'yes' );
	}
	if (get_option ( CSYN_RSS_PULL_MODE ) === false) {
		csyn_set_option ( CSYN_RSS_PULL_MODE, 'auto', '', 'yes' );
	}
	if (get_option ( CSYN_CRON_MAGIC ) === false) {
		csyn_set_option ( CSYN_CRON_MAGIC, md5 ( time () ), '', 'yes' );
	}
}

class CyberSyn_Syndicator {
	var $post = array ();
	var $insideitem;
	var $tag;
	var $count;
	var $posts_found;
	var $max;
	var $current_feed = array ();
	var $current_feed_url = '';
	var $feeds = array ();
	var $update_period;
	var $feed_title;
	var $blog_charset;
	var $feed_charset;
	var $feed_charset_convert;
	var $preview;
	var $global_options = array ();
	var $edit_existing;
	var $current_category;
	var $generator;
	
	function fixURL($url) {
		$url = trim ( $url );
		if (strlen ( $url ) > 0 && strpos ( strtolower ( $url ), "http://" ) !== 0) {
			$url = "http://" . $url;
		}
		return $url;
	}
	
	function resetPost() {
		$this->post ['post_title'] = "";
		$this->post ['post_content'] = "";
		$this->post ['post_excerpt'] = "";
		$this->post ['guid'] = "";
		$this->post ['post_date'] = time ();
		$this->post ['post_date_gmt'] = time ();
		$this->post ['post_name'] = "";
		$this->post ['categories'] = array ();
		$this->post ['media_content'] = array ();
		$this->post ['media_thumbnail'] = array ();
		$this->post ['link'] = "";
	}
	
	function CyberSyn_Syndicator() {
		$this->blog_charset = get_option ( 'blog_charset' );
		$this->global_options = get_option ( CSYN_FEED_OPTIONS );
		$this->feeds = get_option ( CSYN_SYNDICATED_FEEDS );
	}
	
	function parse_w3cdtf($w3cdate) {
		if (preg_match ( "/^\s*(\d{4})(-(\d{2})(-(\d{2})(T(\d{2}):(\d{2})(:(\d{2})(\.\d+)?)?(?:([-+])(\d{2}):?(\d{2})|(Z))?)?)?)?\s*\$/", $w3cdate, $match )) {
			list ( $year, $month, $day, $hours, $minutes, $seconds ) = array ($match [1], $match [3], $match [5], $match [7], $match [8], $match [10] );
			if (is_null ( $month )) {
				$month = ( int ) gmdate ( 'm' );
			}
			if (is_null ( $day )) {
				$day = ( int ) gmdate ( 'd' );
			}
			if (is_null ( $hours )) {
				$hours = ( int ) gmdate ( 'H' );
				$seconds = $minutes = 0;
			}
			$epoch = gmmktime ( $hours, $minutes, $seconds, $month, $day, $year );
			if ($match [14] != 'Z') {
				list ( $tz_mod, $tz_hour, $tz_min ) = array ($match [12], $match [13], $match [14] );
				$tz_hour = ( int ) $tz_hour;
				$tz_min = ( int ) $tz_min;
				$offset_secs = (($tz_hour * 60) + $tz_min) * 60;
				if ($tz_mod == "+") {
					$offset_secs *= - 1;
				}
				$offset = $offset_secs;
			}
			$epoch = $epoch + $offset;
			return $epoch;
		} else {
			return - 1;
		}
	}
	
	function fixWhiteSpaces($str) {
		return preg_replace ( '/\s\s+/', ' ', preg_replace ( '/\s\"/', ' "', preg_replace ( '/\s\'/', ' \'', $str ) ) );
	}
	
	function parseFeed($feed_url) {
		global $csyn_disable_encoding;
		$this->feed_charset_convert = $this->generator = $this->feed_title = $this->tag = '';
		$this->insideitem = false;
		$this->current_feed_url = $feed_url;
		$this->posts_found = 0;
		$rss_lines = csyn_file_get_contents ( $this->current_feed_url, true );
		if (is_array ( $rss_lines ) && count ( $rss_lines ) > 0) {
			preg_match ( "/encoding[. ]?=[. ]?[\"'](.*?)[\"']/i", $rss_lines [0], $matches );
			if (isset ( $matches [1] ) && $matches [1] != "") {
				$this->feed_charset = trim ( $matches [1] );
			} else {
				$this->feed_charset = "not defined";
			}
			$xml_parser = xml_parser_create ();
			xml_set_object ( $xml_parser, $this );
			xml_set_element_handler ( $xml_parser, "startElement", "endElement" );
			xml_set_character_data_handler ( $xml_parser, "charData" );
			foreach ( $rss_lines as $line ) {
				if ($this->count >= $this->max) {
					break;
				}
				
				if ($csyn_disable_encoding != 'on' && $this->feed_charset != 'not defined' && strtolower ( $this->blog_charset ) != strtolower ( $this->feed_charset ) && function_exists ( 'mb_convert_encoding' )) {
					$line = mb_convert_encoding ( $line, $this->blog_charset, $this->feed_charset );
				}
				
				if (! xml_parse ( $xml_parser, $line . "\n" )) {
					return false;
				}
			}
			xml_parser_free ( $xml_parser );
			return $this->count;
		} else {
			return false;
		}
	}
	
	function syndicateFeeds($feed_ids, $check_time, $show_report = false) {
		global $csyn_disallow_pings;
		$this->preview = false;
		$feeds_cnt = count ( $this->feeds );
		if (count ( $feed_ids ) > 0) {
			if ($show_report) {
				echo "<div id=\"message\" class=\"updated fade\"><p>\n";
			}
			for($i = 0; $i < $feeds_cnt; $i ++) {
				if (in_array ( $i, $feed_ids )) {
					if (! $check_time || $this->getUpdateTime ( $this->feeds [$i] ) == "asap") {
						$this->feeds [$i] ['updated'] = time ();
						csyn_set_option ( CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes' );
						$this->current_feed = $this->feeds [$i];
						$this->resetPost ();
						$this->max = ( int ) $this->current_feed ['options'] ['max_items'];
						if ($show_report) {
							echo 'Syndicating <a href="' . htmlspecialchars ( $this->current_feed ['url'] ) . '" target="_blank"><strong>' . $this->current_feed ['title'] . "</strong></a>...\n";
							ob_flush ();
							flush ();
						}
						if ($this->current_feed ['options'] ['undefined_category'] == 'use_global') {
							$this->current_feed ['options'] ['undefined_category'] = $this->global_options ['undefined_category'];
						}
						$this->count = 0;
						$result = $this->parseFeed ( $this->current_feed ['url'] );
						if ($show_report) {
							if ($this->count == 1) {
								echo $this->count . " post was added";
							} else {
								echo $this->count . " posts were added";
							}
							if ($result === false) {
								echo " [!]";
							}
							echo "<br />\n";
							ob_flush ();
							flush ();
						}
					}
				}
			}
			if ($show_report) {
				echo "</p></div>\n";
			}
		}
	}
	
	function displayPost() {
		echo "<p><strong>Feed Title:</strong> " . $this->feed_title . "<br />\n";
		echo "<strong>URL:</strong> " . htmlspecialchars ( $this->current_feed_url ) . "<br />\n";
		if ($this->generator != "") {
			echo "<strong>Generator:</strong> " . $this->generator . "<br />\n";
		}
		echo "<strong>Charset Encoding:</strong> " . $this->feed_charset . "</p>\n";
		echo "<table width=\"100%\" bgcolor=\"#F0F0F0\" cellpadding=\"8\"><tr><td>\n";
		echo "<strong>Title:</strong> " . $this->fixWhiteSpaces ( trim ( $this->post ['post_title'] ) ) . "<br />\n";
		echo "<strong>Date:</strong> " . gmdate ( 'Y-m-d H:i:s', ( int ) $this->post ['post_date'] ) . "<br />\n";
		if (strlen ( trim ( $this->post ['post_content'] ) ) == 0) {
			$this->post ['post_content'] = $this->post ['post_excerpt'];
		}
		echo $this->fixWhiteSpaces ( trim ( $this->post ['post_content'] ) ) . "\n";
		
		if (sizeof ( $this->post ['media_thumbnail'] ) == sizeof ( $this->post ['media_content'] )) {
			echo '<p class="media_block">' . "\n";
			for($i = 0; $i < sizeof ( $this->post ['media_thumbnail'] ); $i ++) {
				echo '<a href="' . $this->post ['media_content'] [$i] . '"><img src="' . $this->post ['media_thumbnail'] [$i] . '"></a>';
			}
			echo "</p>";
		}
		
		echo "</td></tr></table>\n";
	}
	
	function feedPreview($feed_url, $edit_existing = false) {
		echo "<br />\n";
		$this->edit_existing = $edit_existing;
		if (! $this->edit_existing) {
			for($i = 0; $i < count ( $this->feeds ); $i ++) {
				if ($this->feeds [$i] ['url'] == $feed_url) {
					echo '<div id="message" class="error"><p><strong>This feed is already in use.</strong></p></div>' . "\n";
					return false;
				}
			}
		}
		$this->max = 1;
		$this->preview = true;
		echo "<fieldset>\n";
		echo "<h3>Feed Info and Preview</h3>\n";
		$this->resetPost ();
		$this->count = 0;
		$result = $this->parseFeed ( $feed_url );
		if (! $result) {
			echo '<div id="message" class="error"><p><strong>No feed found at</strong> <a href="http://validator.w3.org/feed/check.cgi?url=' . urlencode ( $feed_url ) . '" target="_blank">' . htmlspecialchars ( $feed_url ) . '</a></p></div>' . "\n";
		}
		echo "</fieldset>\n";
		return ($result != 0);
	}
	
	function startElement($parser, $name, $attribs) {
		$this->tag = $name;
		
		if ($this->insideitem && $name == "MEDIA:CONTENT") {
			array_push ( $this->post ['media_content'], $attribs ["URL"] );
		}
		
		if ($this->insideitem && $name == "MEDIA:THUMBNAIL") {
			array_push ( $this->post ['media_thumbnail'], $attribs ["URL"] );
		}
		
		if ($name == "ITEM" || $name == "ENTRY") {
			$this->insideitem = true;
		} elseif (! $this->insideitem && $name == "TITLE" && strlen ( trim ( $this->feed_title ) ) != 0) {
			$this->tag = "";
		}
	}
	
	function endElement($parser, $name) {
		if (($name == "ITEM" || $name == "ENTRY")) {
			$this->posts_found ++;
			if (($this->count < $this->max)) {
				if ($this->preview) {
					$this->displayPost ();
					$this->count ++;
				} else {
					$this->insertPost ();
				}
				$this->resetPost ();
				$this->insideitem = false;
			}
		} elseif ($name == "CATEGORY") {
			$category = trim ( $this->fixWhiteSpaces ( $this->current_category ) );
			if (strlen ( $category ) > 0) {
				array_push ( $this->post ['categories'], $category );
			}
			$this->current_category = "";
		} elseif ($this->count >= $this->max) {
			$this->insideitem = false;
		}
	}
	
	function charData($parser, $data) {
		if ($this->insideitem) {
			switch ($this->tag) {
				case "TITLE" :
					$this->post ['post_title'] .= $data;
					break;
				case "DESCRIPTION" :
					$this->post ['post_excerpt'] .= $data;
					break;
				case "SUMMARY" :
					$this->post ['post_excerpt'] .= $data;
					break;
				case "LINK" :
					if (trim ( $data ) != '') {
						$this->post ['link'] = trim ( $data );
					}
					break;
				case "CONTENT:ENCODED" :
					$this->post ['post_content'] .= $data;
					break;
				case "CONTENT" :
					$this->post ['post_content'] .= $data;
					break;
				case "CATEGORY" :
					$this->current_category .= trim ( $data );
					break;
				case "GUID" :
					$this->post ['guid'] .= trim ( $data );
					break;
				case "ID" :
					$this->post ['guid'] .= trim ( $data );
					break;
				case "ATOM:ID" :
					$this->post ['guid'] .= trim ( $data );
					break;
				case "DC:IDENTIFIER" :
					$this->post ['guid'] .= trim ( $data );
					break;
				case "DC:DATE" :
					$this->post ['post_date'] = $this->parse_w3cdtf ( $data );
					if ($this->post ['post_date']) {
						$this->tag = "";
					}
					break;
				case "DCTERMS:ISSUED" :
					$this->post ['post_date'] = $this->parse_w3cdtf ( $data );
					if ($this->post ['post_date']) {
						$this->tag = "";
					}
					break;
				case "PUBLISHED" :
					$this->post ['post_date'] = $this->parse_w3cdtf ( $data );
					if ($this->post ['post_date']) {
						$this->tag = "";
					}
					break;
				case "ISSUED" :
					$this->post ['post_date'] = $this->parse_w3cdtf ( $data );
					if ($this->post ['post_date']) {
						$this->tag = "";
					}
					break;
				case "PUBDATE" :
					$this->post ['post_date'] = strtotime ( $data );
					if ($this->post ['post_date']) {
						$this->tag = "";
					}
					break;
			}
		} elseif ($this->tag == "TITLE") {
			$this->feed_title .= $this->fixWhiteSpaces ( $data );
		} elseif ($this->tag == "GENERATOR") {
			$this->generator .= trim ( $data );
		}
	}
	
	function deleteFeeds($feed_ids, $delete_posts = false, $defele_feeds = false) {
		global $wpdb;
		$feeds_cnt = count ( $feed_ids );
		if ($feeds_cnt > 0) {
			if ($delete_posts) {
				$to_delete = "(";
				foreach ( $feed_ids as $item ) {
					$to_delete .= "'" . $this->feeds [$item] ['url'] . "', ";
				}
				$to_delete .= ")";
				$to_delete = str_replace ( ", )", ")", $to_delete );
				$post_ids = $wpdb->get_col ( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'cyberseo_rss_source' AND meta_value IN {$to_delete}" );
				if (count ( $post_ids ) > 0) {
					foreach ( $post_ids as $post_id ) {
						@wp_delete_post ( $post_id, true );
					}
				}
			}
			if ($defele_feeds) {
				$feeds = array ();
				$feeds_cnt = count ( $this->feeds );
				for($i = 0; $i < $feeds_cnt; $i ++) {
					if (! in_array ( $i, $feed_ids )) {
						array_push ( $feeds, $this->feeds [$i] );
					}
				}
				$this->feeds = $feeds;
				sort ( $this->feeds );
			}
			csyn_set_option ( CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes' );
		}
	}
	
	function wpdb_update($table, $data, $where) {
		global $wpdb;
		$data = add_magic_quotes ( $data );
		$bits = $wheres = array ();
		foreach ( array_keys ( $data ) as $k )
			$bits [] = "`$k` = '$data[$k]'";
		if (is_array ( $where ))
			foreach ( $where as $c => $v )
				$wheres [] = "$c = '" . $wpdb->escape ( $v ) . "'";
		else
			return false;
		return $wpdb->query ( "UPDATE $table SET " . implode ( ', ', $bits ) . ' WHERE ' . implode ( ' AND ', $wheres ) );
	}
	
	function insertPost() {
		global $wpdb, $wp_version;
		$cat_ids = $this->getCategoryIds ( $this->post ['categories'] );
		if (empty ( $cat_ids ) && $this->current_feed ['options'] ['undefined_category'] == 'drop') {
			return;
		}
		$post = array ();
		if (strlen ( $this->post ['guid'] ) < 8) {
			$components = parse_url ( $this->post ['link'] );
			$guid = 'tag:' . $components ['host'];
			if ($this->post ['post_date'] != "") {
				$guid .= '://post.' . $this->post ['post_date'];
			} else {
				$guid .= '://' . md5 ( $this->post ['link'] . '/' . $this->post ['post_title'] );
			}
		} else {
			$guid = $this->post ['guid'];
		}
		$post ['post_title'] = $this->fixWhiteSpaces ( trim ( $this->post ['post_title'] ) );
		$post ['post_name'] = sanitize_title ( $post ['post_title'] );
		$post ['guid'] = $wpdb->escape ( $guid );
		switch ($this->current_feed ['options'] ['duplicate_check_method']) {
			case "guid" :
				$result_dup = @$wpdb->query ( "SELECT ID FROM " . $wpdb->posts . " WHERE guid = \"" . $post ['guid'] . "\"" );
				break;
			case "title" :
				$result_dup = @$wpdb->query ( "SELECT ID FROM " . $wpdb->posts . " WHERE post_name = \"" . $post ['post_name'] . "\"" );
				break;
			default :
				$result_dup = @$wpdb->query ( "SELECT ID FROM " . $wpdb->posts . " WHERE guid = \"" . $post ['guid'] . "\" OR post_name = \"" . $post ['post_name'] . "\"" );
		}
		if (! $result_dup) {
			if ($this->current_feed ['options'] ['base_date'] == 'syndication') {
				$post_date = time ();
			} else {
				$post_date = (( int ) $this->post ['post_date']);
			}
			$post_date += 60 * ($this->current_feed ['options'] ['date_min'] + mt_rand ( 0, $this->current_feed ['options'] ['date_max'] - $this->current_feed ['options'] ['date_min'] ));
			$post ['post_date'] = $wpdb->escape ( gmdate ( 'Y-m-d H:i:s', $post_date + 3600 * ( int ) get_option ( 'gmt_offset' ) ) );
			$post ['post_date_gmt'] = $wpdb->escape ( gmdate ( 'Y-m-d H:i:s', $post_date ) );
			$post ['post_modified'] = $wpdb->escape ( gmdate ( 'Y-m-d H:i:s', $post_date + 3600 * ( int ) get_option ( 'gmt_offset' ) ) );
			$post ['post_modified_gmt'] = $wpdb->escape ( gmdate ( 'Y-m-d H:i:s', $post_date ) );
			$post ['post_title'] = $wpdb->escape ( $post ['post_title'] );
			$post ['post_status'] = $this->current_feed ['options'] ['post_status'];
			$post ['comment_status'] = $this->current_feed ['options'] ['comment_status'];
			$post ['ping_status'] = $this->current_feed ['options'] ['ping_status'];
			$post ['post_type'] = "post";
			$post ['post_author'] = 1;
			if (strlen ( trim ( $this->post ['post_content'] ) ) == 0) {
				$this->post ['post_content'] = $this->post ['post_excerpt'];
			}
			if (sizeof ( $this->post ['media_content'] ) > 0 && sizeof ( $this->post ['media_thumbnail'] ) == sizeof ( $this->post ['media_content'] )) {
				$this->post ['post_content'] .= '<p class="media_block">' . "\n";
				for($i = 0; $i < sizeof ( $this->post ['media_thumbnail'] ); $i ++) {
					$this->post ['post_content'] .= '<a href="' . $this->post ['media_content'] [$i] . '"><img src="' . $this->post ['media_thumbnail'] [$i] . '" class="media_thumbnail"></a>' . "\n";
				}
				$this->post ['post_content'] .= "</p>\n";
			}
			$post_content = $this->fixWhiteSpaces ( $this->post ['post_content'] );
			$post_excerpt = $this->fixWhiteSpaces ( $this->post ['post_excerpt'] );
			$post_categories = array ();
			if (is_array ( $this->current_feed ['options'] ['post_category'] )) {
				$post_categories = $this->current_feed ['options'] ['post_category'];
			}
			if (! empty ( $cat_ids )) {
				$post_categories = array_merge ( $post_categories, $cat_ids );
			} elseif ($this->current_feed ['options'] ['undefined_category'] == 'use_default' && empty ( $post_categories )) {
				array_push ( $post_categories, get_option ( 'default_category' ) );
			}
			$post_categories = array_unique ( $post_categories );
			$post ['post_category'] = $post_categories;
			$post ['post_content'] = $post_content;
			$post ['post_excerpt'] = $post_excerpt;
			if ($this->current_feed ['options'] ['create_tags'] == 'on') {
				$post ['tags_input'] = array_unique ( $this->post ['categories'] );
			}
			remove_filter ( 'content_save_pre', 'wp_filter_post_kses' );
			remove_filter ( 'excerpt_save_pre', 'wp_filter_post_kses' );
			$post_id = wp_insert_post ( $post );
			add_post_meta ( $post_id, 'cyberseo_rss_source', $this->current_feed ['url'] );
			$this->count ++;
			if (function_exists ( 'wp_set_post_categories' )) {
				wp_set_post_categories ( $post_id, $post_categories );
			} elseif (function_exists ( 'wp_set_post_cats' )) {
				wp_set_post_cats ( '1', $post_id, $post_categories );
			}
		}
	}
	
	function getCategoryIds($category_names) {
		global $wpdb;
		
		$cat_ids = array ();
		foreach ( $category_names as $cat_name ) {
			if (function_exists ( 'term_exists' )) {
				$cat_id = term_exists ( $cat_name, 'category' );
				if ($cat_id) {
					array_push ( $cat_ids, $cat_id ['term_id'] );
				} elseif ($this->current_feed ['options'] ['undefined_category'] == 'create_new') {
					$term = wp_insert_term ( $cat_name, 'category' );
					array_push ( $cat_ids, $term ['term_id'] );
				}
			} else {
				$cat_name_escaped = $wpdb->escape ( $cat_name );
				$results = $wpdb->get_results ( "SELECT cat_ID FROM $wpdb->categories WHERE (LOWER(cat_name) = LOWER('$cat_name_escaped'))" );
				
				if ($results) {
					foreach ( $results as $term ) {
						array_push ( $cat_ids, ( int ) $term->cat_ID );
					}
				} elseif ($this->current_feed ['options'] ['undefined_category'] == 'create_new') {
					if (function_exists ( 'wp_insert_category' )) {
						$cat_id = wp_insert_category ( array ('cat_name' => $cat_name ) );
					} else {
						$cat_name_sanitized = sanitize_title ( $cat_name );
						$wpdb->query ( "INSERT INTO $wpdb->categories SET cat_name='$cat_name_escaped', category_nicename='$cat_name_sanitized'" );
						$cat_id = $wpdb->insert_id;
					}
					array_push ( $cat_ids, $cat_id );
				}
			}
		}
		if ((count ( $cat_ids ) != 0)) {
			$cat_ids = array_unique ( $cat_ids );
		}
		return $cat_ids;
	}
	
	function writeNestedCategories($categories) {
		foreach ( $categories as $category ) {
			echo '<li><label for="category-', $category ['cat_ID'], '" class="selectit"><input value="', $category ['cat_ID'], '" type="checkbox" name="post_category[]" id="category-', $category ['cat_ID'], '"', ($category ['checked'] ? ' checked="checked"' : ""), '/> ', wp_specialchars ( $category ['cat_name'] ), "</label></li>\n";
			if (isset ( $category ['children'] )) {
				echo "\n<span class='cat-nest'>\n";
				write_nested_categories ( $category ['children'] );
				echo "</span>\n";
			}
		}
	}
	
	function categoryChecklist($post_id = 0, $descendents_and_self = 0, $selected_cats = false) {
		if (function_exists ( 'wp_category_checklist' )) {
			wp_category_checklist ( $post_id, $descendents_and_self, $selected_cats );
		} else {
			global $checked_categories;
			$cats = array ();
			if ($post_id) {
				$cats = wp_get_post_categories ( $post_id );
			} else {
				$cats = $selected_cats;
			}
			$checked_categories = $cats;
			$this->writeNestedCategories ( get_nested_categories ( 0 ) );
		}
	}
	
	function categoryListBox($checked, $title) {
		echo '<div id="categorydiv" class="postbox">' . "\n";
		echo '<ul id="category-tabs">' . "\n";
		echo '<li class="ui-tabs-selected">' . "\n";
		echo '<p style="font-size:smaller;font-style:bold;margin:0">' . $title . '</p>' . "\n";
		echo '</li>' . "\n";
		echo '</ul>' . "\n";
		echo '<div id="categories-all" class="cybersyn-ui-tabs-panel">' . "\n";
		echo '<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">' . "\n";
		$this->categoryChecklist ( NULL, false, $checked );
		echo '</ul>' . "\n";
		echo '</div><br />' . "\n";
		echo '</div>' . "\n";
	}
	
	function showSettings($islocal, $settings) {
		global $wp_version, $csyn_is_wp_mu;
		if ($wp_version < '2.5') {
			echo "<hr>\n";
		}
		echo '<form action="' . preg_replace ( '/\&edit-feed-id\=[0-9]+/', '', $_SERVER ['REQUEST_URI'] ) . '" method="post">' . "\n";
		?>
<table class="widefat" style="margin-top: .8em" width="100%">
	<thead>
		<tr valign="top">
                <?php
		if ($islocal) {
			echo "<th colspan=\"2\">Syndication settings for \"" . trim ( $this->feed_title ) . "\"</th>";
		} else {
			echo "<th colspan=\"2\">Default syndication settings</th>";
		}
		?>
                </tr>
	</thead>
	<tbody>
        <?php
		if ($islocal) {
			echo '<tr><td>Feed title: </td><td><input type="text" name="feed_title" size="100" value="' . (($this->edit_existing) ? $this->feeds [( int ) $_GET ["edit-feed-id"]] ['title'] : $this->feed_title) . '"></td></tr>';
		}
		?>
        <?php
		if (! $islocal) {
			?>
                <tr>
			<td>RSS Pull Mode</td>
			<td><select style="width: 160px;"
				name="<?php
			echo CSYN_RSS_PULL_MODE;
			?>"
				<?php
			echo '<option ' . ((get_option ( CSYN_RSS_PULL_MODE ) == "auto") ? 'selected ' : '') . 'value="auto">auto</option>' . "\n";
			echo '<option ' . ((get_option ( CSYN_RSS_PULL_MODE ) == "cron") ? 'selected ' : '') . 'value="cron">by cron job or manually</option>' . "\n";
			?></select> - set the RSS pulling mode. I would suggest you to do
			that by cron job once per hour or so. To do so, simple add the
			following line into your crontab:<br />
			<strong><?php
			echo "0 * * * * /usr/bin/curl --silent " . get_option ( 'siteurl' ) . "/?pull-feeds=" . get_option ( CSYN_CRON_MAGIC );
			?></strong><br />
			If you have no access to a crontab, or not sure on how to set a cron
			job, set the RSS Pull Mode to "auto".</td>
		</tr>
        <?php
		}
		?>
                <tr>
			<td width="290">
        <?php
		if ($islocal) {
			echo "Syndicate this feed to the following categories";
		} else {
			echo "Syndicate new feeds to the following categories";
		}
		?>
        </td>
			<td>
			<div id="categorydiv">
			<div id="categories-all" class="cybersyn-ui-tabs-panel">
			<ul id="categorychecklist"
				class="list:category categorychecklist form-no-clear">
                <?php
		$this->categoryChecklist ( NULL, false, $settings ['post_category'] );
		?>
                </ul>
			</div>
			</div>
			</td>
		</tr>
		<tr>
			<td>Undefined categories</td>
			<td><select name="undefined_category" size="1">
        <?php
		if ($islocal) {
			echo '<option ' . (($settings ["undefined_category"] == "use_global") ? 'selected ' : '') . 'value="use_global">Use global default syndicating settings</option>' . "\n";
		}
		echo '<option ' . (($settings ["undefined_category"] == "use_default") ? 'selected ' : '') . 'value="use_default">Post to default WordPress category</option>' . "\n";
		echo '<option ' . (($settings ["undefined_category"] == "create_new") ? 'selected ' : '') . 'value="create_new">Create new categories defined in syndicating post</option>' . "\n";
		echo '<option ' . (($settings ["undefined_category"] == "drop") ? 'selected ' : '') . 'value="drop">Do not syndicate post that doesn\'t much at least one category defined above</option>' . "\n";
		?>
                </select></td>
		</tr>
		<tr>
			<td>Create tags from category names</td>
			<td>
        <?php
		echo '<input type="checkbox" name="create_tags" ' . (($settings ['create_tags'] == 'on') ? 'checked ' : '') . '>';
		?>
        </td>
		</tr>
		<tr>
			<td>Check for duplicate posts by</td>
			<td><select name="duplicate_check_method" size="1">
        <?php
		echo '<option ' . (($settings ["duplicate_check_method"] == "guid_and_title") ? 'selected ' : '') . 'value="guid_and_title">GUID and title</option>' . "\n";
		echo '<option ' . (($settings ["duplicate_check_method"] == "guid") ? 'selected ' : '') . 'value="guid">GUID only</option>' . "\n";
		echo '<option ' . (($settings ["duplicate_check_method"] == "title") ? 'selected ' : '') . 'value="title">Title only</option>' . "\n";
		?>
                </select></td>
		</tr>
		<tr>
			<td>
        <?php
		if ($islocal) {
			echo 'Check this feed for updates every</td><td><input type="text" name="update_interval" value="' . $settings ['interval'] . '" size="4"> minutes. If you don\'t need automatic updates set this parameter to 0.';
		} else {
			echo 'Check syndicated feeds for updates every</td><td><input type="text" name="update_interval" value="' . $settings ['interval'] . '" size="4"> minutes. If you don\'t need auto updates, just set this parameter to 0.';
		}
		?>
        </td>
		</tr>
		<tr>
			<td>Maximum number of posts to be syndicated from each feed at once</td>
			<td>
                <?php
		echo '<input type="text" name="max_items" value="' . $settings ['max_items'] . '" size="3">' . " - use low values to decrease the syndication time and improve SEO of your blog.";
		?>
                </td>
		</tr>
		<tr>
			<td>Posts Status</td>
			<td><select name="post_status" size="1">
        <?php
		echo '<option ' . (($settings ["post_status"] == "publish") ? 'selected ' : '') . 'value="publish">Publish immediately</option>' . "\n";
		echo '<option ' . (($settings ["post_status"] == "pending") ? 'selected ' : '') . 'value="pending">Hold for review</option>' . "\n";
		echo '<option ' . (($settings ["post_status"] == "draft") ? 'selected ' : '') . 'value="draft">Save as drafts</option>' . "\n";
		echo '<option ' . (($settings ["post_status"] == "private") ? 'selected ' : '') . 'value="private">Save as private</option>' . "\n";
		?>
                </select></td>
		</tr>
		<tr>
			<td>Comments</td>
			<td><select name="post_comments" size="1">
        <?php
		echo '<option ' . (($settings ['comment_status'] == 'open') ? 'selected ' : '') . 'value="open">Allow comments on syndicated posts</option>' . "\n";
		echo '<option ' . (($settings ['comment_status'] == 'closed') ? 'selected ' : '') . 'value="closed">Disallow comments on syndicated posts</option>' . "\n";
		?>
                </select></td>
		</tr>
		<tr>
			<td>Pings</td>
			<td><select name="post_pings" size="1">
        <?php
		echo '<option ' . (($settings ['ping_status'] == 'open') ? 'selected ' : '') . 'value="open">Accept pings</option>' . "\n";
		echo '<option ' . (($settings ['ping_status'] == 'closed') ? 'selected ' : '') . 'value="closed">Don\'t accept pings</option>' . "\n";
		?>
                </select></td>
		</tr>
		<tr>
			<td>Base date</td>
			<td><select name="post_publish_date" size="1">
        <?php
		echo '<option ' . (($settings ['base_date'] == 'post') ? 'selected ' : '') . 'value="post">Get date from post</option>' . "\n";
		echo '<option ' . (($settings ['base_date'] == 'syndication') ? 'selected ' : '') . 'value="syndication">Use syndication date</option>' . "\n";
		?>
                </select></td>
		</tr>
		<tr>
			<td>Post date adjustment range</td>
			<td>
                <?php
		echo '[<input type="text" name="date_min" value="' . $settings ['date_min'] . '" size="6"> .. <input type="text" name="date_max" value="' . $settings ['date_max'] . '" size="6">]';
		?>
            - here you can set the syndication date adjustment range in minutes.
			This range will be used to randomly adjust the publication date for
			every aggregated post. For example, if you set the adjustment range
			as [0..60], the post dates will be increased by random value between
			0 and 60 minutes.</td>
		</tr>
	</tbody>
</table>
<?php
		echo '<div class="submit">' . "\n";
		if ($islocal) {
			if ($this->edit_existing) {
				echo '<input class="button" name="update_feed_settings" value="Update Feed Settings" type="submit">' . "\n";
				echo '<input class="button" name="cancel" value="Cancel" type="submit">' . "\n";
				echo '<input type="hidden" name="feed_id" value="' . ( int ) $_GET ["edit-feed-id"] . '">' . "\n";
			} else {
				echo '<input class="button-primary" name="syndicate_feed" value="Syndicate This Feed" type="submit">' . "\n";
				echo '<input class="button" name="cancel" value="Cancel" type="submit">' . "\n";
				echo '<input type="hidden" name="feed_url" value="' . $this->current_feed_url . '">' . "\n";
			}
		} else {
			echo '<input class="button-primary" name="update_default_settings" value="Update Default Settings" type="submit">' . "\n";
		}
		echo "</div>\n";
		
		echo "</form>\n";
	}
	
	function getUpdateTime($feed) {
		$time = time ();
		$interval = 60 * ( int ) $feed ['options'] ['interval'];
		$updated = ( int ) $feed ['updated'];
		if ($feed ['options'] ['interval'] == 0) {
			return "never";
		} elseif (($time - $updated) >= $interval) {
			return "asap";
		} else {
			return "in " . ( int ) (($interval - ($time - $updated)) / 60) . " minutes";
		}
	}
	
	function addFeed($title, $url, $interval, $post_category, $post_status, $comment_status, $ping_status, $base_date, $duplicate_check_method, $undefined_category, $date_min, $date_max, $max_items, $create_tags) {
		$feed = array ();
		$feed ['title'] = $title;
		$feed ['url'] = $url;
		$feed ['updated'] = 0;
		$feed ['options'] ['interval'] = $interval;
		$feed ['options'] ['post_category'] = $post_category;
		$feed ['options'] ['post_status'] = $post_status;
		$feed ['options'] ['comment_status'] = $comment_status;
		$feed ['options'] ['ping_status'] = $ping_status;
		$feed ['options'] ['base_date'] = $base_date;
		$feed ['options'] ['duplicate_check_method'] = $duplicate_check_method;
		$feed ['options'] ['undefined_category'] = $undefined_category;
		$feed ['options'] ['date_min'] = $date_min;
		$feed ['options'] ['date_max'] = $date_max;
		$feed ['options'] ['create_tags'] = $create_tags;
		$feed ['options'] ['max_items'] = $max_items;
		$id = array_push ( $this->feeds, $feed );
		if ((( int ) $interval) != 0) {
			$this->syndicateFeeds ( array ($id ), false );
		}
		sort ( $this->feeds );
		csyn_set_option ( CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes' );
	}
	
	function showMainPage() {
		global $wp_version;
		echo '<form action="' . $_SERVER ['REQUEST_URI'] . '" method="post">' . "\n";
		echo '<table class="form-table" width="100%">';
		echo "<tr><td align=\"right\">\n";
		echo 'New Feed URL: <input type="text" name="feed_url" value="" size="100">' . "\n";
		echo '&nbsp;<input class="button-primary" name="new_feed" value="Syndicate &raquo;" type="submit">' . "\n";
		echo "</td></tr>\n";
		echo "</table>\n";
		echo "</form>";
		if (count ( $this->feeds ) > 0) {
			echo '<form id="syndycated_feeds" action="' . $_SERVER ['REQUEST_URI'] . '" method="post">' . "\n";
			echo '<table class="widefat" style="margin-top: .5em" width="100%">' . "\n";
			echo '<thead>' . "\n";
			echo '<tr>' . "\n";
			echo '<th width="25%">Feed title</th>' . "\n";
			echo '<th width="55%">URL</th>' . "\n";
			echo '<th width="15%">Next update</th>' . "\n";
			echo '<th width="5%" align="right"><input type="checkbox" onclick="checkAll(document.getElementById(\'syndycated_feeds\'));"></th>' . "\n";
			echo "</tr>\n";
			echo '</thead>' . "\n";
			for($i = 0; $i < count ( $this->feeds ); $i ++) {
				if ($i % 2) {
					echo "<tr>\n";
				} else {
					echo '<tr class="alternate">' . "\n";
				}
				echo '<td>' . $this->feeds [$i] ['title'] . ' [<a href="' . $_SERVER ['REQUEST_URI'] . '&edit-feed-id=' . $i . '">edit</a>]</td>' . "\n";
				echo '<td>' . '<a href="' . $this->feeds [$i] ['url'] . '" target="_blank">' . htmlspecialchars ( $this->feeds [$i] ['url'] ) . '</a></td>' . "\n";
				echo "<td>" . $this->getUpdateTime ( $this->feeds [$i] ) . "</td>\n";
				echo '<th align="right"><input name="feed_ids[]" value="' . $i . '" type="checkbox"></td>' . "\n";
				echo "</tr>\n";
			}
			echo "</table>\n";
			if ($wp_version < '2.5') {
				echo "<br /><hr>\n";
			}
			?>
<div class="submit">
<table width="100%">
	<tr>
		<td>
		<div align="left"><input class="button-primary"
			name="check_for_updates" value="Pull selected feeds now!"
			type="submit"></div>
		</td>
		<td>
		<div align="right"><input class="button secondary"
			name="delete_feeds_and_posts"
			value="Delete selected feeds and syndicated posts" type="submit"> <input
			class="button secondary" name="delete_feeds"
			value="Delete selected feeds" type="submit"> <input
			class="button secondary" name="delete_posts"
			value="Delete posts syndycated from selected feeds" type="submit"></div>
		</td>
	</tr>
</table>
</div>
</form>
<?php
		}
		$this->showSettings ( false, $this->global_options );
	}
}

function csyn_set_option($option_name, $newvalue, $deprecated, $autoload) {
	if (get_option ( $option_name ) === false) {
		add_option ( $option_name, $newvalue, $deprecated, $autoload );
	} else {
		update_option ( $option_name, $newvalue );
	}
}

function csyn_main_menu() {
	if (function_exists ( 'add_options_page' )) {
		add_options_page ( __ ( 'CyberSyn' ), __ ( 'CyberSyn' ), 'manage_options', DIRNAME ( __FILE__ ) . '/cybersyn-syndicator.php' );
	}
}

function csyn_auto_update_feeds() {
	global $csyn_syndicator;
	$feed_cnt = count ( $csyn_syndicator->feeds );
	if ($feed_cnt > 0) {
		$feed_ids = range ( 0, $feed_cnt - 1 );
		$csyn_syndicator->syndicateFeeds ( $feed_ids, true );
	}
}

$csyn_syndicator = new CyberSyn_Syndicator ( );
$csyn_disable_encoding = get_option ( CSYN_DISABLE_ENCODING );
$csyn_rss_pull_mode = get_option ( CSYN_RSS_PULL_MODE );
if (isset ( $_GET ['pull-feeds'] ) && $_GET ['pull-feeds'] == get_option ( CSYN_CRON_MAGIC )) {
	$feed_cnt = count ( $csyn_syndicator->feeds );
	if ($feed_cnt > 0) {
		$feed_ids = range ( 0, $feed_cnt - 1 );
		$csyn_syndicator->syndicateFeeds ( $feed_ids, true, true );
	}
	die ();
}
if (is_admin ()) {
	csyn_preset_options ();
	add_action ( 'admin_menu', 'csyn_main_menu' );
} else {
	if ($csyn_update_feeds_now && strpos ( $csyn_rss_pull_mode, 'auto' ) !== false) {
		add_action ( 'shutdown', 'csyn_auto_update_feeds' );
	}
}
?>