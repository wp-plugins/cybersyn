<?php
/*
 Copyright (c) 2005-2012 by CyberSEO (http://www.cyberseo.net). All Rights Reserved.
 */

if (!function_exists("get_option") || !function_exists("add_filter")) {
	die();
}
?>
<style type="text/css">
div.cybersyn-ui-tabs-panel {
	margin: 0 5px 0 0px;
	padding: .5em .9em;
	height: 11em;
	overflow: auto;
	border: 1px solid #dfdfdf;
}
.error a {
	color: #100;
}
</style>
<script type="text/javascript">
<!--
function checkAll(form) {
        for (i = 0, n = form.elements.length; i < n; i++) {
                if(form.elements[i].type == "checkbox" && !(form.elements[i].getAttribute('onclick',2))) {
                        if(form.elements[i].checked == true)
                        form.elements[i].checked = false;
                        else
                        form.elements[i].checked = true;
                }
        }
}

function changeMode() {
	var mode = document.general_settings.<?php echo CSYN_RSS_PULL_MODE; ?>.value;
	var auto = document.getElementById("auto");	
	var cron = document.getElementById("cron");
	if (mode == "auto") {
		auto.style.display = 'block';
		cron.style.display = 'none';     	
	} else {
		auto.style.display = 'none';
		cron.style.display = 'block';      
	}
}
//-->
</script>
<div class="wrap">
<?php
if (!isset($_POST["new_feed"]) && !isset($_GET["edit-feed-id"]) && !isset($_POST["check_for_updates"]) && !isset($_POST["alter_default_settings"])) {
	echo "<h2>CyberSyn</h2>\n";
?>
<div style="float:left; background-color:#FFFFCC; padding:10px 10px 10px 10px; border:1px solid #ddd;">
<a href="http://www.cyberseo.net/" target="_blank"><img class="alignright" src="<?php echo WP_PLUGIN_URL; ?>/cybersyn/images/cyberseo.png" alt="" width="251" height="125" /></a>
<h3>Looking for a professional autoblogging plugin? Upgrade to CyberSEO with 10% discount!</h3>
- The <a href="http://www.cyberseo.net/" target="_blank"><strong>CyberSEO plugin</strong></a> is the most powerful <a href="http://www.cyberseo.net/xml-syndicator/" target="_blank">XML/RSS feed syndicator</a> and <a title="Synonymizer and Rewriter" href="http://www.cyberseo.net/synonymizer-rewriter/" target="_blank">synonymizer</a>, which works in a similar way as CyberSyn, but has the following additional features:<br />
- The CyberSEO plugin is able to parse all known RSS and XML feeds such as regular blog-style RSS, Ebay feeds, XML Shop feeds, YouTube feeds, Yahoo Answers feeds, Yahoo News feeds, XML tube feeds (SmartScripts Tube and TubeAce formats), Flickr and many more.<br />
- CyberSEO is able to automatically extract full-text articles from the shortened RSS feeds.<br />
- CyberSEO allows to <a href="http://www.cyberseo.net/auto-comments/" target="_blank">generate comments</a> on your posts. These plugin can generate these coments on its own blog only, thus this is NOT a spamming tool.<br />    
- The unique "Parse WordPress archives" function allows one to syndicate all the published posts from ANY other WordPress blog with a single click.<br />
- You can spin (<a title="Synonymizer and Rewriter" href="http://www.cyberseo.net/synonymizer-rewriter/" target="_blank">synonymize and rewrite</a>) every syndicated post, shuffle its paragraphs, add any random HTML blocks as headers and footers.<br />
- You can hotlink images from the syndicated posts or store (cache) them on your server. This feature allows to bypass hotlink protection, hide the image source URL and improve performance of the blog.<br />
- CyberSEO allows one to run a full-featured automatically updating TUBE site.<br />
- CyberSEO has an unique feature which allows the PHP coders to write their own scripts for per-processing of syndicating feeds. With this feature the blog owner gains almost absolute power on syndicating content!<br />
- The CyberSEO XML/RSS Feed Syndicator includes a smart pinging algorithm which won't send out 100's of pings if you are pulling many feeds at once.<br /><br />
<strong><a href="http://www.cyberseo.net/" target="_blank">Upgrade to CyberSEO now</a> and use the following coupon code to get 10% discount: "CSYNUSER"</strong>
<br /><br />
</div>
<?php
}
if (defined("CSYN_MIN_UPDATE_TIME")) {
	$min_update_time = CSYN_MIN_UPDATE_TIME;
} else {
	$min_update_time = 0;
}
if (isset($_GET["edit-feed-id"])) {
	if ($csyn_syndicator->feedPreview ($csyn_syndicator->fixURL ($csyn_syndicator->feeds [(int) $_GET["edit-feed-id"]]['url']), true)) {
		$csyn_syndicator->showSettings (true, $csyn_syndicator->feeds [(int) $_GET["edit-feed-id"]]['options']);
	} else {
		$csyn_syndicator->showMainPage (false);
	}
} elseif (isset($_POST["update_feed_settings"])) {
	$date_min = (int) $_POST['date_min'];
	$date_max = (int) $_POST['date_max'];
	if ($date_min > $date_max) {
		$date_min = $date_max;
	}
	if (strlen(trim(stripslashes(htmlspecialchars($_POST['feed_title'], ENT_NOQUOTES)))) == 0) {
		$_POST['feed_title'] = "no name";
	}
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['title'] = trim(stripslashes(htmlspecialchars($_POST['feed_title'], ENT_NOQUOTES)));
	if ((int) $_POST['update_interval'] == 0) {
		$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['interval'] = 0;
	} else {
		$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['interval'] = max($min_update_time, abs((int) $_POST['update_interval']));
	}
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['post_status'] = $_POST['post_status'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['comment_status'] = $_POST['post_comments'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['ping_status'] = $_POST['post_pings'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['base_date'] = $_POST['post_publish_date'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['max_items'] = abs((int) $_POST['max_items']);
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['post_category'] = @$_POST['post_category'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['duplicate_check_method'] = $_POST['duplicate_check_method'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['undefined_category'] = $_POST['undefined_category'];
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['date_min'] = $date_min;
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['date_max'] = $date_max;
	$csyn_syndicator->feeds [(int) $_POST["feed_id"]]['options']['create_tags'] = @$_POST['create_tags'];
	csyn_set_option(CSYN_SYNDICATED_FEEDS, $csyn_syndicator->feeds, '', 'yes');
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["check_for_updates"])) {
	$csyn_syndicator->syndicateFeeds ($_POST["feed_ids"], false, true);
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["delete_feeds"])) {
	$csyn_syndicator->deleteFeeds ($_POST["feed_ids"], false, true);
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["delete_posts"])) {
	$csyn_syndicator->deleteFeeds ($_POST["feed_ids"], true, false);
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["delete_feeds_and_posts"])) {
	$csyn_syndicator->deleteFeeds ($_POST["feed_ids"], true, true);
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["new_feed"]) && strlen($_POST["feed_url"]) > 0) {
	if ($csyn_syndicator->feedPreview ($csyn_syndicator->fixURL ($_POST["feed_url"]), false)) {
		$options = $csyn_syndicator->global_options;
		$options['undefined_category'] = 'use_global';
		$csyn_syndicator->showSettings (true, $options);
	} else {
		$csyn_syndicator->showMainPage (true);
	}
} elseif (isset($_POST["syndicate_feed"])) {
	$date_min = (int) $_POST['date_min'];
	$date_max = (int) $_POST['date_max'];
	if ($date_min > $date_max) {
		$date_min = $date_max;
	}
	if (strlen(trim(stripslashes(htmlspecialchars($_POST['feed_title'], ENT_NOQUOTES)))) == 0) {
		$_POST['feed_title'] = "no name";
	}
	if ((int) $_POST['update_interval'] == 0) {
		$update_interval = 0;
	} else {
		$update_interval = max($min_update_time, abs((int) $_POST['update_interval']));
	}
	$csyn_syndicator->addFeed (trim(stripslashes(htmlspecialchars($_POST['feed_title'], ENT_NOQUOTES))), $_POST['feed_url'], $update_interval, @$_POST['post_category'], $_POST['post_status'], $_POST['post_comments'], $_POST['post_pings'], $_POST['post_publish_date'], $_POST['duplicate_check_method'], $_POST['undefined_category'], $date_min, $date_max, abs((int) $_POST['max_items']), @$_POST['create_tags']);
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["update_default_settings"])) {
	$pseudo_cron_interval = intval(@$_POST[CSYN_PSEUDO_CRON_INTERVAL]);
	if ($pseudo_cron_interval < 1) {
		$pseudo_cron_interval = 1;
	}
	csyn_set_option(CSYN_PSEUDO_CRON_INTERVAL, $pseudo_cron_interval, '', 'yes');
	csyn_set_option(CSYN_RSS_PULL_MODE, $_POST[CSYN_RSS_PULL_MODE], '', 'yes');
	csyn_set_option(CSYN_LINK_TO_SOURCE, @$_POST[CSYN_LINK_TO_SOURCE], '', 'yes');

	$date_min = (int) $_POST['date_min'];
	$date_max = (int) $_POST['date_max'];
	if ($date_min > $date_max) {
		$date_min = $date_max;
	}
	$csyn_syndicator->global_options ['interval'] = abs((int) $_POST['update_interval']);
	$csyn_syndicator->global_options ['post_status'] = $_POST['post_status'];
	$csyn_syndicator->global_options ['comment_status'] = $_POST['post_comments'];
	$csyn_syndicator->global_options ['ping_status'] = $_POST['post_pings'];
	$csyn_syndicator->global_options ['base_date'] = $_POST['post_publish_date'];
	$csyn_syndicator->global_options ['max_items'] = abs((int) $_POST['max_items']);
	$csyn_syndicator->global_options ['post_category'] = @$_POST['post_category'];
	$csyn_syndicator->global_options ['duplicate_check_method'] = $_POST['duplicate_check_method'];
	$csyn_syndicator->global_options ['undefined_category'] = $_POST['undefined_category'];
	$csyn_syndicator->global_options ['date_min'] = $date_min;
	$csyn_syndicator->global_options ['date_max'] = $date_max;
	$csyn_syndicator->global_options ['create_tags'] = @$_POST['create_tags'];
	csyn_set_option(CSYN_FEED_OPTIONS, $csyn_syndicator->global_options, '', 'yes');
	$csyn_syndicator->showMainPage (false);
} elseif (isset($_POST["alter_default_settings"])) {
	$csyn_syndicator->showSettings (false, $csyn_syndicator->global_options );
} else {
	$csyn_syndicator->showMainPage (false);
}
?>
</div>
<script type='text/javascript'>
<!--
changeMode();
//-->
</script>