<?php
$version = "1.1";
$homepage = "http://www.weberz.com/plugins/comment-link-manager/";
/*
Plugin Name: Comment Link Manager
Plugin URI: http://www.weberz.com/plugins/comment-link-manager/
Description: Provides an interface for managing links left in comments.
Version: 1.1
Author: Weberz Hosting
Author URI: http://www.weberz.com

Copyright 2009  Weberz Hosting  (email: rob@weberz.com)

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


if (!defined('PLUGINDIR')) {
        define('PLUGINDIR','wp-content/plugins');
}

if (is_file(trailingslashit(ABSPATH.PLUGINDIR).'comment-link-manager.php')) {
        define('CLM_FILE', trailingslashit(ABSPATH.PLUGINDIR).'comment-link-manager.php');
}
else if (is_file(trailingslashit(ABSPATH.PLUGINDIR). 'comment-link-manager/comment-link-manager.php')) {
        define('CLM_FILE', trailingslashit(ABSPATH.PLUGINDIR).'comment-link-manager/comment-link-manager.php');
}

define('BLACKLIST',1);
define('WHITELIST',2);

function clm_getdomain($url) {
	$url = preg_replace("/(www\.)*/i","",$url);
	preg_match('@^(?:http://)?([^/]+)@i',$url, $matches);
	$host = $matches[1];
	return $host;	
}

function clm_openNewWindow($link) {
	$link = preg_replace('/<a (.*)http:\/\/(.*)([\'"])(.*)>/i', '<a $1http://$2$3 target=$3_blank$3$4>', $link);
	return $link;
}

function clm_remNoFollow($link) {
	$link = preg_replace('/<a (.*)rel=([\'"])nofollow([\'"])(.*)>/i', '<a $1$4>', $link);
	$link = preg_replace('/<a (.*)rel=([\'"])nofollow (.*)([\'"])(.*)>/i', '<a $1rel=$2$3$2$5>', $link);
	$link = preg_replace('/<a (.*)rel=([\'"])(.*) nofollow([\'"])(.*)>/i', '<a $1rel=$2$3$2$5>', $link);	
	return $link;
}

function clm_authorLinkMod($link) {
	$remNFAuthor = get_option("clm_remNFAuthor");
	if (!preg_match("/[01]{1}/",$remNFAuthor)) {
		$remNFAuthor=0;
	}
	if ($remNFAuthor == 1) {
		$link = clm_remNoFollow($link);
	}

	$addNWAuthor = get_option("clm_addNWAuthor");
	if (!preg_match("/[01]{1}/",$addNWAuthor)) {
		$addNWAuthor=0;
	}
	if ($addNWAuthor == 1) {
		$link = clm_openNewWindow($link);
	}
	
	return $link;
}

add_filter('get_comment_author_link', 'clm_authorLinkMod');

function clm_commentLinkMod($link) {
	$remNFComment = get_option("clm_remNFComment");
	if (!preg_match("/[01]{1}/",$remNFComment)) {
		$remNFComment=0;
	}
	if ($remNFComment == 1) {
		$link = clm_remNoFollow($link);
	}

	$addNWComment = get_option("clm_addNWComment");
	if (!preg_match("/[01]{1}/",$addNWComment)) {
		$addNWComment=0;
	}
	if ($addNWComment == 1) {
		$link = clm_openNewWindow($link);
	}
	
	return $link;
}

add_filter('comment_text', 'clm_commentLinkMod');

function clm_linkfilter($link) {
	global $wpdb;
	$wpdb->clm = $wpdb->prefix.'clm_manager';
	$wpdb->comments = $wpdb->prefix.'comments';

	//trim the link of any white space
	$retlink = trim($link);
	
	//Check if a url exists.  If not.. Return Nothing.
	if ($retlink == "") {
		return "";
	}
	
	//Obtain Domain From Link
	$domain = clm_getdomain($link);

	//Check Blacklist For Domain
	$sql = "select id from `" . $wpdb->clm . "` where domain = \"" . $domain . "\" and list = " . BLACKLIST;
	$result = $wpdb->get_results($sql);
	if (count($result) !=0) {
		//Domain is blacklisted.  Return Nothing.
		return "";
	}

	//Load # of safe posts
	$safeNumber = get_option("clm_safeNumber");
	if (!preg_match("/[0-9]+/",$safeNumber)) {
		$safeNumber=0;
	}

	if ($safeNumber == 0) {
		return $retlink;
	} else {
		//Theres a limit.  Check the whitelist and apply limit
		//Check Whitelist For Domain
		$sql = "select id from `" . $wpdb->clm . "` where domain = \"" . $domain . "\" and list = " . WHITELIST;
		$result = $wpdb->get_results($sql);
		if (count($result) >=1) {
			//Domain is whitelisted.  Return the Link!
			return $link;
		} else {
			//Not Whitelisted.  Now apply the required number of comments.
			$sql = "select comment_ID from `" . $wpdb->comments . "` where comment_author_url like \"http://" . $domain . "%\" or comment_author_url like \"http://www." . $domain . "%\"";
			$result = $wpdb->get_results($sql);
			$resCount = count($result);
			if ($resCount >= $safeNumber) {
				return $link;
			} else {
				return "";
			}
		}
	}

	return $retlink;
}
add_filter('get_comment_author_url', 'clm_linkfilter');

function clm_activate() {
        global $wpdb;

        $wpdb->clm = $wpdb->prefix.'clm_manager';
        $charset_collate = '';
        if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
                if (!empty($wpdb->charset)) {
                        $charset_collate .= " DEFAULT CHARACTER SET $wpdb->charset";
                }
                if (!empty($wpdb->collate)) {
                        $charset_collate .= " COLLATE $wpdb->collate";
                }
        }
        $result = $wpdb->query("
		CREATE TABLE `$wpdb->clm` (
			`id` INT( 30 ) NOT NULL AUTO_INCREMENT ,
			`domain` TEXT NOT NULL ,
			`list` INT( 2 ) NOT NULL ,
			`comment` TEXT NOT NULL ,
			`timestamp` INT( 30 ) NOT NULL ,
			PRIMARY KEY ( `id` ) ,
			FULLTEXT (
				`domain`
			)
		) $charset_collate COMMENT = 'Comment Link Manager List Database';
        ");
	$sql = "select id from `$wpdb->clm` where domain = 'weberz.com'";
	$result = $wpdb->get_results($sql);
	if (count($result)==0) {
		$sql = "insert into `$wpdb->clm` (domain, list, comment, timestamp) values('weberz.com', 2, 'Added by Robert Rolfe', UNIX_TIMESTAMP())";
		$wpdb->query($sql);
	}
}
register_activation_hook(CLM_FILE, 'clm_activate' );

function clm_adminPage() {
	global $wpdb;
	global $version;
	global $homepage;

	echo "<div class=\"wrap\">";
	echo "<h2>Comment Link Manager</h2>";
	echo "by <a href=\"http://www.weberz.com\" title=\"Weberz Web Hosting\" target=\"_blank\">Weberz Hosting</a> <br />";
	echo "<strong>Version: </strong>" . $version . " | <a href=\"" . $homepage . "\" target=\"_blank\" title=\"CLM Home Page\">Plugin Homepage</a> | <a href=\"http://twitter.com/Weberz\" title=\"Weberz Twitter Feed\" target=\"_blank\">Follow Us on Twitter</a><br>";
	if ($_POST['CLMACTION']=="saveoptions") {
		if (isset($_POST['clm_safeNumber'])) {
			$safeNumber=$_POST['clm_safeNumber'];
		} else {
			$safeNumber=0;
		}
		if (!preg_match("/[0-9]+/",$safeNumber)) {
			echo "<div style=\"color: #FF0000; font-weight: bold;\">Error:  Posts Required Value Must Be A Valid Number!</div>";
		} else {
			update_option('clm_safeNumber', intval($safeNumber));
			update_option('clm_remNFAuthor', intval($_POST['clm_remNFAuthor']));
			update_option('clm_remNFComment', intval($_POST['clm_remNFComment']));
			update_option('clm_addNWAuthor', intval($_POST['clm_addNWAuthor']));
			update_option('clm_addNWComment', intval($_POST['clm_addNWComment']));
			echo "<div style=\"color: #FF0000; font-weight: bold;\">Options Saved Successfully</div>";
		}
	} else if ($_POST['CLMACTION']=="addlist") {
		if (isset($_POST['domain'])) {
			$domain = $_POST['domain'];
			if (preg_match("/[a-z0-9-\.]+/i", $domain)) {
				$sql = "insert into `" . $wpdb->prefix . "clm_manager` (domain, list, comment, timestamp) values('" . $domain . "', " . $_POST['list'] . ", '" . $_POST['comment'] . "', UNIX_TIMESTAMP())";
				$wpdb->query($sql);
				echo "<div style=\"color: #FF0000; font-weight: bold;\">List Entry Added Successfully!</div>";
			} else {
				echo "<div style=\"color: #FF0000; font-weight: bold;\">Error: Invalid Domain Entered</div>";
			}
		} else {
			echo "<div style=\"color: #FF0000; font-weight: bold;\">Error: Unable To Add List Entry</div>";
		}
	} else if ($_POST['CLMACTION']=="dellist") {
		if (isset($_POST['idnum'])) {
			$id = $_POST['idnum'];
		} else {
			$id = 0;
		}
		if (($id != 0) && (preg_match("/[0-9]+/", $id))) {
			$sql = "delete from `" . $wpdb->prefix . "clm_manager` where id = " . $id;
			$wpdb->query($sql);
			echo "<div style=\"color: #FF0000; font-weight: bold;\">List Entry Deleted Successfully</div>";
		} else {
			echo "<div style=\"color: #FF0000; font-weight: bold;\">Error: Unable To Delete List Entry</div>";
		}
	}

	echo "<h3 style=\"padding-bottom: 0px; margin-bottom: 0px;\">" . __('List Management') . ":</h3>";
	echo "<table class=\"widefat\" cellspacing=\"0\">";
	echo "<thead>";
	echo "<tr>";
	echo "<th scope=\"col\" class=\"manage-column\">Id</th>";
	echo "<th scope=\"col\" class=\"manage-column\">Domain</th>";
	echo "<th scope=\"col\" class=\"manage-column\">List</th>";
	echo "<th scope=\"col\" class=\"manage-column\">Comment</th>";
	echo "<th scope=\"col\" class=\"manage-column\">Last Modified</th>";
	echo "<th scope=\"col\" class=\"manage-column\">&nbsp;</th>";
	echo "</tr>";
	echo "</thead>";
	echo "<tfoot>";
	echo "<tr>";
	echo "<th scope=\"col\" class=\"manage-column\">Id</th>";
	echo "<th scope=\"col\" class=\"manage-column\">Domain</th>";
	echo "<th scope=\"col\" class=\"manage-column\">List</th>";
	echo "<th scope=\"col\" class=\"manage-column\">Comment</th>";
	echo "<th scope=\"col\" class=\"manage-column\">Last Modified</th>";
	echo "<th scope=\"col\" class=\"manage-column\">&nbsp;</th>";
	echo "</tr>";
	echo "</tfoot>";
	echo "<tbody>";

	$sql = "select id, domain, list, comment, timestamp from `" . $wpdb->prefix . "clm_manager` order by domain";
	$result = $wpdb->get_results($sql);
	for ($i=0; $i < count($result); $i++) {
		$id = $result[$i]->id;
		$domain = $result[$i]->domain;
		$list = $result[$i]->list;
		$comment = $result[$i]->comment;
		$timestamp = $result[$i]->timestamp;

		if ($list == BLACKLIST) {
			$list = "Blacklist";
		} else {
			$list = "Whitelist";
		}

		echo "<tr>";
		echo "<th scope=\"col\" class=\"manage-column\">" . $id . "</th>";
		echo "<th scope=\"col\" class=\"manage-column\">" . $domain . "</th>";
		echo "<th scope=\"col\" class=\"manage-column\">" . $list . "</th>";
		echo "<th scope=\"col\" class=\"manage-column\">" . nl2br($comment) . "</th>";
		echo "<th scope=\"col\" class=\"manage-column\">" . date("m-d-Y g:h:s", $timestamp) . "</th>";
		echo "<th scope=\"col\" class=\"manage-column\"><form method=\"POST\" action=\"\"><input type=\"hidden\" name=\"CLMACTION\" value=\"dellist\"><input type=\"hidden\" name=\"idnum\" value=\"" . $id . "\"><input type=\"submit\" name=\"submit\" value=\"" . __('Delete') . "\" style=\"padding: 2px;\"></form></th>";
		echo "</tr>";
	}

	echo "</tbody>";
	echo "</table>";
	echo "<br>";
	echo "<h3 style=\"padding-bottom: 0px; margin-bottom: 0px;\">" . __('Add New Black/White List Entry') . ":</h3>";
	echo "<form method=\"POST\" action=\"\">";
	echo "<input type=\"hidden\" name=\"CLMACTION\" value=\"addlist\" />";
	echo "<table width=\"200\">";
	echo "<tr>";
	echo "<td width=\"75\">" . __('Domain:') . "</td>";
	echo "<td width=\"125\"><input type=\"text\" name=\"domain\" value=\"\"></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td width=\"75\">" . __('List:') . "</td>";
	echo "<td width=\"125\"><select name=\"list\">";
	echo "<option value=\"" . BLACKLIST . "\">" . __('Blacklist') . "</option>";
	echo "<option value=\"" . WHITELIST . "\">" . __('Whitelist') . "</option>";
	echo "</select></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan=\"2\">" . __('Comment:') . "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan=\"2\"><textarea name=\"comment\" rows=\"4\" cols=\"30\"></textarea></td>";
	echo "</tr>";
	echo "</table>";
	echo "<p class=\"submit\" style=\"margin: 0px; padding: 0px;\"><input type=\"submit\" name=\"Submit\" value=\"" . __('Add List Entry') . " &raquo;\" /></p>";
	echo "</form>";
	echo "<br />";
	echo "<h3 style=\"padding-bottom: 0px; margin-bottom: 0px;\">" . __('Edit Options') . ":</h3>";
	echo "<form method=\"POST\" action=\"\">";
	echo "<input type=\"hidden\" name=\"CLMACTION\" value=\"saveoptions\" />";
	echo __('Required number of posts before enabling author comment links: ') . "<input type=\"text\" name=\"clm_safeNumber\" value=\"" . get_option('clm_safeNumber') . "\" style=\"width: 50px;\" /><br />";
	echo "<input type=\"checkbox\" value=\"1\" name=\"clm_remNFAuthor\" ";
	if (get_option('clm_remNFAuthor')==1) { 
		echo "checked"; 
	} 
	echo "> " . __('Remove nofollow attribute from comment author links') . "<br />";
	echo "<input type=\"checkbox\" value=\"1\" name=\"clm_addNWAuthor\" ";
	if (get_option('clm_addNWAuthor')==1) { 
		echo "checked"; 
	}
	echo "> " . __('Open comment author links in new window') . "<br />";
	echo "<input type=\"checkbox\" value=\"1\" name=\"clm_remNFComment\" ";
	if (get_option('clm_remNFComment')==1) { 
		echo "checked"; 
	}
	echo "> " . __('Remove nofollow attribute from comment body links') . "<br />";
	echo "<input type=\"checkbox\" value=\"1\" name=\"clm_addNWComment\" ";
	if (get_option('clm_addNWComment')==1) { 
		echo "checked"; 
	}
	echo "> " . __('Open comment body links in new window') . "<br />";
	echo "<p class=\"submit\" style=\"margin: 0px; padding: 0px;\"><input type=\"submit\" name=\"Submit\" value=\"" . __('Update Options') . " &raquo;\" /></p>";
	echo "</form>";
	echo "<br />";
	echo "<h3 style=\"padding-bottom: 0px; margin-bottom: 0px;\">" . __('Credits') . ":</h3>";
	echo "<a href=\"" . $homepage . "\" target=\"_blank\" title=\"" . __('Comment Link Manager Home Page') . "\">" . __('Comment Link Manager') . "</a> " . __('was designed by ') . "<a href=\"http://twitter.com/rrolfe\" target=\"_blank\" title=\"Robert Rolfe\">Robert Rolfe</a>. " . __('It\'s released under the GNU GPL version 2 License.');
	echo "</div>";
}

function clm_addAdminPage() {
	if (function_exists('add_options_page')) {
		add_options_page('Comment Link Manager', 'Comment Link Manager', 9, CLM_FILE, 'clm_adminpage');
	}
}
add_action('admin_menu', 'clm_addAdminPage');
?>
