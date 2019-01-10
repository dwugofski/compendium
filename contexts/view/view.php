<?php

function fill_sidebar($target_user) {
	global $dom;
	$dom->goto("books");

	if (isset($target_user)) {
		$books = Page::get_user_books($target_user);
		//usort($books, ['Page', 'compare']);

		$do_restart = FALSE;
		foreach ($books as $book_index => $book) {
			$do_restart = add_page($book, count($books), $book_index, $do_restart);
		}
	}

	$dom->create("li", ["id" => "sidebar_footer"], "");
}

function add_page($page, $list_size=1, $index=0, $restart=FALSE) {
	global $dom;
	$had_children = FALSE;

	$dom->create("li", ["id" => $page->selector]);
	$dom->text = htmlentities($page->title, ENT_QUOTES);
	if ($index == 0) $dom->add_class("first");
	if ($index == $list_size - 1) $dom->add_class("last");
	if ($restart) $dom->add_class("restart");
	if ($page->has_children()) {
		$had_children = TRUE;
		$dom->add_class("parent");
		$dom->remove_class("last");
		$dom->end();

		$children = $page->children;
		$dom->create("ul", []);

		$do_restart = FALSE;
		foreach ($children as $child_index => $child) {
			$do_restart = add_page($child, count($children), $child_index, $do_restart);
		}

		$dom->end();
	} else $dom->end();

	return $had_children;
}

function display_page($pagesel, $target_user) {
	global $dom;

	$target_page = NULL;
	if (isset($target_user)) {
		$pages = Page::get_user_pages($target_user);

		if (count($pages) > 0) {
			if (isset($pagesel)) {
				foreach ($pages as $key => $page) {
					if ($page->selector == $pagesel) {
						$target_page = $page;
					}
				}
			} else {
				$target_page = $pages[0];
			}
		}

		if (!isset($target_page)) throw new CompendiumError("Page not found.", FALSE, ERRORS::USER_ERROR, 404);
	}

	if (!isset($target_page)) {
		if (isset($pagesel)) {
			$target_page = new Page($pagesel, 'sel');
		} else throw new CompendiumError("Page not found.", FALSE, ERRORS::USER_ERROR, 404);
	}

	if (!isset($target_page)) throw new CompendiumError("Page could not be found.", FALSE, ERRORS::USER_ERROR, 404);

	if ($target_page->locked && !isset($_SESSION['user'])) {
		throw new CompendiumError("Page cannot be viewed without logging in.", FALSE, ERRORS::USER_ERROR, 401);
	} elseif (!$target_page->can_see($_SESSION['user'])) throw new CompendiumError("User cannot see page.", FALSE, ERRORS::USER_ERROR, 401);

	$dom->goto("content")->append_html(file_get_contents(__DIR__."/view.html"));
	$dom->goto("page_holder")->append_html($target_page->text);
	$dom->goto("display_h1")->text = htmlentities($target_page->title);
	if ($target_page->description != "") {
		$dom->goto("display_h2");
		$dom->text = htmlentities($target_page->description);
		$dom->remove_class("nodisp");
	}
	else $dom->goto("display_h2")->add_class("nodisp");
	return $target_page;
}

function set_context($dom) {
	$page_user = NULL;
	$target_page = (isset($_GET['page_id'])) ? $_GET['page_id'] : null;
	if (!isset($_GET['user']) && isset($_SESSION['user'])) {
		$page_user = $_SESSION['user'];
	} elseif (isset($_GET['user'])) {
		$page_user = new User($_GET['user'], 'sel');
	} else throw new CompendiumError("User not found.", false, ERRORS::USER_ERROR, 404);

	fill_sidebar($page_user);
	$page = display_page($target_page, $page_user);

	add_navopt_create($dom, $page->selector);
	if ($page->can_edit($page_user)) add_navopt_edit($dom, $page->selector);
}

?>