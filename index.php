<?php

include_once(__DIR__."/users/user.php");
include_once(__DIR__."/pages/page.php");
include_once(__DIR__."/util/session.php");
include_once(__DIR__."/util/dom.php");

$LOGGED_IN = false;
$USER = null;

$HAS_PAGE = false;
$PAGE = null;

$DOM = new MyDOM(file_get_contents(__DIR__."/def_index.html"));

$CONTEXT = (isset($_GET['context'])) ? $_GET['context'] : 'home';

/* --------------------------------------------------
 * --------------------------------------------------
 * STRUCTURAL MANAGEMENT ----------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

function set_content($content_html) {
	global $DOM;

	$DOM->goto("content")->clear();
	$DOM->append_html($content_html);
}

function create_screens(){
	global $DOM, $CONTEXT;

	$DOM->goto("screens");

	if (!isset($_SESSION['user'])) {
		$DOM->append_html(file_get_contents(__DIR__."/users/login_screen.html"));
		$DOM->end();
	} else {
		$DOM->append_html(file_get_contents(__DIR__."/users/delete_screen.html"));
		$DOM->end();
	}

	$DOM->create("div", ["class"=>"clearer"], "");

	if ($CONTEXT == 'create' || $CONTEXT == 'edit') {
		$DOM->goto("screens");
		$DOM->append_html(file_get_contents(__DIR__."/pages/add_image_screen.html"));
	}
}

function handle_error($file, $title="Something Went Wrong", $description="The Compendium Has Encountered an Error") {
	global $DOM;

	$DOM->goto("main");
	$DOM->add_class("no-sidebar");
	$DOM->add_class("error-msg");

	set_content(file_get_contents($file));

	$DOM->goto("display_h1")->text = htmlentities($title);
	if (!empty($description)) {
		$DOM->goto("display_h2")->text = htmlentities($description);
		$DOM->remove_class("nodisp");
	} else $DOM->goto("display_h2")->add_class("nodisp");
}

/* --------------------------------------------------
 * --------------------------------------------------
 * NAVBAR MANAGEMENT --------------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

function create_navbar() {
	global $DOM;

	$DOM->goto("navbar");

	$DOM->create("div", ["class"=>"navopt fl", "id"=>"navopt_home"], "Compendium Home");

	if (isset($_SESSION['user'])) {
		$DOM->append_html(file_get_contents(__DIR__."/users/navopt_user.html"));
		$DOM->goto("navopt_user")->text = "Hello, ".$_SESSION['user']->username." \u{25BC}";
		$DOM->goto("navbar");
	} else {
		$DOM->append_html(file_get_contents(__DIR__."/users/navopt_sign_in.html"));
		$DOM->end();
	}

	$DOM->create("div", ["class"=>"clearer", "id" => "navbar_clearer"], "");
}

function add_navopt($id, $text="", $right=false, $attrs=null) {
	global $DOM;

	if (empty($attrs)) $attrs = [];
	if (!isset($text)) $text = "";

	$attrs["id"] = $id;

	$DOM->goto("navbar");
	$DOM->insert_before("navbar_clearer", "div", $attrs, $text);
	$DOM->add_class("navopt");
	$DOM->add_class( ($right) ? "fr" : "fl" );
}

function add_navopt_create() {
	global $HAS_PAGE, $PAGE;

	if ($HAS_PAGE){
		add_navopt("navopt_create", "Create a Child Page", false, ["parent" => $PAGE->selector, "class" => "button"]);
	} else {
		add_navopt("navopt_create", "Create a Page", false, ["class" => "button"]);
	}
}

function add_navopt_edit($page_id=null) {
	global $HAS_PAGE, $PAGE;

	if ($HAS_PAGE) {
		add_navopt("navopt_edit", "Edit this page", true, ["page" => $PAGE->selector, "class" => "button"]);
	}
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
	global $LOGGED_IN, $USER;

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
	global $LOGGED_IN, $USER;

	if (empty($page)) return false;
	if ($LOGGED_IN) return $page->can_see($USER);
	else return $page->unlocked();
}

function can_be_edited($page) {
	global $LOGGED_IN, $USER;

	if (empty($page)) return false;
	if ($LOGGED_IN) return $page->can_edit($USER);
	else return $page->opened();
}

function get_page($page_ident, $identifier="selector") {
	if (is_a($page_ident, "Page")) return (Page::is_page($page_ident->id, "id")) ? $page_ident : null;
	if (!Page::is_page($page_ident, $identifier)) return null;
	return new Page($page_ident, $identifier);
}

function add_page_to_sidebar($page, $list_size=1, $index=0, $restart=false) {
	global $DOM;
	$had_children = false;

	if (!can_be_seen($page)) return false;

	$DOM->create("li", ["id" => $page->selector]);
	$DOM->text = htmlentities($page->title, ENT_QUOTES);
	if ($index == 0) $DOM->add_class("first");
	if ($index == $list_size - 1) $DOM->add_class("last");
	if ($restart) $DOM->add_class("restart");
	if ($page->has_children()) {
		$had_children = TRUE;
		$DOM->add_class("parent");
		$DOM->remove_class("last");
		$DOM->end();

		$children = $page->children;
		$DOM->create("ul", []);

		$do_restart = false;
		foreach ($children as $child_index => $child) {
			$do_restart = add_page_to_sidebar($child, count($children), $child_index, $do_restart);
		}

		$DOM->end();
	} else $DOM->end();

	return $had_children;
}

function fill_sidebar_pages($target_user, $identifier="id") {
	global $DOM;

	$target_user = get_user($target_user, $identifier);

	$DOM->goto("books");

	if (isset($target_user)) {
		$books = Page::get_user_books($target_user);

		$do_restart = false;
		foreach ($books as $book_index => $book) {
			$do_restart = add_page_to_sidebar($book, count($books), $book_index, $do_restart);
		}
	}

	$DOM->create("li", ["id" => "sidebar_footer"], "");
}

function view_page($page_ident, $identifier="selector") {
	global $HAS_PAGE, $PAGE, $DOM, $USER, $LOGGED_IN;

	$PAGE = null;
	$PAGE = get_page($page_ident, $identifier);
	$HAS_PAGE = (!is_null($PAGE));
	if (!$HAS_PAGE) {
		// Display html for "page not found"
		return;
	}
	error_log("Page found");

	fill_sidebar_pages($PAGE->author);

	if (!can_be_seen($PAGE)) {
		// Display html for "page not found"
		return;
	}

	set_content(file_get_contents(__DIR__."/contexts/view.html"));
	$DOM->goto("page_holder")->clear()->append_html($PAGE->text);
	$DOM->goto("display_h1")->text = htmlentities($PAGE->title);
	if ($PAGE->description != "") {
		$DOM->goto("display_h2");
		$DOM->text = htmlentities($PAGE->description);
		$DOM->remove_class("nodisp");
	} else $DOM->goto("display_h2")->add_class("nodisp");

	if ($LOGGED_IN) {
		if ($USER->has_permission(User::ACT_EDIT_OWN_PAGES)) add_navopt_create();
		if ($PAGE->can_edit($USER)) add_navopt_edit();
	}
}

function view_user_pages($user_ident, $identifier="selector") {
	global $LOGGED_IN, $USER, $HAS_PAGE, $PAGE;

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
		handle_error(__DIR__."/errors/user_no_pages.html", "An Empty Library", "This user has not written any pages");
		if ($target_user->id == $USER->id && $USER->has_permission(User::ACT_EDIT_OWN_PAGES)) {
			add_navopt_create();
		}
	}
}

function edit_page() {
	global $LOGGED_IN, $USER, $DOM, $HAS_PAGE, $PAGE;

	if (!$LOGGED_IN) {
		// Display "you must be logged in to access this page"
		return;
	}

	$PAGE = null;
	if (isset($_GET['page_id'])) $PAGE = get_page($_GET['page_id'], 'selector');
	$HAS_PAGE = (!is_null($PAGE));
	if (!$HAS_PAGE) {
		// Display html for "page not found"
		return;
	}

	$DOM->goto("main");
	$DOM->add_class("no-sidebar");

	$DOM->goto("display_h1")->text = "Edit a Page";
	$DOM->goto("display_h2")->text = "Use the form below to edit this page";

	$DOM->goto("content");
	$DOM->append_html(file_get_contents(__DIR__."/contexts/create.html"));
	$DOM->goto("text_entry")->set_attr("id", "text_editing");

	$DOM->goto("page_form_user")->set_attr("value", $USER->selector);

	if ($PAGE->has_parent()) {
		$DOM->goto("page_form_path")->text = $PAGE->parent->path['titles'];
		$DOM->goto("page_form_parent")->set_attr("value", $PAGE->parent->selector);
	}
}

function create_page() {
	global $LOGGED_IN, $USER, $DOM, $HAS_PAGE, $PAGE;

	if (!$LOGGED_IN) {
		// Display "you must be logged in to access this page"
		return;
	}
	if (!$USER->has_permission(User::ACT_EDIT_OWN_PAGES)) {
		// Display "you do not have permission to create pages"
		return;
	}

	$DOM->goto("main");
	$DOM->add_class("no-sidebar");

	$DOM->goto("display_h1")->text = "Create a Page";
	$DOM->goto("display_h2")->text = "Use the form below to create a page";

	$DOM->goto("content");
	$DOM->append_html(file_get_contents(__DIR__."/contexts/create.html"));

	$DOM->goto("page_form_user")->set_attr("value", $USER->selector);

	if (isset($_GET['parent_id'])) {
		if (Page::is_page($_GET['parent_id'], 'sel')) {
			$parent = new Page($_GET['parent_id'], 'sel');
			$DOM->goto("page_form_path")->text = $parent->path['titles'];
			$DOM->goto("page_form_parent")->set_attr("value", $parent->selector);
		} else $DOM->goto("page_form_path")->text = "Select a parent book";
	} else {
		$DOM->goto("page_form_path")->text = "Select a parent book";
	}
}

/* --------------------------------------------------
 * --------------------------------------------------
 * HOME VIEWING -------------------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

function home_display() {
	global $DOM, $LOGGED_IN, $USER;

	$DOM->goto("display_h1")->text = "The Compendium";
	$DOM->goto("display_h2")->text = "A place where worlds meet";

	$DOM->goto("content");
	$DOM->append_html(file_get_contents(__DIR__."/contexts/home.html"));

	$DOM->goto("main");
	$DOM->add_class("no-sidebar");

	if ($LOGGED_IN) {
		if ($USER->has_permission(User::ACT_EDIT_OWN_PAGES)) add_navopt_create();
	}
}

/* --------------------------------------------------
 * --------------------------------------------------
 * MAIN ---------------------------------------------
 * --------------------------------------------------
 * -------------------------------------------------- */

if (isset($_SESSION['user'])) log_in_user($_SESSION['user']);
if (!$LOGGED_IN) unset($_SESSION['user']);

create_navbar();
create_screens();

switch($CONTEXT) {
	case 'view': 
		if (isset($_GET['page_id'])) view_page($_GET['page_id']);
		else if (isset($_GET['user_id'])) view_user_pages($_GET['user_id']);
		else if ($LOGGED_IN) view_user_pages($USER);
		else {
			$CONTEXT = 'home';
			home_display();
		}
		break;
	case 'edit':
		edit_page();
		break;
	case 'create':
		create_page();
		break;
	default:
		$CONTEXT = 'home';
		home_display();
}

echo $DOM->print();

?>