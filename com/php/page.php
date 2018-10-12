<?php

include_once(dirname(__DIR__)."\errors.php");
include_once(dirname(__DIR__)."\mysql.php");

class Page implements Ds\Hashable {
	private $id;

	public static function validate_title($title) {
		return is_string($title);
	}

	public static function validate_text($text) {
		return is_string($text);
	}

	public static function get_user_pages($user) {
		$sql = "SELECT id FROM pages WHERE author_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$userid]);
		$ret = array();
		if (is_array($rows)) {
			foreach($rows as $i=>$row) {
				$ret[] = new Page($row['id']);
			}
		}
		return $ret;
	}

	public static function is_page($pageid) {
		$sql = "SELECT id FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$pageid]);
		if (is_array($rows) && count($rows) > 0) return TRUE;
		else return FALSE;
	}

	public static function create_new_page($author, $title="Untitled", $text=""){
		if ($author->has_permission('epo') == FALSE) ERRORS::log(ERRORS::PERMISSIONS_ERROR, sprintf("User '%d' cannot create pages --> Page::create_new_page()", $author->id));
		if (self::validate_title($title) == FALSE) ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Invalid page title:\n --------- \n%s\n ---------- \n --> Page::create_new_page()", $title));
		if (self::validate_text($text) == FALSE) ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Invalid page text:\n --------- \n%s\n ---------- \n --> Page::create_new_page()", $text));

		$selector = self::make_selector();
		$sql = "INSERT INTO pages (author_id, title, content, selector) VALUES (?, ?, ?, ?)";
		MYSQL::run_query($sql, 'isbs', [&$author->id, &$title, &$text, &$selector]);
		return MYSQL::get_index();
	}

	private function make_selector() {
		$selector = bin2hex(openssl_random_pseudo_bytes(12));
		$unique = TRUE;
		MYSQL::prepare("SELECT id FROM pages WHERE selector = ?", "s", [&$selector]);
		for ($i=0; $i<10; $i+=1) {
			if (!empty(MYSQL::execute())) {
				$unique = TRUE;
			} else {
				$selector = bin2hex(openssl_random_pseudo_bytes(12));
			}
		}
		if ($unique) return $selector;
		else ERRORS::log(ERRORS::PAGE_ERROR("Could not establish a unique selector for pages\n"));
	}

	public function __construct($pageid) {
		if (self::is_page($pageid)) $this->id = $pageid;
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Page '%d' not found --> Page::__construct()", $pageid));
	}

	public function __get($name) {
		switch($name){
			case "id":
				return $this->id;
			case "author":
				return $this->get_author();
			case "title":
				return $this->get_title();
			case "text":
				return $this->get_text();
			case "selector":
				return $this->get_selector();
			case "locked":
				return $this->is_locked();
			case "opened":
				return $this->is_opened();
			case "colabs":
			case "collabs":
			case "collaborators":
				return $this->get_colabs();
			case "whitelist":
				return $this->get_whitelist();
			case "blacklist":
				return $this->get_blacklist();
			case "level":
				return $this->get_level();
			case "parents":
				return $this->get_parents(TRUE);
			case "parent":
				return $this->get_parent();
			case "all_children":
				return $this->get_children(TRUE);
			case "children":
				return $this->get_children(FALSE);
			case "isBook":
				return $this->is_book();
			case "isChapter":
				return $this->is_chapter();
			case "book":
				return $this->get_book();
			case "chapter":
				return $this->get_chapter();
			default:
				ERRORS::log(ERRORS::PAGE_ERROR, "Attempted to get unknown property '%s' of page", $name);
		}
	}

	public function __set($name, $value) {
		switch($name) {
			case "title":
				$this->set_title($value);
			case "text":
				$this->set_text($value);
			case "locked":
				if ($value) $this->lock();
				else $this->unlock();
			case "opened":
				if ($value) $this->open();
				else $this->close();
			case "id":
			case "author":
			case "selector":
			case "colabs":
			case "collabs":
			case "collaborators":
			case "level":
			case "parents":
			case "parent":
			case "all_children":
			case "children":
			case "isBook":
			case "isChapter":
			case "book":
			case "chapter":
				ERRORS::log(ERRORS::PAGE_ERROR, "Attempted to set read-only property '%s' of page", $name);
			default:
				ERRORS::log(ERRORS::PAGE_ERROR, "Attempted to set unknown property '%s' of page", $name);
		}
	}

	public function can_see($user) {
		if ($user->has_permission(User::ACT_VIEW_ALL_PAGES)) return TRUE;
		if ($this->get_author() == $user->id && $user->has_permission(User::ACT_VIEW_OWN_PAGES)) return TRUE;
		if ($this->is_blacklisted_user($user)) return FALSE;
		if ($this->is_locked() == FALSE && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return TRUE;
		if ($this->is_colab($user) && $user->has_permission(User::ACT_VIEW_OPEN_PAGES)) return TRUE;
		if ($this->is_whitelisted_user($user) && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return TRUE;

		return FALSE;
	}

	public function can_edit($user) {
		if ($user->has_permission(User::ACT_EDIT_ALL_PAGES)) return TRUE;
		if ($this->get_author() == $user->id && $user->has_permission(User::ACT_EDIT_OWN_PAGES)) return TRUE;
		if (in_array($user->id, $this->get_blacklist())) return FALSE;
		if ($this->is_opened() && $user->has_permission(User::ACT_EDIT_OPEN_PAGES)) return TRUE;
		if ($this->is_colab($user) && $user->has_permission(User::ACT_EDIT_OPEN_PAGES)) return TRUE;

		return FALSE;
	}

	public function can_lock($user) {
		if ($user->has_permission(User::ACT_LOCK_ALL_PAGES)) return TRUE;
		if ($this->get_author() == $user->id && $$user->has_permission(User::ACT_LOCK_OWN_PAGES)) return TRUE;

		return FALSE;
	}

	public function can_open($user) {
		if ($user->has_permission(User::ACT_OPEN_ALL_PAGES)) return TRUE;
		if ($this->get_author() == $user->id && $$user->has_permission(User::ACT_OPEN_OWN_PAGES)) return TRUE;

		return FALSE;
	}

	public function get_author() {
		$sql = "SELECT author_id FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$this->id]);
		if (is_array($rows) && count($rows) > 0) return $rows[0]['author_id'];
		else ERRORS::log(ERRORS::PAGE_ERROR, "Page '%d' not found --> Page::get_author()", $this->id);
	}

	public function get_colabs() {
		$sql = "SELECT collaborator_id FROM page_colabs WHERE page_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$this->id]);
		$colabs = array();
		if (is_array($rows) && count($rows) > 0) {
			foreach($rows as $i=>$colab_id) {
				$colabs[] = new User($colab_id);
			}
		}
		return $colabs;
	}

	public function is_colab($user) {
		$colabs = $this->get_colabs();
		foreach ($colabs as $i => $colab) {
			if ($colab->$id == $user->$id) return TRUE;
		}
		return FALSE;
	}

	public function add_collaborator($user) {
		$this->unblacklist_user($user);
		if(!$this->is_colab($user)) MYSQL::run_query("INSERT INTO page_colabs (page_id, collaborator_id) VALUES (?, ?)", 'ii', [&$this->id, &$user->id]);
	}

	public function remove_collaborator($user) {
		if ($this->is_colab($user)) {
			$sql = "DELETE FROM page_colabs WHERE page_id = ? AND collaborator_id = ?";
			MYSQL::run_query($sql, 'ii', [&$this->id, &$user->id]);
		}
	}

	public function get_listed_users() {
		$sql = "SELECT user_id FROM page_whitelists WHERE page_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$this->id]);
		$users = array();
		if (is_array($rows) && count($rows) > 0) {
			foreach($rows as $i=>$user) {
				$users[] = new User($user['user_id']);
			}
		}
		return $users; 
	}

	public function is_listed_user($user) {
		$listed_users = $this->get_listed_users();
		foreach ($listed_users as $i => $listed_user) {
			if ($listed_user->$id == $user->$id) return TRUE;
		}
		return FALSE;
	}

	public function list_user($user, $color=FALSE) {
		$sql = "INSERT INTO page_whitelists (page_id, user_id, color) VALUES (?, ?, ?)";
		MYSQL::run_query($sql, 'iii', [&$this->id, &$user->id, &$color]);
	}

	public function unlist_user($user) {
		$sql = "DELETE FROM page_whitelists WHERE page_id = ? AND user_id = ?";
		MYSQL::run_query($sql, 'ii', [&$this->id, &$user->id])
	}

	public function get_whitelist() {
		$sql = "SELECT user_id FROM page_whitelists WHERE page_id = ? AND color = ?";
		$rows = MYSQL::run_query($sql, 'ii', [&$this->id, TRUE]);
		$users = array();
		if (is_array($rows) && count($rows) > 0) {
			foreach($rows as $i=>$user) {
				$users[] = new User($user['user_id']);
			}
		}
		return $users;
	}

	public function is_whitelisted_user($user){
		$listed_users = $this->get_whitelist();
		foreach ($listed_users as $i => $listed_user) {
			if ($listed_user->$id == $user->$id) return TRUE;
		}
		return FALSE;
	}

	public function whitelist_user($user) {
		$this->unblacklist_user($user);
		if ($this->is_whitelisted_user($user) == FALSE) $this->list_user($user, TRUE);
	}

	public function unwhitelist_user($user) {
		if ($this->is_whitelisted_user($user)) unlist_user($user);
	}

	public function get_blacklist() {
		$sql = "SELECT user_id FROM page_whitelists WHERE page_id = ? AND color = ?";
		$rows = MYSQL::run_query($sql, 'ii', [&$this->id, FALSE]);
		$users = array();
		if (is_array($rows) && count($rows) > 0) {
			foreach($rows as $i=>$user) {
				$users[] = new User($user['user_id']);
			}
		}
		return $users;
	}

	public function is_blacklisted_user($user){
		$listed_users = $this->get_blacklist();
		foreach ($listed_users as $i => $listed_user) {
			if ($listed_user->$id == $user->$id) return TRUE;
		}
		return FALSE;
	}

	public function blacklist_user($user) {
		$this->unwhitelist_user($user);
		$this->remove_collaborator($user);
		if ($this->is_blacklisted_user($user) == FALSE) $this->list_user($user, FALSE);
	}

	public function unblacklist_user($user) {
		if ($this->is_blacklisted_user($user)) unlist_user($user);
	}

	public function add_child($child_page) {
		if ($this->is_child($child_page, TRUE) == FALSE && $this->is_parent($child_page, TRUE) == FALSE) {
			$sql = "INSERT INTO sub_pages (parent_id, child_id) VALUES (?, ?)";
			MYSQL::run_query($sql, 'ii', [&$this->id, &$child_page->id]);
		}
	}

	public function get_parents($recursive=FALSE) {
		$sql = "SELECT parent_id FROM sub_pages WHERE child_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$this->id]);
		$parent_ids = Ds\Set();
		if (is_empty($rows) == FALSE) {
			foreach ($rows as $i => $row) {
				$parent_ids->add($row['parent_id']);
				$new_parent = new Page($row['parent_id']);
				if ($recursive){
					foreach ($new_parent->get_parents($recursive) as $j=>$grandparent){
						$parent_ids->add($grandparent->$id);
					}
				}
			}
		}
		$parents = array();
		foreach($parent_ids as $i=>$parent_id) $parents[] = new Page($parent_id);
	}

	public function get_parent() {
		$parents = $this->get_parents(FALSE);
		return $parents[0];
	}

	public function is_parent($page, $recursive=FALSE) {
		$parents = $this->get_parents($recursive);
		foreach ($parents as $i => $parent) {
			if ($parent->id == $page->id) return TRUE;
		}
		return FALSE;
	}

	public function has_parent(){
		$parents = $this->get_parents(FALSE);
		return !is_empty($parents);
	}

	public function get_children() {
		$sql = "SELECT child_id FROM sub_pages WHERE parent_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$this->id]);
		$child_ids = Set();
		if (is_empty($rows) == FALSE) {
			foreach ($rows as $i => $row) {
				$child_ids->add($row['child_id']);
				$new_child = new Page($row['child_id']);
				if ($recursive){
					foreach ($new_child->get_parents($recursive) as $j=>$grandchild){
						$child_ids->add($grandchild->$id);
					}
				}
			}
		}
		$children = array();
		foreach($child_ids as $i=>$child_id) $children[] = new Page($child_id);
		return $children;
	}

	public function is_child($page, $recursive=FALSE) {
		$children = $this->get_parents($recursive);
		foreach ($children as $i => $child) {
			if ($child->id == $page->id) return TRUE;
		}
		return FALSE;
	}

	public function add_child($child) {
		if ($child->has_parent()) ERRORS::log(ERRORS::PAGE_ERROR, "Child page %d already has parent\n", $child->id);
		MYSQL::run_query("INSERT INTO sub_pages (parent_id, child_id) VALUES (?, ?)", 'ii', [&$this->id, &$child->id]);
	}

	public function has_children(){
		$children = $this->get_children(FALSE);
		return !is_empty($children);
	}

	public function get_level(){
		$parents = $this->get_parents(TRUE);
		return count($parents);
	}

	public function get_book(){
		$book = $this;
		while($book && !$book->is_book()){
			$book = $book->get_parent();
		}
		return $book;
	}

	public function get_chapter(){
		$chapter = $this;
		while($chapter && !$chapter->is_chapter()){
			$chapter = $chapter->get_parent();
		}
		return $chapter;
	}

	public function is_book() {
		return !$this->has_parent();
	}

	public function is_chapter() {
		return ($this->get_level() == 1);
	}

	public function get_text() {
		$sql = "SELECT content FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', $this->id);
		if (is_empty($rows) == FALSE) {
			return $rows[0]['content'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::get_text()", $this->id));
	}

	public function set_text($text) {
		if (Pages::validate_text($text) == FALSE) ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Text format invalid:\n ---------- \n%s\n ---------- \n --> Pages::set_text()", $text));
		$sql = "UPDATE pages SET content = ? WHERE id = ?";
		MYSQL::run_query($sql, 'bi', [&$text, &$this->id]);
	}

	public function get_title() {
		$sql = "SELECT title FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', $this->id);
		if (is_empty($rows) == FALSE) {
			return $rows[0]['title'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::get_title()", $this->id));
	}

	public function set_title($title) {
		if (Pages::validate_title($title) == FALSE) ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Title format invalid:\n ---------- \n%s\n ---------- \n --> Pages::set_title()", $title));
		$sql = "UPDATE pages SET title = ? WHERE id = ?";
		MYSQL::run_query($sql, 'si', [&$title, &$this->id]);
	}

	public function is_locked() {
		$sql = "SELECT locked FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', $this->id);
		if (is_empty($rows) == FALSE) {
			return $rows[0]['locked'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::is_locked()", $this->id));
	}

	public function set_locked($locked) {
		MYSQL::run_query("UPDATE pages SET locked = ? WHERE id = ?", 'si', [&$locked, &$this->id]);
	}

	public function lock() {
		$this->set_locked(TRUE);
	}

	public function unlock() {
		$this->set_locked(FALSE);
	}

	public function is_opened() {
		$sql = "SELECT opened FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', $this->id);
		if (is_empty($rows) == FALSE) {
			return $rows[0]['opened'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::is_opened()", $this->id));
	}

	public function set_opened($opened) {
		MYSQL::run_query("UPDATE pages SET open = ? WHERE id = ?", 'si', [&$opened, &$this->id]);
	}

	public function open() {
		$this->set_opened(TRUE);
	}

	public function close() {
		$this->set_opened(FALSE);
	}

	public function get_selector() {
		$selector = MYSQL::run_query("SELECT selector FROM pages WHERE id = ?", 'i', [&$this->id])[0]["selector"];
		return $selector;
	}

	// Hashable functions

	public function equals($other_page) {
		return ($this->id == $other_page->id);
	}

	public function hash() {
		return $this->selector;
	}
}

?>