<?php
/*
  Plugin Name: CyberSyn
  Version: 3.20
  Author: CyberSEO.net
  Author URI: http://www.cyberseo.net/
  Plugin URI: http://www.cyberseo.net/cybersyn/
  Description: CyberSyn is powerful, lightweight and easy to use Atom/RSS syndicating plugin for WordPress.
 */

if (!function_exists("get_option") || !function_exists("add_filter")) {
    die();
}

define('CSYN_MAX_CURL_REDIRECTS', 10);
define('CSYN_MAX_DONLOAD_ATTEMPTS', 10);
define('CSYN_FEED_OPTIONS', 'cxxx_feed_options');
define('CSYN_SYNDICATED_FEEDS', 'cxxx_syndicated_feeds');
define('CSYN_RSS_PULL_MODE', 'cxxx_rss_pull_mode');
define('CSYN_CRON_MAGIC', 'cxxx_cron_magic');
define('CSYN_PSEUDO_CRON_INTERVAL', 'cxxx_pseudo_cron_interval');
define('CSYN_DISABLE_DUPLICATION_CONTROL', 'cxxx_disable_feed_duplication_control');
define('CSYN_THEBESTSPINNER_OPTIONS', 'cxxx_thebestspinner_options');
define('CSYN_WORDAI_OPTIONS', 'cxxx_wordai_options');
define('CSYN_LINK_TO_SOURCE', 'cxxx_link_to_source');

$csyn_banner = '        <div style="background-color:#FFFFCC; padding:10px 10px 10px 10px; border:1px solid #ddd;">
            <h3>Looking for a real professional solution? Check out the CyberSEO plugin!</h3>
            <a href="http://www.cyberseo.net/" target="_blank"><img align="right" style="padding-left:20px;" src="' . WP_PLUGIN_URL . '/cybersyn/images/cyberseo.png" alt="" width="251" height="125" /></a>
            <a href="http://www.cyberseo.net/" target="_blank"><strong>The CyberSEO plugin</strong></a> is the most powerful XML/RSS feed syndicator, which is similar to CyberSyn, but has many additional features. With CyberSEO you can:<br />
            - parse all existing RSS and XML feeds such as standard RSS and Atom feeds, Ebay feeds, XML Shop feeds, YouTube feeds, Yahoo Answers feeds, Yahoo News feeds, XML tube feeds (SmartScripts Tube and TubeAce formats), Flickr and thousands of other sources;<br />
            - syndicate the feed contents as regular posts, pages and even custom post types;<br />
            - automatically generate comments on your posts. The comments will be generated on your own site only, thus this is NOT a spamming tool;<br />            
            - extract full-text articles from the shortened RSS feeds;<br />
            - use the unique "Parse WordPress archives" function to syndicate all published posts from any other WordPress blog;<br />
            - spin, synonymize and rewrite every syndicated post, shuffle its paragraphs, add any random HTML blocks as headers and footers.<br />
            - automatically generate featured images (post thumbnails);<br />
            - translate the articles to any language on the fly with any translation service of your choice (Google Translate, Yahoo! Babel Fish, Yandex Translate etc);<br />
            - run self-populating blogs, tubes, pinboards, image galleries, online magazines, Q&amp;A sites, online shops and many more;<br />
            - use your own PHP extensions to get the unlimited power on syndicating content!<br />
            <h3><a href="http://www.cyberseo.net/" target="_blank">Click here to get CyberSEO today!</a> Don\'t forget to enter this coupon code for 10% discount: "CSYNUSER"</h3>
        </div>';

function csyn_mk_post_data($data) {
    $result = '';
    foreach ($data as $key => $value) {
        $result .= $key . "=" . urlencode($value) . "&";
    }
    return $result;
}

function csyn_curl_post($url, $data, &$info) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, csyn_mk_post_data($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result = trim(curl_exec($ch));
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $result;
}

function csyn_short_str($url, $max = 0) {
    $length = strlen($url);
    if ($max > 1 && $length > $max) {
        $ninety = $max * 0.9;
        $length = $length - $ninety;
        $first = substr($url, 0, -$length);
        $last = substr($url, $ninety - $max);
        $url = $first . "&#8230;" . $last;
    }
    return $url;
}

function csyn_REQUEST_URI() {
    return strtok($_SERVER['REQUEST_URI'], "?") . "?" . strtok("?");
}

function csyn_fix_white_spaces($str) {
    return preg_replace('/\s\s+/', ' ', preg_replace('/\s\"/', ' "', preg_replace('/\s\'/', ' \'', $str)));
}

function csyn_delete_post_images($post_id) {
    $post = get_post($post_id, ARRAY_A);
    $wp_upload_dir = wp_upload_dir();

    preg_match_all('/<img(.+?)src=[\'\"](.+?)[\'\"](.*?)>/is', $post['post_content'] . $post['post_excerpt'], $matches);
    $image_urls = $matches[2];

    if (count($image_urls)) {
        $image_urls = array_unique($image_urls);
        foreach ($image_urls as $url) {
            @unlink(str_replace($wp_upload_dir['url'], $wp_upload_dir['path'], $url));
        }
    }
}

function csyn_addslash($url) {
    if ($url[strlen($url) - 1] !== "/") {
        $url .= "/";
    }
    return $url;
}

function csyn_file_get_contents($url, $as_array = false) {
    global $csyn_last_effective_url;
    if (@parse_url($url, PHP_URL_SCHEME) != "" && function_exists('curl_init')) {
        $max_redirects = CSYN_MAX_CURL_REDIRECTS;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (ini_get('open_basedir') == '' && (ini_get('safe_mode' == 'Off') || !ini_get('safe_mode'))) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
        } else {
            $base_url = $url;
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            $rch = curl_copy_handle($ch);
            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
            do {
                curl_setopt($rch, CURLOPT_URL, $url);
                curl_setopt($rch, CURLOPT_REFERER, $url);
                $header = curl_exec($rch);
                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $url = trim(array_pop($matches));
                        if (strlen($url) && substr($url, 0, 1) == '/') {
                            $url = $base_url . $url;
                        }
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$max_redirects);
            curl_close($rch);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_REFERER, $url);
        }
        $attempts = 0;
        $code = 206;
        while ($code == 206 && $attempts++ < CSYN_MAX_DONLOAD_ATTEMPTS) {
            curl_setopt($ch, CURLOPT_HEADER, false);
            $content = curl_exec($ch);
            $csyn_last_effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $c_length = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $c_download = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
            if ($c_length > $c_download) {
                $code = 206;
            } else {
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            }
        }
        curl_close($ch);

        if ($code != 200 || $c_length > $c_download) {
            $content = false;
        } elseif ($as_array) {
            $content = @explode("\n", trim($content));
        }
    }
    if (!isset($content) || $content === false) {
        if ($as_array) {
            $content = @file($url, FILE_IGNORE_NEW_LINES);
        } else {
            $content = @file_get_contents($url);
        }
        $csyn_last_effective_url = $url;
    }
    return $content;
}

function csyn_update_options(&$options) {
    $defaults = array('interval' => 1440,
        'max_items' => 1,
        'post_status' => 'publish',
        'comment_status' => 'open',
        'ping_status' => 'closed',
        'post_author' => 1,
        'base_date' => 'post',
        'duplicate_check_method' => 'guid_and_title',
        'undefined_category' => 'use_default',
        'synonymizer_mode' => '0',
        'create_tags' => '',
        'post_tags' => '',
        'post_category' => array(),
        'date_min' => 0,
        'date_max' => 0,
        'insert_media_attachments' => 'no',
        'convert_encoding' => '',
        'store_images' => '',
        'post_footer' => "",
        'include_post_footers' => '',
        'embed_videos' => '');

    $result = 0;

    foreach ($defaults as $key => $value) {
        if (!isset($options[$key])) {
            $options[$key] = $value;
            $result = 1;
        }
    }

    return $result;
}

function csyn_preset_options() {
    if (get_option(CSYN_DISABLE_DUPLICATION_CONTROL) === false) {
        csyn_set_option(CSYN_DISABLE_DUPLICATION_CONTROL, '', '', 'yes');
    }

    if (get_option(CSYN_SYNDICATED_FEEDS) === false) {
        csyn_set_option(CSYN_SYNDICATED_FEEDS, array(), '', 'yes');
    }

    if (get_option(CSYN_CRON_MAGIC) === false) {
        csyn_set_option(CSYN_CRON_MAGIC, md5(time()), '', 'yes');
    }

    if (get_option(CSYN_RSS_PULL_MODE) === false) {
        csyn_set_option(CSYN_RSS_PULL_MODE, 'auto', '', 'yes');
    }

    if (get_option(CSYN_PSEUDO_CRON_INTERVAL) === false) {
        csyn_set_option(CSYN_PSEUDO_CRON_INTERVAL, '10', '', 'yes');
    }

    if (get_option(CSYN_LINK_TO_SOURCE) === false) {
        csyn_set_option(CSYN_LINK_TO_SOURCE, 'auto', '', 'yes');
    }

    if (get_option(CSYN_THEBESTSPINNER_OPTIONS) === false) {
        $options = array(
            'username' => '',
            'password' => '',
            'protectedterms' => '',
        );
        csyn_set_option(CSYN_THEBESTSPINNER_OPTIONS, $options, '', 'yes');
    }

    if (get_option(CSYN_WORDAI_OPTIONS) === false) {
        $options = array(
            'plan' => 'standard',
            'standard_quality' => '50',
            'turing_quality' => 'Readable',
            'email' => '',
            'pass' => '',
            'nonested' => '',
            'sentence' => 'on',
            'paragraph' => '',
            'returnspin' => 'true',
            'nooriginal' => 'on',
            'protected' => '',
            'synonyms' => '',
        );
        cseo_set_option(CSYN_WORDAI_OPTIONS, $options, '', 'yes');
    }
}

function csyn_compare_files($file_name_1, $file_name_2) {
    $file1 = csyn_file_get_contents($file_name_1);
    $file2 = csyn_file_get_contents($file_name_2);
    if ($file1 && $file2) {
        return (md5($file1) == md5($file2));
    }
    return false;
}

function csyn_save_image($image_url, $preferred_name = "") {
    $wp_upload_dir = wp_upload_dir();
    if (is_writable($wp_upload_dir['path'])) {
        $image_file = csyn_file_get_contents($image_url);
        preg_match('/.*?(\.[a-zA-Z]+$)/', $image_url, $matches);
        $ext = $matches[1];
        $default_file_name = sanitize_file_name(sanitize_title($preferred_name) . $ext);
        if ($preferred_name != "" && strpos($default_file_name, "%") === false) {
            $file_name = $default_file_name;
        } else {
            $file_name = basename($image_url);
        }
        if (file_exists($wp_upload_dir['path'] . '/' . $file_name)) {
            if (csyn_compare_files($image_url, $wp_upload_dir['path'] . '/' . $file_name)) {
                return $wp_upload_dir['url'] . '/' . $file_name;
            }
            $file_name = wp_unique_filename($wp_upload_dir['path'], $file_name);
        }
        $image_path = $wp_upload_dir['path'] . '/' . $file_name;
        $local_image_url = $wp_upload_dir['url'] . '/' . $file_name;

        if (@file_put_contents($image_path, $image_file)) {
            return $local_image_url;
        }
    }
    return $image_url;
}

class CyberSyn_Syndicator {

    var $post = array();
    var $insideitem;
    var $element_tag;
    var $tag;
    var $count;
    var $failure;
    var $posts_found;
    var $max;
    var $current_feed = array();
    var $current_feed_url = "";
    var $feeds = array();
    var $update_period;
    var $feed_title;
    var $blog_charset;
    var $feed_charset;
    var $feed_charset_convert;
    var $preview;
    var $global_options = array();
    var $edit_existing;
    var $current_category;
    var $current_custom_field;
    var $current_custom_field_attr = array();
    var $generator;
    var $xml_parse_error;
    var $show_report = false;

    function fixURL($url) {
        $url = trim($url);
        if (strlen($url) > 0 && !preg_match('!^https?://.+!i', $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }

    function extractEmbeddableCode($content) {
        preg_match('/www\.youtube\.com\/watch\?v=(.+)&/', $content, $matches);
        if (isset($matches[1])) {
            $id = $matches[1];
            $video_page = csyn_file_get_contents('http://www.youtube.com/watch?v=' . $id);
            preg_match('/<div id="watch-description-text">(.*?)<\/div>/is', $video_page, $matches);
            if (isset($matches[1])) {
                return '<p><iframe class="embedded_video" width="560" height="315" src="http://www.youtube.com/embed/' . $id . '" frameborder="0" allowfullscreen></iframe></p>' . $matches[1];
            }
        }
        return $content;
    }

    function resetPost() {
        global $csyn_urls_to_check;
        $this->post ['post_title'] = "";
        $this->post ['post_content'] = "";
        $this->post ['post_excerpt'] = "";
        $this->post ['guid'] = "";
        $this->post ['post_date'] = time();
        $this->post ['post_date_gmt'] = time();
        $this->post ['post_name'] = "";
        $this->post ['categories'] = array();
        $this->post ['comments'] = array();
        $this->post ['media_content'] = array();
        $this->post ['media_thumbnail'] = array();
        $this->post ['enclosure_url'] = "";
        $this->post ['link'] = "";
        $this->post ['options'] = array();
        $csyn_urls_to_check = array();
    }

    function CyberSyn_Syndicator() {
        $this->blog_charset = strtoupper(get_option('blog_charset'));

        $this->global_options = get_option(CSYN_FEED_OPTIONS);
        if (csyn_update_options($this->global_options)) {
            csyn_set_option(CSYN_FEED_OPTIONS, $this->global_options, '', 'yes');
        }

        $this->feeds = get_option(CSYN_SYNDICATED_FEEDS);
        $changed = 0;
        for ($i = 0; $i < count($this->feeds); $i++) {
            $changed += csyn_update_options($this->feeds [$i]['options']);
        }
        if ($changed) {
            csyn_set_option(CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes');
        }
    }

    function parse_w3cdtf($w3cdate) {
        if (preg_match("/^\s*(\d{4})(-(\d{2})(-(\d{2})(T(\d{2}):(\d{2})(:(\d{2})(\.\d+)?)?(?:([-+])(\d{2}):?(\d{2})|(Z))?)?)?)?\s*\$/", $w3cdate, $match)) {
            list($year, $month, $day, $hours, $minutes, $seconds) = array($match[1], $match[3], $match[5], $match[7], $match[8], $match[10]);
            if (is_null($month)) {
                $month = (int) gmdate('m');
            }
            if (is_null($day)) {
                $day = (int) gmdate('d');
            }
            if (is_null($hours)) {
                $hours = (int) gmdate('H');
                $seconds = $minutes = 0;
            }
            $epoch = gmmktime($hours, $minutes, $seconds, $month, $day, $year);
            if ($match[14] != 'Z') {
                list($tz_mod, $tz_hour, $tz_min) = array($match[12], $match[13], $match[14]);
                $tz_hour = (int) $tz_hour;
                $tz_min = (int) $tz_min;
                $offset_secs = (($tz_hour * 60) + $tz_min) * 60;
                if ($tz_mod == "+") {
                    $offset_secs *= - 1;
                }
                $offset = $offset_secs;
            }
            $epoch = $epoch + $offset;
            return $epoch;
        } else {
            return -1;
        }
    }

    function parseFeed($feed_url) {
        $this->tag = "";
        $this->insideitem = false;
        $this->element_tag = "";
        $this->feed_title = "";
        $this->generator = "";
        $this->current_feed_url = $feed_url;
        $this->feed_charset_convert = "";
        $this->posts_found = 0;
        $this->failure = false;

        if ($this->preview) {
            $options = $this->global_options;
        } else {
            $options = $this->current_feed ['options'];
        }

        $feed_url = $this->current_feed_url;

        $rss_lines = csyn_file_get_contents($feed_url, true);

        if (is_array($rss_lines) && count($rss_lines) > 0) {
            preg_match("/encoding[. ]?=[. ]?[\"'](.*?)[\"']/i", $rss_lines[0], $matches);
            if (isset($matches[1]) && $matches[1] != "") {
                $this->feed_charset = trim($matches[1]);
            } else {
                $this->feed_charset = "not defined";
            }

            $xml_parser = xml_parser_create();
            xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, $this->blog_charset);
            xml_set_object($xml_parser, $this);
            xml_set_element_handler($xml_parser, "startElement", "endElement");
            xml_set_character_data_handler($xml_parser, "charData");

            $do_mb_convert_encoding = ($options['convert_encoding'] == 'on' && $this->feed_charset != "not defined" && $this->blog_charset != strtoupper($this->feed_charset));

            $this->xml_parse_error = 0;
            foreach ($rss_lines as $line) {
                if ($this->count >= $this->max || $this->failure) {
                    break;
                }
                if ($do_mb_convert_encoding && function_exists("mb_convert_encoding")) {
                    $line = mb_convert_encoding($line, $this->blog_charset, $this->feed_charset);
                }

                if (!xml_parse($xml_parser, $line . "\n")) {
                    $this->xml_parse_error = xml_get_error_code($xml_parser);
                    xml_parser_free($xml_parser);
                    return false;
                }
            }

            xml_parser_free($xml_parser);
            return $this->count;
        } else {
            return false;
        }
    }

    function syndicateFeeds($feed_ids, $check_time) {
        $this->preview = false;
        $feeds_cnt = count($this->feeds);
        if (count($feed_ids) > 0) {
            if ($this->show_report) {
                ob_end_flush();
                ob_implicit_flush();
                echo "<div id=\"message\" class=\"updated fade\"><p>\n";
                flush();
            }
            @set_time_limit(60 * 60);
            for ($i = 0; $i < $feeds_cnt; $i++) {
                if (in_array($i, $feed_ids)) {
                    if (!$check_time || $this->getUpdateTime($this->feeds [$i]) == "asap") {
                        $this->feeds [$i]['updated'] = time();
                        csyn_set_option(CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes');
                        $this->current_feed = $this->feeds [$i];
                        $this->resetPost();
                        $this->max = (int) $this->current_feed ['options']['max_items'];
                        if ($this->show_report) {
                            echo 'Syndicating <a href="' . htmlspecialchars($this->current_feed ['url']) . '" target="_blank"><strong>' . $this->current_feed ['title'] . "</strong></a>...\n";
                            flush();
                        }
                        if ($this->current_feed ['options']['undefined_category'] == 'use_global') {
                            $this->current_feed ['options']['undefined_category'] = $this->global_options ['undefined_category'];
                        }
                        $this->count = 0;

                        $result = $this->parseFeed($this->current_feed ['url']);

                        if ($this->show_report) {
                            if ($this->count == 1) {
                                echo $this->count . " post was added";
                            } else {
                                echo $this->count . " posts were added";
                            }
                            if ($result === false) {
                                echo " [!]";
                            }
                            echo "<br />\n";
                            flush();
                        }
                    }
                }
            }
            if (isset($save_options)) {
                csyn_set_option(CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes');
            }
            if ($this->show_report) {
                echo "</p></div>\n";
            }
        }
    }

    function displayPost() {
        echo "<p><strong>Feed Title:</strong> " . $this->feed_title . "<br />\n";
        echo "<strong>URL:</strong> " . htmlspecialchars($this->current_feed_url) . "<br />\n";
        if ($this->generator != "") {
            echo "<strong>Generator:</strong> " . $this->generator . "<br />\n";
        }
        echo "<strong>Charset Encoding:</strong> " . $this->feed_charset . "</p>\n";
        echo "<strong>Title:</strong> " . csyn_fix_white_spaces(trim($this->post ['post_title'])) . "<br />\n";
        echo "<strong>Date:</strong> " . gmdate('Y-m-d H:i:s', (int) $this->post ['post_date']) . "<br />\n";
        if (mb_strlen(trim($this->post ['post_content'])) == 0) {
            $this->post ['post_content'] = $this->post ['post_excerpt'];
        }

        echo '<div style="overflow:auto; max-height:250px; border:1px #ccc solid; background-color:white; padding:8px; margin:8px 0 8px; 0;">' . "\n";
        echo csyn_fix_white_spaces(trim($this->post ['post_content']));
        echo '</div>' . "\n";

        $attachment = "";
        if (sizeof($this->post ['media_thumbnail'])) {
            $attachment .= '<div class="media_block">' . "\n";
            for ($i = 0; $i < sizeof($this->post ['media_thumbnail']); $i++) {
                if (isset($this->post ['media_content'][$i])) {
                    $attachment .= '<a href="' . $this->post ['media_content'][$i] . '"><img src="' . $this->post ['media_thumbnail'][$i] . '" class="media_thumbnail"></a>' . "\n";
                } else {
                    $attachment .= '<img src="' . $this->post ['media_thumbnail'][$i] . '" class="media_thumbnail">' . "\n";
                }
            }
            $attachment .= "</div>";
        }
        if ($this->post ['enclosure_url'] != "" && $this->post ['link'] != "") {
            $attachment .= '<div class="media_block">' . "\n";
            $attachment .= '<a href="' . $this->post ['link'] . '"><img src="' . $this->post ['enclosure_url'] . '"></a>';
            $attachment .= "</div>";
        }
        if ($attachment != "") {
            echo "<br /><br /><strong>Attachments </strong> (adjust the \"Media Attachments\" settings to handle them):<br /><hr />\n" . $attachment . "<hr />\n";
        }
    }

    function feedPreview($feed_url, $edit_existing = false) {
        echo "<br />\n";
        $this->edit_existing = $edit_existing;
        $no_feed_dupes = get_option(CSYN_DISABLE_DUPLICATION_CONTROL) != "on";
        if (!$this->edit_existing) {
            for ($i = 0; $i < count($this->feeds); $i++) {
                if ($no_feed_dupes && $this->feeds [$i]['url'] == $feed_url) {
                    echo '<div id="message" class="error"><p><strong>This feed is already in use.</strong></p></div>' . "\n";
                    return false;
                }
            }
        }
        $this->max = 1;
        $this->preview = true;
        ?>
        <table class="widefat" width="100%">
            <thead>
                <tr valign="top">
                    <th>Feed Info and Preview</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <?php
                        $this->resetPost();
                        $this->count = 0;
                        $result = $this->parseFeed($feed_url);
                        if (!$result) {
                            echo '<div id="message"><p><strong>No feed found at</strong> <a href="http://validator.w3.org/feed/check.cgi?url=' . urlencode($feed_url) . '" target="_blank">' . htmlspecialchars($feed_url) . '</a><br />' . "\n";
                            echo 'XML parse error: ' . $this->xml_parse_error . ' (' . xml_error_string($this->xml_parse_error) . ')</p></div>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
        return ($result != 0);
    }

    function startElement($parser, $name, $attribs) {
        $this->tag = $name;

        if ($this->insideitem && $name == "MEDIA:CONTENT") {
            $this->post ['media_content'][] = $attribs["URL"];
        }

        if ($this->insideitem && $name == "MEDIA:THUMBNAIL") {
            $this->post ['media_thumbnail'][] = $attribs["URL"];
        }

        if ($name == "ENCLOSURE") {
            $this->post ['enclosure_url'] = $attribs['URL'];
        }

        if ($name == "LINK" && isset($attribs['HREF']) && isset($attribs ["REL"])) {
            if (stripos($attribs ["REL"], "enclosure") !== false) {
                $this->post['enclosure_url'] = $attribs['HREF'];
            } elseif (stripos($attribs ["REL"], "alternate") !== false && $this->post['link'] == '') {
                $this->post['link'] = $attribs['HREF'];
            }
        }

        if ($name == "ITEM" || $name == "ENTRY") {
            $this->insideitem = true;
        } elseif (!$this->insideitem && $name == "TITLE" && strlen(trim($this->feed_title)) != 0) {
            $this->tag = "";
        }
    }

    function endElement($parser, $name) {
        if (($name == "ITEM" || $name == "ENTRY")) {
            $this->posts_found++;
            if (($this->count < $this->max)) {
                if ($this->preview) {
                    $this->displayPost();
                    $this->count++;
                } else {
                    $this->insertPost();
                }
                $this->resetPost();
                $this->insideitem = false;
            }
        } elseif ($name == "CATEGORY") {
            $category = trim(csyn_fix_white_spaces($this->current_category));
            if (strlen($category) > 0) {
                $this->post ['categories'][] = $category;
            }
            $this->current_category = "";
        } elseif ($this->count >= $this->max) {
            $this->insideitem = false;
        }
    }

    function charData($parser, $data) {
        if ($this->insideitem) {
            switch ($this->tag) {
                case "TITLE":
                    $this->post ['post_title'] .= $data;
                    break;
                case "DESCRIPTION":
                    $this->post ['post_excerpt'] .= $data;
                    break;
                case "SUMMARY":
                    $this->post ['post_excerpt'] .= $data;
                    break;
                case "LINK":
                    if (trim($data) != "") {
                        $this->post ['link'] .= trim($data);
                    }
                    break;
                case "CONTENT:ENCODED":
                    $this->post ['post_content'] .= $data;
                    break;
                case "CONTENT":
                    $this->post ['post_content'] .= $data;
                    break;
                case "CATEGORY":
                    $this->current_category .= trim($data);
                    break;
                case "GUID":
                    $this->post ['guid'] .= trim($data);
                    break;
                case "ID":
                    $this->post ['guid'] .= trim($data);
                    break;
                case "ATOM:ID":
                    $this->post ['guid'] .= trim($data);
                    break;
                case "DC:IDENTIFIER":
                    $this->post ['guid'] .= trim($data);
                    break;
                case "DC:DATE":
                    $this->post ['post_date'] = $this->parse_w3cdtf($data);
                    if ($this->post ['post_date']) {
                        $this->tag = "";
                    }
                    break;
                case "DCTERMS:ISSUED":
                    $this->post ['post_date'] = $this->parse_w3cdtf($data);
                    if ($this->post ['post_date']) {
                        $this->tag = "";
                    }
                    break;
                case "PUBLISHED":
                    $this->post ['post_date'] = $this->parse_w3cdtf($data);
                    if ($this->post ['post_date']) {
                        $this->tag = "";
                    }
                    break;
                case "ISSUED":
                    $this->post ['post_date'] = $this->parse_w3cdtf($data);
                    if ($this->post ['post_date']) {
                        $this->tag = "";
                    }
                    break;
                case "PUBDATE":
                    $this->post ['post_date'] = strtotime($data);
                    if ($this->post ['post_date']) {
                        $this->tag = "";
                    }
                    break;
            }
        } elseif ($this->tag == "TITLE") {
            $this->feed_title .= csyn_fix_white_spaces($data);
        } elseif ($this->tag == "GENERATOR") {
            $this->generator .= trim($data);
        }
    }

    function deleteFeeds($feed_ids, $delete_posts = false, $defele_feeds = false) {
        global $wpdb;
        $feeds_cnt = count($feed_ids);
        if ($feeds_cnt > 0) {

            @set_time_limit(60 * 60);
            ob_end_flush();
            ob_implicit_flush();
            echo "<div id=\"message\" class=\"updated fade\"><p>\n";
            echo "Deleting. Please wait...";
            flush();

            if ($delete_posts) {
                $to_delete = "(";
                $cnt = count($feed_ids);
                for ($i = 0; $i < $cnt; $i++) {
                    $to_delete .= "'" . $this->feeds [$feed_ids[$i]]['url'] . "', ";
                }
                $to_delete .= ")";
                $to_delete = str_replace(", )", ")", $to_delete);
                $post_ids = $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'cyberseo_rss_source' AND meta_value IN {$to_delete}");
                if (count($post_ids) > 0) {
                    foreach ($post_ids as $post_id) {
                        @wp_delete_post($post_id, true);
                        echo(str_repeat(' ', 512));
                        flush();
                    }
                }
            }
            if ($defele_feeds) {
                $feeds = array();
                $feeds_cnt = count($this->feeds);
                for ($i = 0; $i < $feeds_cnt; $i++) {
                    if (!in_array($i, $feed_ids)) {
                        $feeds[] = $this->feeds [$i];
                    }
                }
                $this->feeds = $feeds;
                sort($this->feeds);
            }
            csyn_set_option(CSYN_SYNDICATED_FEEDS, $this->feeds, '', 'yes');

            echo " Done!</p></div>\n";
        }
    }

    function insertPost() {
        global $wpdb, $wp_version, $csyn_last_effective_url;

        if ($this->show_report) {
            echo(str_repeat(' ', 512));
            flush();
        }

        if (mb_strlen(trim($this->post ['post_content'])) == 0) {
            $this->post ['post_content'] = $this->post ['post_excerpt'];
        }

        $this->post['post_title'] = trim($this->post['post_title']);

        if (mb_strlen($this->post ['post_title'])) {
            $cat_ids = $this->getCategoryIds($this->post ['categories']);
            if (empty($cat_ids) && $this->current_feed ['options']['undefined_category'] == 'drop') {
                return;
            }
            $post = array();

            if (isset($this->post['tags_input']) && is_array($this->post['tags_input'])) {
                $post['tags_input'] = $this->post['tags_input'];
            } else {
                $post['tags_input'] = array();
            }

            if (mb_strlen($this->post['guid']) < 8) {
                if (strlen($this->post['link'])) {
                    $components = parse_url($this->post ['link']);
                    $guid = 'tag:' . $components['host'];
                } else {
                    $guid = 'tag:' . md5($this->post['post_content'] . $this->post['post_excerpt']);
                }
                if ($this->post['post_date'] != "") {
                    $guid .= '://post.' . $this->post['post_date'];
                } else {
                    $guid .= '://' . md5($this->post['link'] . '/' . $this->post['post_title']);
                }
            } else {
                $guid = $this->post['guid'];
            }

            $post['post_title'] = csyn_fix_white_spaces($this->post['post_title']);
            $post['post_name'] = sanitize_title($post['post_title']);
            $post['guid'] = addslashes($guid);

            switch ($this->current_feed ['options']['duplicate_check_method']) {
                case "guid":
                    $result_dup = @$wpdb->query("SELECT ID FROM " . $wpdb->posts . " WHERE guid = \"" . $post['guid'] . "\"");
                    break;
                case "title":
                    $result_dup = @$wpdb->query("SELECT ID FROM " . $wpdb->posts . " WHERE post_name = \"" . $post['post_name'] . "\"");
                    break;
                default:
                    $result_dup = @$wpdb->query("SELECT ID FROM " . $wpdb->posts . " WHERE guid = \"" . $post['guid'] . "\" OR post_name = \"" . $post['post_name'] . "\"");
            }

            if (!$result_dup) {
                if ($this->current_feed ['options']['base_date'] == 'syndication') {
                    $post_date = time();
                } else {
                    $post_date = ((int) $this->post ['post_date']);
                }
                $post_date += 60 * ($this->current_feed ['options']['date_min'] + mt_rand(0, $this->current_feed ['options']['date_max'] - $this->current_feed ['options']['date_min']));
                $post['post_date'] = addslashes(gmdate('Y-m-d H:i:s', $post_date + 3600 * (int) get_option('gmt_offset')));
                $post['post_date_gmt'] = addslashes(gmdate('Y-m-d H:i:s', $post_date));
                $post['post_modified'] = addslashes(gmdate('Y-m-d H:i:s', $post_date + 3600 * (int) get_option('gmt_offset')));
                $post['post_modified_gmt'] = addslashes(gmdate('Y-m-d H:i:s', $post_date));
                $post['post_status'] = $this->current_feed ['options']['post_status'];
                $post['comment_status'] = $this->current_feed ['options']['comment_status'];
                $post['ping_status'] = $this->current_feed ['options']['ping_status'];
                $post['post_type'] = 'post';
                $post['post_author'] = $this->current_feed ['options']['post_author'];

                $attachment = "";
                if ($this->current_feed ['options']['insert_media_attachments'] != "no") {
                    if (sizeof($this->post ['media_thumbnail'])) {
                        $attachment .= '<div class="media_block">' . "\n";
                        for ($i = 0; $i < sizeof($this->post ['media_thumbnail']); $i++) {
                            if ($this->current_feed ['options']['store_images'] == 'on') {
                                $this->post ['media_thumbnail'][$i] = csyn_save_image($this->post ['media_thumbnail'][$i], $post['post_title']);
                            }
                            if (isset($this->post ['media_content'][$i])) {
                                $attachment .= '<a href="' . $this->post ['media_content'][$i] . '"><img src="' . $this->post ['media_thumbnail'][$i] . '" class="media_thumbnail"></a>' . "\n";
                            } else {
                                $attachment .= '<img src="' . $this->post ['media_thumbnail'][$i] . '" class="media_thumbnail">' . "\n";
                            }
                        }
                        $attachment .= "</div>\n";
                    }

                    if ($this->post ['enclosure_url'] != "" && $this->post ['link'] != "") {
                        $attachment .= '<div class="media_block">' . "\n";
                        $attachment .= '<a href="' . $this->post ['link'] . '"><img src="' . $this->post ['enclosure_url'] . '" class="media_thumbnail"></a>' . "\n";
                        $attachment .= "</div>\n";
                    }
                }

                $attachment_status = $this->current_feed['options']['insert_media_attachments'];

                $post['post_content'] = $this->post['post_content'];
                $post['post_excerpt'] = $this->post['post_excerpt'];

                if ($this->current_feed ['options']['embed_videos'] == 'on') {
                    $post['post_excerpt'] = $post['post_content'] = $this->extractEmbeddableCode($post['post_content']);
                }

                if ($this->current_feed ['options']['store_images'] == 'on') {
                    preg_match_all('/<img(.+?)src=[\'\"](.+?)[\'\"](.*?)>/is', $post['post_content'] . $post['post_excerpt'], $matches);
                    $image_urls = array_unique($matches[2]);
                    $home = get_option('home');
                    for ($i = 0; $i < count($image_urls); $i++) {
                        if (strpos($image_urls[$i], $home) === false) {
                            $new_image_url = csyn_save_image($image_urls[$i], $post['post_title']);
                            $post['post_content'] = str_replace($image_urls[$i], $new_image_url, $post['post_content']);
                            $post['post_excerpt'] = str_replace($image_urls[$i], $new_image_url, $post['post_excerpt']);
                            if ($this->show_report) {
                                echo(str_repeat(' ', 256));
                                flush();
                            }
                        }
                    }
                }

                $inc_footerss = $this->current_feed ['options']['include_post_footers'];

                $title = $post['post_title'];
                $content = csyn_fix_white_spaces($post['post_content']);
                $excerpt = csyn_fix_white_spaces($post['post_excerpt']);
                $divider = ' 888011000110888 ';
                $packet = csyn_spin_content($title . $divider . $content . $divider . $excerpt);
                if (substr_count($packet, $divider) == 2) {
                    list($title, $content, $excerpt) = explode($divider, $packet);
                }
                $post['post_title'] = addslashes($title);
                $post['post_content'] = addslashes(csyn_touch_post_content($content, $attachment, $attachment_status));
                $post['post_excerpt'] = addslashes(csyn_touch_post_content($excerpt, $attachment, $attachment_status, $inc_footerss));

                $post_categories = array();
                if (is_array($this->current_feed ['options']['post_category'])) {
                    $post_categories = $this->current_feed ['options']['post_category'];
                }

                if (!empty($cat_ids)) {
                    $post_categories = array_merge($post_categories, $cat_ids);
                } elseif ($this->current_feed ['options']['undefined_category'] == 'use_default' && empty($post_categories)) {
                    $post_categories[] = get_option('default_category');
                }

                $post_categories = array_unique($post_categories);

                $post['post_category'] = $post_categories;

                if ($this->current_feed ['options']['create_tags'] == 'on') {
                    $post['tags_input'] = array_merge($post['tags_input'], $this->post ['categories']);
                }

                if ($this->current_feed ['options']['post_tags'] != '') {
                    $tags = explode(',', $this->current_feed ['options']['post_tags']);
                    $post['tags_input'] = array_merge($post['tags_input'], $tags);
                }

                $post['tags_input'] = array_unique($post['tags_input']);

                remove_filter('content_save_pre', 'wp_filter_post_kses');
                remove_filter('excerpt_save_pre', 'wp_filter_post_kses');

                $post_id = wp_insert_post($post, true);

                if (is_wp_error($post_id) && $this->show_report) {
                    $this->failure = true;
                    echo "<br /><b>Error:</b> " . $post_id->get_error_message($post_id->get_error_code()) . "<br />\n";
                } else {

                    $this->count++;
                    $this->failure = false;

                    add_post_meta($post_id, 'cyberseo_rss_source', $this->current_feed ['url']);
                    add_post_meta($post_id, 'cyberseo_post_link', $this->post ['link']);

                    if (version_compare($wp_version, '3.0', '<')) {
                        if (function_exists('wp_set_post_categories')) {
                            wp_set_post_categories($post_id, $post_categories);
                        } elseif (function_exists('wp_set_post_cats')) {
                            wp_set_post_cats('1', $post_id, $post_categories);
                        }
                    }
                }
            }
        }
    }

    function getCategoryIds($category_names) {
        global $wpdb;

        $cat_ids = array();
        foreach ($category_names as $cat_name) {
            if (function_exists('term_exists')) {
                $cat_id = term_exists($cat_name, 'category');
                if ($cat_id) {
                    $cat_ids[] = $cat_id['term_id'];
                } elseif ($this->current_feed ['options']['undefined_category'] == 'create_new') {
                    $term = wp_insert_term($cat_name, 'category');
                    $cat_ids[] = $term['term_id'];
                }
            } else {
                $cat_name_escaped = addslashes($cat_name);
                $results = $wpdb->get_results("SELECT cat_ID FROM $wpdb->categories WHERE (LOWER(cat_name) = LOWER('$cat_name_escaped'))");

                if ($results) {
                    foreach ($results as $term) {
                        $cat_ids[] = (int) $term->cat_ID;
                    }
                } elseif ($this->current_feed ['options']['undefined_category'] == 'create_new') {
                    if (function_exists('wp_insert_category')) {
                        $cat_id = wp_insert_category(array('cat_name' => $cat_name));
                    } else {
                        $cat_name_sanitized = sanitize_title($cat_name);
                        $wpdb->query("INSERT INTO $wpdb->categories SET cat_name='$cat_name_escaped', category_nicename='$cat_name_sanitized'");
                        $cat_id = $wpdb->insert_id;
                    }
                    $cat_ids[] = $cat_id;
                }
            }
        }
        if ((count($cat_ids) != 0)) {
            $cat_ids = array_unique($cat_ids);
        }
        return $cat_ids;
    }

    function categoryChecklist($post_id = 0, $descendents_and_self = 0, $selected_cats = false) {
        wp_category_checklist($post_id, $descendents_and_self, $selected_cats);
    }

    function categoryListBox($checked, $title) {
        echo '<div id="categorydiv" class="postbox">' . "\n";
        echo '<ul id="category-tabs">' . "\n";
        echo '<li class="ui-tabs-selected">' . "\n";
        echo '<p>' . $title . '</p>' . "\n";
        echo '</li>' . "\n";
        echo '</ul>' . "\n";

        echo '<div id="categories-all" class="cybersyn-ui-tabs-panel">' . "\n";
        echo '<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">' . "\n";
        $this->categoryChecklist(NULL, false, $checked);
        echo '</ul>' . "\n";
        echo '</div><br />' . "\n";
        echo '</div>' . "\n";
    }

    function showSettings($islocal, $settings) {
        global $wp_version, $wpdb, $csyn_bs_options;
        if (version_compare($wp_version, '2.5', '<')) {
            echo "<hr>\n";
        }
        echo '<form action="' . preg_replace('/\&edit-feed-id\=[0-9]+/', '', csyn_REQUEST_URI()) . '" method="post">' . "\n";
        ?>
        <table class="widefat" style="margin-top: .8em" width="100%">
            <thead>
                <tr valign="top">
                    <?php
                    if ($islocal) {
                        echo "<th colspan=\"2\">Syndication settings for \"" . trim($this->feed_title) . "\"</th>";
                    } else {
                        echo "<th colspan=\"2\">Default syndication settings</th>";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($islocal) { ?>
                    <tr>
                        <td>Feed title:</td>
                        <td>
                            <input type="text" name="feed_title" size="132" value="<?php echo ($this->edit_existing) ? $this->feeds [(int) $_GET["edit-feed-id"]]['title'] : $this->feed_title; ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Feed URL</td>
                        <td><input type="text" name="new_feed_url" size="132" value="<?php echo htmlspecialchars($this->current_feed_url); ?>"<?php
            if (!$this->edit_existing) {
                echo ' disabled';
            }
                    ?>>
                        </td>
                    </tr>
                <?php } ?>
                <tr>
                    <td width="280"><?php
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
                                <ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
                                    <?php
                                    $this->categoryChecklist(NULL, false, $settings['post_category']);
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td>Content spinner</td>
                    <td>
                        <?php
                        if (strlen($csyn_bs_options['username']) && strlen($csyn_bs_options['password'])) {
                            $status = '';
                            $hint = '';
                        } else {
                            $status = 'disabled';
                            $settings["synonymizer_mode"] = 0;
                            $hint = 'In order to use <a href="http://www.cyberseo.net/partners/thebestspinner.php" target="_blank">The Best Spinner</a>, you must enter your account info first.';
                        }
                        ?>
                        <select name="synonymizer_mode" size="1" <?php echo $status; ?>>    
                            <?php
                            echo '<option ' . (($settings["synonymizer_mode"] == "0") ? 'selected ' : '') . 'value="0">Disabled</option>' . "\n";
                            echo '<option ' . (($settings["synonymizer_mode"] == "11") ? 'selected ' : '') . 'value="11">Use The Best Spinner</option>' . "\n";
                            echo '<option ' . (($settings["synonymizer_mode"] == "14") ? 'selected ' : '') . 'value="14">Use WordAi</option>' . "\n";
                            ?> 
                        </select>
                        <?php echo $hint; ?>
                    </td>
                </tr>

                <tr>
                    <td>Attribute all posts to the following user</td>
                    <td><select name="post_author" size="1">
                            <?php
                            $wp_user_search = $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID");
                            foreach ($wp_user_search as $userid) {
                                echo '<option ' . (($settings["post_author"] == $userid->ID) ? 'selected ' : '') . 'value="' . $userid->ID . '">' . $userid->display_name . "\n";
                            }
                            ?>
                        </select></td>
                </tr>

                <tr>
                    <td>Undefined categories</td>
                    <td><select name="undefined_category" size="1">
                            <?php
                            if ($islocal) {
                                echo '<option ' . (($settings["undefined_category"] == "use_global") ? 'selected ' : '') . 'value="use_global">Use RSS/Atom default settings</option>' . "\n";
                            }
                            echo '<option ' . (($settings["undefined_category"] == "use_default") ? 'selected ' : '') . 'value="use_default">Post to default WordPress category</option>' . "\n";
                            echo '<option ' . (($settings["undefined_category"] == "create_new") ? 'selected ' : '') . 'value="create_new">Create new categories defined in syndicating post</option>' . "\n";
                            echo '<option ' . (($settings["undefined_category"] == "drop") ? 'selected ' : '') . 'value="drop">Do not syndicate post that doesn\'t match at least one category defined above</option>' . "\n";
                            ?>
                        </select></td>
                </tr>
                <tr>
                    <td>Create tags from category names</td>
                    <td><?php
                    echo '<input type="checkbox" name="create_tags" ' . (($settings['create_tags'] == 'on') ? 'checked ' : '') . '>';
                            ?>
                    </td>
                </tr>
                <tr>
                    <td>Post tags (separate with commas)</td>
                    <td><?php
                echo '<input type="text" name="post_tags" value="' . stripslashes($settings['post_tags']) . '" size="60">';
                            ?>
                    </td>
                </tr>
                <tr>
                    <td>Check for duplicate posts by</td>
                    <td><select name="duplicate_check_method" size="1">
                            <?php
                            echo '<option ' . (($settings["duplicate_check_method"] == "guid_and_title") ? 'selected ' : '') . 'value="guid_and_title">GUID and title</option>' . "\n";
                            echo '<option ' . (($settings["duplicate_check_method"] == "guid") ? 'selected ' : '') . 'value="guid">GUID only</option>' . "\n";
                            echo '<option ' . (($settings["duplicate_check_method"] == "title") ? 'selected ' : '') . 'value="title">Title only</option>' . "\n";
                            ?>
                        </select></td>
                </tr>
                <tr>
                    <td><?php
                    if ($islocal) {
                        echo 'Check this feed for updates every</td><td><input type="text" name="update_interval" value="' . $settings['interval'] . '" size="4"> minutes. If you don\'t need automatic updates set this parameter to 0.';
                    } else {
                        echo 'Check syndicated feeds for updates every</td><td><input type="text" name="update_interval" value="' . $settings['interval'] . '" size="4"> minutes. If you don\'t need auto updates, just set this parameter to 0.';
                    }
                    if (defined("CSYN_MIN_UPDATE_TIME")) {
                        echo " <strong>This option is limited by Administrator:<strong> the update period can not be less than " . CSYN_MIN_UPDATE_TIME . " minutes.";
                    }
                            ?>
                    </td>
                </tr>
                <tr>
                    <td>Maximum number of posts to be syndicated from each feed at once</td>
                    <td><?php
                echo '<input type="text" name="max_items" value="' . $settings['max_items'] . '" size="3">' . " - use low values to decrease the syndication time and improve SEO of your blog.";
                            ?>
                    </td>
                </tr>
                <tr>
                    <td>Posts status</td>
                    <td><select name="post_status" size="1">
                            <?php
                            echo '<option ' . (($settings["post_status"] == "publish") ? 'selected ' : '') . 'value="publish">Publish immediately</option>' . "\n";
                            echo '<option ' . (($settings["post_status"] == "pending") ? 'selected ' : '') . 'value="pending">Hold for review</option>' . "\n";
                            echo '<option ' . (($settings["post_status"] == "draft") ? 'selected ' : '') . 'value="draft">Save as draft</option>' . "\n";
                            echo '<option ' . (($settings["post_status"] == "private") ? 'selected ' : '') . 'value="private">Save as private</option>' . "\n";
                            ?>
                        </select></td>
                </tr>
                <tr>
                    <td>Comments</td>
                    <td><select name="post_comments" size="1">
                            <?php
                            echo '<option ' . (($settings['comment_status'] == 'open') ? 'selected ' : '') . 'value="open">Allow comments on syndicated posts</option>' . "\n";
                            echo '<option ' . (($settings['comment_status'] == 'closed') ? 'selected ' : '') . 'value="closed">Disallow comments on syndicated posts</option>' . "\n";
                            ?>
                        </select></td>
                </tr>
                <tr>
                    <td>Pings</td>
                    <td><select name="post_pings" size="1">
                            <?php
                            echo '<option ' . (($settings['ping_status'] == 'open') ? 'selected ' : '') . 'value="open">Accept pings</option>' . "\n";
                            echo '<option ' . (($settings['ping_status'] == 'closed') ? 'selected ' : '') . 'value="closed">Don\'t accept pings</option>' . "\n";
                            ?>
                        </select></td>
                </tr>
                <tr>
                    <td>Base date</td>
                    <td><select name="post_publish_date" size="1">
                            <?php
                            echo '<option ' . (($settings['base_date'] == 'post') ? 'selected ' : '') . 'value="post">Get date from post</option>' . "\n";
                            echo '<option ' . (($settings['base_date'] == 'syndication') ? 'selected ' : '') . 'value="syndication">Use syndication date</option>' . "\n";
                            ?>
                        </select></td>
                </tr>

                <tr>
                    <td>Media attachments</td>
                    <td><select name="insert_media_attachments" size="1">
                            <?php
                            echo '<option ' . (($settings["insert_media_attachments"] == "no") ? 'selected ' : '') . 'value="no">Do not insert attachments</option>' . "\n";
                            echo '<option ' . (($settings["insert_media_attachments"] == "top") ? 'selected ' : '') . 'value="top">Insert attachments at the top of the post</option>' . "\n";
                            echo '<option ' . (($settings["insert_media_attachments"] == "bottom") ? 'selected ' : '') . 'value="bottom">Insert attachments at the bottom of the post</option>' . "\n";
                            ?>
                        </select> - if enabled CyberSyn syndicator will insert media attachments (if available) into the aggregating post. The
                        following types of attachments are supported: <strong>&lt;media:content&gt;</strong>,
                        <strong>&lt;media:thumbnail&gt;</strong> and <strong>&lt;enclosure&gt;</strong>
                        (type "image" only) All the aggregated images will contain <strong>class="media_thumbnail"</strong>
                        in the <strong>&lt;img&gt;</strong> tag.</td>
                </tr>

                <tr>
                    <td>Convert character encoding</td>
                    <td><?php
                    echo '<input type="checkbox" name="convert_encoding" ' . (($settings['convert_encoding'] == 'on') ? 'checked ' : '') . '> - enables character encoding conversion.
                This option might be useful when parsing XML/RSS feeds in national charsets different than UTF-8.';
                            ?>
                    </td>
                </tr>

                <tr>
                    <td>Store images locally</td>
                    <td><?php
                echo '<input type="checkbox" name="store_images" ' . (($settings['store_images'] == 'on') ? 'checked ' : '') . '> - if enabled, all images from the syndicating feeds will be copied into the default uploads folder of this blog. Make sure that your /wp-content/uploads folder is writable.';
                            ?>
                    </td>
                </tr>

                <tr>
                    <td>Post date adjustment range</td>
                    <td><?php
                echo '[<input type="text" name="date_min" value="' . $settings['date_min'] . '" size="6"> .. <input type="text" name="date_max" value="' . $settings['date_max'] . '" size="6">]';
                            ?>
                        - here you can set the syndication date adjustment range in minutes. This range will be used to randomly adjust the publication date for every aggregated post. For example, if you set
                        the adjustment range as [0..60], the post dates will be increased by a random value between 0 and 60 minutes.
                    </td>
                </tr>

                <tr>
                    <td>Post footer</td>
                    <td><?php
                echo '<input type="text" name="post_footer" value="' . htmlspecialchars(stripslashes($settings['post_footer']), ENT_QUOTES) . '" size="60">';
                echo ' - the HTML code wich will be inserted into the bottom of each syndicated post.' . "\n";
                            ?>
                    </td>
                </tr>

                <tr>
                    <td>Insert post footer into excerpts</td>
                    <td><?php
                echo '<input type="checkbox" name="include_post_footers" ' . (($settings['include_post_footers'] == 'on') ? 'checked ' : '') . '> - enable this option if you want to insert the post footer into the post excerpt.';
                            ?>
                    </td>
                </tr>

                <tr>
                    <td>Embed videos</td>
                    <td><?php
                echo '<input type="checkbox" name="embed_videos" ' . (($settings['embed_videos'] == 'on') ? 'checked ' : '') . '> - the embeddable videos will be automatically extracted and inserted into the posts. Feed sources supported: YouTube only.';
                            ?>
                    </td>
                </tr>                

            </tbody>
        </table>
        <?php
        echo '<div class="submit">' . "\n";
        if ($islocal) {
            if ($this->edit_existing) {
                echo '<input class="button-primary" name="update_feed_settings" value="Update Feed Settings" type="submit">' . "\n";
                echo '<input class="button" name="cancel" value="Cancel" type="submit">' . "\n";
                echo '<input type="hidden" name="feed_id" value="' . (int) $_GET["edit-feed-id"] . '">' . "\n";
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
        $time = time();
        $interval = 60 * (int) $feed['options']['interval'];
        $updated = (int) $feed['updated'];
        if ($feed['options']['interval'] == 0) {
            return "never";
        } elseif (($time - $updated) >= $interval) {
            return "asap";
        } else {
            return "in " . (int) (($interval - ($time - $updated)) / 60) . " minutes";
        }
    }

    function showMainPage($showsettings = true) {
        global $wp_version, $csyn_banner;
        echo $csyn_banner;
        echo '<form action="' . csyn_REQUEST_URI() . '" method="post">' . "\n";
        echo '<table class="form-table" width="100%">';
        echo "<tr><td align=\"right\">\n";
        echo 'New Feed URL: <input type="text" name="feed_url" value="" size="100">' . "\n";
        echo '&nbsp;<input class="button-primary" name="new_feed" value="Syndicate &raquo;" type="submit">' . "\n";
        echo "</td></tr>\n";
        echo "</table>\n";
        echo "</form>";
        echo '<form id="syndycated_feeds" action="' . csyn_REQUEST_URI() . '" method="post">' . "\n";
        if (count($this->feeds) > 0) {
            echo '<table class="widefat" style="margin-top: .5em" width="100%">' . "\n";
            echo '<thead>' . "\n";
            echo '<tr>' . "\n";
            echo '<th width="3%" align="center"><input type="checkbox" onclick="checkAll(document.getElementById(\'syndycated_feeds\'));"></th>' . "\n";
            echo '<th width="25%">Feed title</th>' . "\n";
            echo '<th width="50%">URL</th>' . "\n";
            echo '<th width="10%">Next update</th>' . "\n";
            echo '<th width="12%">Last update</th>' . "\n";
            echo "</tr>\n";
            echo '</thead>' . "\n";
            for ($i = 0; $i < count($this->feeds); $i++) {
                if ($i % 2) {
                    echo "<tr>\n";
                } else {
                    echo '<tr class="alternate">' . "\n";
                }
                echo '<td align="center"><input name="feed_ids[]" value="' . $i . '" type="checkbox"></td>' . "\n";
                echo '<td>' . $this->feeds [$i]['title'] . ' [<a href="' . csyn_REQUEST_URI() . '&edit-feed-id=' . $i . '">edit</a>]</td>' . "\n";
                echo '<td>' . '<a href="' . $this->feeds [$i]['url'] . '" target="_blank">' . csyn_short_str(htmlspecialchars($this->feeds [$i]['url']), 100) . '</a></td>' . "\n";
                echo "<td>" . $this->getUpdateTime($this->feeds [$i]) . "</td>\n";
                $last_update = $this->feeds [$i]['updated'];
                if ($last_update) {
                    echo "<td>" . intval((time() - $last_update) / 60) . " minutes ago</td>\n";
                } else {
                    echo "<td> - </td>\n";
                }
                echo "</tr>\n";
            }
            echo "</table>\n";
            if (version_compare($wp_version, '2.5', '<')) {
                echo "<br /><hr>\n";
            }
        }
        ?>
        <div class="submit">
            <table width="100%">
                <tr>
                    <td>
                        <div align="left">
                            <input class="button-primary" name="check_for_updates" value="Pull selected feeds now!" type="submit">
                        </div>
                    </td>
                    <td>
                        <div align="right">
                            <input class="button secondary" name="delete_feeds_and_posts" value="Delete selected feeds and syndicated posts" type="submit">
                            <input class="button secondary" name="delete_feeds" value="Delete selected feeds" type="submit">
                            <input class="button secondary" name="delete_posts" value="Delete posts syndycated from selected feeds" type="submit">
                        </div>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td><br />
                        <div align="right">
                            <input class="button secondary" name="alter_default_settings" value="Alter default settings" type="submit">
                        </div>
                    </td>
                </tr>
            </table>
        </form>
        </div>
        <?php
        if ($showsettings) {
            $this->showSettings(false, $this->global_options);
        }
    }

}

function csyn_set_option($option_name, $newvalue, $deprecated, $autoload) {
    if (get_option($option_name) === false) {
        add_option($option_name, $newvalue, $deprecated, $autoload);
    } else {
        update_option($option_name, $newvalue);
    }
}

function csyn_thebestspinner($content) {
    global $csyn_bs_options;

    if (strlen($csyn_bs_options['username']) && strlen($csyn_bs_options['password'])) {
        $url = 'http://thebestspinner.com/api.php';
        $data = array();
        $data['action'] = 'authenticate';
        $data['format'] = 'php';
        $data['username'] = $csyn_bs_options['username'];
        $data['password'] = $csyn_bs_options['password'];
        $result = unserialize(csyn_curl_post($url, $data, $info));
        if (isset($result['success']) && $result['success'] == 'true') {
            $data['session'] = $result['session'];
            $data['action'] = 'rewriteText';
            $data['protectedterms'] = $csyn_bs_options['protectedterms'];
            $data['text'] = $content;
            $result = unserialize(csyn_curl_post($url, $data, $info));
            if ($result['success'] == 'true') {
                return $result['output'];
            }
        }
    }
    return $content;
}

function csyn_wordai($content) {
    global $csyn_wa_options;

    $text = urlencode($content);
    if ($csyn_wa_options['plan'] == 'turing') {
        $ch = curl_init('http://wordai.com/users/turing-api.php');
        $quality = $csyn_wa_options['turing_quality'];
    } else {
        $ch = curl_init('http://wordai.com/users/regular-api.php');
        $quality = $csyn_wa_options['standard_quality'];
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "s=$text&quality=$quality&email=" . $csyn_wa_options['email'] . "&pass=" . $csyn_wa_options['pass'] .
            "&nonested=" . $csyn_wa_options['nonested'] . "&sentence=" . $csyn_wa_options['sentence'] . "&paragraph=" . $csyn_wa_options['returnspin'] .
            "&returnspin=" . $csyn_wa_options['returnspin'] . "&nooriginal=" . $csyn_wa_options['nooriginal'] .
            "&protected=" . urlencode($csyn_wa_options['protected']) . "&synonyms=" . urlencode($csyn_wa_options['synonyms']) . "&output=json");
    $response = trim(curl_exec($ch));
    curl_close($ch);

    $api_response_interpreted = json_decode($response, true);
    if ($api_response_interpreted['status'] == 'Success') {
        return $api_response_interpreted['text'];
    }

    return $content;
}

function csyn_spin_content($content) {
    global $csyn_syndicator;

    if (count($csyn_syndicator->current_feed)) {
        $synonymizer_mode = $csyn_syndicator->current_feed ['options']['synonymizer_mode'];
    } else {
        $synonymizer_mode = $csyn_syndicator->global_options ['synonymizer_mode'];
    }

    if ($synonymizer_mode == '11') {
        $content = csyn_thebestspinner($content);
    } elseif ($synonymizer_mode == '14') {
        $content = csyn_wordai($content);
    }

    return $content;
}

function csyn_parse_special_words($content) {
    global $csyn_syndicator;
    return str_replace('####post_link####', $csyn_syndicator->post ['link'], $content);
}

function csyn_touch_post_content($content, $attachment = "", $attachment_status = "no", $inc_footers = true) {
    global $csyn_syndicator;

    if ($attachment != "") {
        if ($attachment_status == "top") {
            $content = $attachment . $content;
        } elseif ($attachment_status == "bottom") {
            $content .= $attachment;
        }
    }

    $footer = stripslashes($csyn_syndicator->current_feed ['options']['post_footer']);

    if (strlen($footer)) {
        $content .= csyn_parse_special_words(trim($footer));
    }

    return $content;
}

function csyn_main_menu() {
    if (function_exists('add_menu_page')) {
        add_menu_page('CyberSyn', 'CyberSyn', 'add_users', DIRNAME(__FILE__) . '/cybersyn-options.php');
        add_submenu_page(DIRNAME(__FILE__) . '/cybersyn-options.php', 'CyberSyn RSS/Atom Syndicator', 'RSS/Atom Syndicator', 'add_users', DIRNAME(__FILE__) . '/cybersyn-syndicator.php');
        add_submenu_page(DIRNAME(__FILE__) . '/cybersyn-options.php', 'CyberSyn The Best Spinner Module', 'The Best Spinner', 'add_users', DIRNAME(__FILE__) . '/cybersyn-bs.php');
        add_submenu_page(DIRNAME(__FILE__) . '/cybersyn-options.php', 'CyberSyn WordAi Module', 'WordAi', 'add_users', DIRNAME(__FILE__) . '/cybersyn-wa.php');
    }
}

function csyn_update_feeds() {
    global $csyn_syndicator;
    $feed_cnt = count($csyn_syndicator->feeds);
    if ($feed_cnt > 0) {
        $feed_ids = range(0, $feed_cnt - 1);
        $csyn_syndicator->show_report = false;
        $csyn_syndicator->syndicateFeeds($feed_ids, true);
    }
}

function csyn_generic_ping($post_id) {
    global $wpdb, $csyn_syndicator;
    $dates = $wpdb->get_row("SELECT post_date, post_modified FROM $wpdb->posts WHERE id=$post_id");
    if ($csyn_syndicator->count <= 1 && $dates->post_modified == $dates->post_date && (strtotime($dates->post_modified < time()) || strtotime($dates->post_date) < time())) {
        if (function_exists('_publish_post_hook')) {
            _publish_post_hook($post_id);
        } else {
            generic_ping();
        }
    }
}

if (is_admin()) {
    csyn_preset_options();
}
$csyn_syndicator = new CyberSyn_Syndicator();
$csyn_rss_pull_mode = get_option(CSYN_RSS_PULL_MODE);
$csyn_bs_options = get_option(CSYN_THEBESTSPINNER_OPTIONS);
$csyn_wa_options = get_option(CSYN_WORDAI_OPTIONS);

function csyn_deactivation() {
    wp_clear_scheduled_hook('update_by_wp_cron');
}

register_deactivation_hook(__FILE__, 'csyn_deactivation');

function csyn_get_cuctom_cron_interval_name() {
    return 'every ' . get_option(CSYN_PSEUDO_CRON_INTERVAL) . ' minutes';
}

function csyn_add_cuctom_cron_interval($schedules) {
    $name = csyn_get_cuctom_cron_interval_name();
    $schedules[$name] = array(
        'interval' => intval(get_option(CSYN_PSEUDO_CRON_INTERVAL)) * 60,
        'display' => __($name)
    );
    return $schedules;
}

function csyn_permalink($permalink) {
    global $post;
    list($link) = get_post_custom_values('cyberseo_post_link');
    if (filter_var($link, FILTER_VALIDATE_URL)) {
        $permalink = $link;
    } elseif (filter_var($post->guid, FILTER_VALIDATE_URL)) {
        $permalink = $post->guid;
    }
    return $permalink;
}

add_filter('cron_schedules', 'csyn_add_cuctom_cron_interval');

if (isset($_GET['pull-feeds']) && $_GET['pull-feeds'] == get_option(CSYN_CRON_MAGIC)) {
    if (!is_admin()) {
        require_once(ABSPATH . 'wp-admin/includes/taxonomy.php');
        add_action('shutdown', 'csyn_update_feeds');
    }
} else {
    if (is_admin()) {
        add_action('admin_menu', 'csyn_main_menu');
        add_action('before_delete_post', 'csyn_delete_post_images');
        remove_action("publish_post", "generic_ping");
        remove_action('do_pings', 'do_all_pings', 10, 1);
        remove_action('publish_post', '_publish_post_hook', 5, 1);
        add_action("publish_post", "csyn_generic_ping");
    } else {
        if (get_option(CSYN_LINK_TO_SOURCE) == 'on') {
            add_filter('post_link', 'csyn_permalink', 1);
        }
        if (strpos($csyn_rss_pull_mode, "auto") !== false) {
            if (function_exists('wp_next_scheduled')) {
                add_action('update_by_wp_cron', 'csyn_update_feeds');
                if (!wp_next_scheduled('update_by_wp_cron')) {
                    wp_schedule_event(time(), csyn_get_cuctom_cron_interval_name(), 'update_by_wp_cron');
                }
            } else {
                add_action('shutdown', 'csyn_update_feeds');
            }
        } else {
            if (function_exists('wp_clear_scheduled_hook') && wp_next_scheduled('update_by_wp_cron')) {
                wp_clear_scheduled_hook('update_by_wp_cron');
            }
        }
    }
}
?>