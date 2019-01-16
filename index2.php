<?php

include_once(__DIR__."/users/user.php");
include_once(__DIR__."/pages/page.php");
include_once(__DIR__."/util/session.php");
include_once(__DIR__."/util/dom.php");

$LOGGED_IN = false;
$USER = null;

$HAS_PAGE = false;
$PAGE = null;

$DOM = new MyDOM($html);

function handle_error($message, $title, $super_title="Something Went Wrong", $description="Compendium Has Encountered an Error") {

}

/* --------------------------------------------------
 * --------------------------------------------------
 * STRUCTURAL MANAGEMENT ----------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

function set_content($content_html) {
	$DOM->goto("content")->clear();
	$DOM->append_html($content_html);
}

/* --------------------------------------------------
 * --------------------------------------------------
 * USER MANAGEMENT ----------------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

function get_user($user_ident, $identifier="selector") {
	if (is_a($user_ident, "User")) return (User::is_user($user_ident->id, "id")) ? $user_ident : null;
	if (!User::is_user($user_ident, $identifier)) return null;
	return new User($user_ident, $identifier);
}

function log_in_user($user) {
	$LOGGED_IN = false;
	if(!empty($user)) {
		try {
			$USER = get_user($user);
			if (!empty($USER)) $LOGGED_IN = true;
		} catch (Exception $e) { }
	}
	return $LOGGED_IN;
}

/* --------------------------------------------------
 * --------------------------------------------------
 * PAGE VIEWING -------------------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

function can_be_seen($page) {
	if (empty($page)) return false;
	if ($LOGGED_IN) return $page->can_see($USER);
	else return $page->opened();
}

function get_page($page_ident, $identifier="selector") {
	if (is_a($page_ident, "Page")) return (Page::is_page($page_ident->id, "id")) ? $page_ident : null;
	if (!Page::is_page($page_ident, $identifier)) return null;
	return new Page($page_ident, $identifier);
}

function view_page($page_ident, $identifier="selector") {
	$PAGE = get_page($page_ident, $identifier);
	if (empty($PAGE)) $HAS_PAGE = false;
	if (!$HAS_PAGE) {
		// Display html for "page not found"
		return;
	}
	if (!can_be_seen($PAGE)) {
		// Display html for "page not found"
		return;
	}

	set_content(file_get_contents(__DIR__."/view.html"));
	$DOM->goto("page_holder")->clear()->append_html($PAGE->text);
	$DOM->goto("display_h1")->text = htmlentities($PAGE->title);
	if ($PAGE->description != "") {
		$DOM->goto("display_h2");
		$DOM->text = htmlentities($PAGE->description);
		$DOM->remove_class("nodisp");
	} else $dom->goto("display_h2")->add_class("nodisp");
}

function view_user_pages($user_ident, $identifier="selector") {
	$target_user = null;
	if ($user_ident === null && $LOGGED_IN) $target_user = $USER;
	else {
		$target_user = get_user($user_ident, $identifier);
	}
	if (empty($target_user)) {
		// Display "user not found" html
		return;
	}
	
	$pages = Page::get_user_pages($target_user);
	$found_page = false;
	if (count($pages) > 0) {
		foreach ($pages as $key => $trial_page) {
			if (can_be_seen($trial_page)) {
				view_page($trial_page);
				$found_page = true;
				break;
			}
		}
	}

	if (!$found_page) {
		// Display "user has no pages" html
	}
}

/* --------------------------------------------------
 * --------------------------------------------------
 * MAIN ---------------------------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

$html = file_get_contents(__DIR__."/def_index.html");

log_in_user($_SESSION['user']);
if (!$LOGGED_IN) unset($_SESSION['user']);

?>