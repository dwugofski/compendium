<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../util/mysql.php");

class Page {

// --------------------------------------------------
// Begin static features
// --------------------------------------------------
	public static $columns = [
		"id",
		"title",
		"description",
		"content",
		"author_id",
		"locked",
		"opened",
		"selector",
		"created",
		"modified",
		"parent_id"
	];

	private static function _find_by($colname, $val, $type="i") {
		if (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_find_by() No column name entered");
		if (is_null($val)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_find_by() No value entered");
		if (empty($type)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_find_by() No type entered");
		if (in_array($colname, self::$columns)) {
			$sql = "SELECT id FROM pages WHERE ".$colname." = ?";
			return MYSQL::run_query($sql, $type, [&$val]);
		} else ERRORS::log(ERRORS::PAGE_ERROR, "Page::_find_by() column \"%s\" not recognized", $colname);
	}

	private static function _find($page_ident) {
		$page_identifiers = [
			['ident' => ["id"], 'colname' => "id", 'type' => 'i'],
			['ident' => ["selector", "sel"], 'colname' => "selector", 'type' => 's'],
			['ident' => ["title"], 'colname' => "title", 'type' => 's']
		];
		$rows = null;

		if (empty($page_ident)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_find() No page_ident entered");
		if (is_array($page_ident) && !empty($page_ident)) {
			foreach ($page_ident as $identifier_request => $value) {
				foreach ($page_identifiers as $identifier) {
					if (in_array($identifier_request, $identifier['ident'])) {
						$rows = self::_find_by($identifier['colname'], $value, $identifier['type']);
						break;
					}
				}
				if (isset($rows)) break;
			}
			if (is_null($rows)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_find() Cannot find an identifier for page_ident of %s", json_encode($page_ident));
		} else {
			$rows = self::_find_by("id", $page_ident, 'i');
		}

		return $rows;
	}

	public static function compare($page1, $page2) {
		if ($page1->title == $page2->title) return 0;
		return ($page1->title < $page2->title) ? -1 : 1;
	}

	public static function validate_title($title) {
		return is_string($title);
	}

	public static function validate_description($description) {
		return is_string($description);
	}

	public static function validate_text($text) {
		return is_string($text);
	}

	public static function get_user_pages($user) {
		$sql = "SELECT id FROM pages WHERE author_id = ? ORDER BY created";
		$rows = MYSQL::run_query($sql, 'i', [$user->id]);
		$ret = array();
		if (is_array($rows)) {
			foreach($rows as $i=>$row) {
				$ret[] = new Page($row['id']);
			}
		}
		return $ret;
	}

	public static function get_user_books($user) {
		$sql = "SELECT id FROM pages WHERE author_id = ? AND parent_id IS NULL";
		$rows = MYSQL::run_query($sql, 'i', [$user->id]);
		$ret = array();
		if (is_array($rows)) {
			foreach($rows as $i=>$row) {
				$ret[] = new Page($row['id']);
			}
		}
		return $ret;
	}

	public static function is_page($page_ident) {
		$rows = self::_find($page_ident);
		if (!empty($rows) && is_array($rows)) return true;
		else return false;
	}

	public static function create_new_page($author, $title="Untitled", $description="", $text=""){
		if ($author->has_permission('epo') == false) ERRORS::log(ERRORS::PERMISSIONS_ERROR, "User '%d' cannot create pages", $author->id);
		if (self::validate_title($title) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Invalid page title: %s", $title);
		if (self::validate_description($description) == false) ERRORS::log(ERRORS::PAGE_ERROR,"Invalid page description: %s", $description);
		if (self::validate_text($text) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Invalid page text \n%s", $text);

		$selector = self::make_selector();
		$sql = "INSERT INTO pages (author_id, title, description, content, selector) VALUES (?, ?, ?, ?, ?)";
		MYSQL::run_query($sql, 'issss', [$author->id, &$title, &$description, &$text, &$selector]);
		return new Page(MYSQL::get_index());
	}

	private static function make_selector() {
		$selector = bin2hex(openssl_random_pseudo_bytes(12));
		$unique = true;
		MYSQL::prepare("SELECT id FROM pages WHERE selector = ?", "s", [&$selector]);
		for ($i=0; $i<10; $i+=1) {
			if (!empty(MYSQL::execute())) {
				$unique = false;
			} else {
				$selector = bin2hex(openssl_random_pseudo_bytes(12));
			}
		}
		if ($unique) return $selector;
		else ERRORS::log(ERRORS::PAGE_ERROR, "Page:make_selector() Could not establish a unique selector for pages");
	}

// --------------------------------------------------
// Begin non-static features
// --------------------------------------------------
	private $id;

	private function _get($colname, $count=null) {
		$ret = null;
		if (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_get() No column name entered");

		if (in_array($colname, $this::$columns)) {
			$rows = MYSQL::run_query("SELECT ".$colname." FROM pages WHERE id = ?", 'i', [$this->id]);
			if (is_array($rows) && count($rows) > 0) {
				if (isset($count)) {
					$count = (($count <= count($rows)) && ($count > 0)) ? $count : count($rows);
					$ret = [];
					for ($i=0; $i<$count; $i=$i+1) {
						$ret[] = $rows[$i][$colname];
					}
				} else {
					$ret = $rows[0][$colname];
				}
			}
			else ERRORS::log(ERRORS::PAGE_ERROR, "Page::_get() Page '%d' not found when trying to get '%s'", $this->id, $colname);
		} else ERRORS::log(ERRORS::PAGE_ERROR, "Page::_get() Column '%s' not recognized", $colname);

		return $ret;
	}

	private function _set($colname, $val, $type) {
		if (empty($colname)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_set() No column name entered");
		if (is_null($val)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_set() No value entered");
		if (empty($type)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::_set() No type entered");

		if (in_array($colname, $this::$columns)) {
			$rows = MYSQL::run_query("UPDATE pages SET ".$col_name." = ? WHERE id = ?", $type.'i', [$val, $this->id]);
		} else ERRORS::log(ERRORS::PAGE_ERROR, "Page::_set() Column '%s' not recognized", $colname);
	}

	public function __construct($page_ident) {
		$rows = $this->_find($page_ident);
		if (!empty($rows)) {
			$this->id = $rows[0]['id'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, "Page::__construct() could not find page with identifier %s", json_encode($page_ident));
	}

	public function __get($name) {
		switch($name){
			case "all_children":
				return $this->get_children(false);
			case "author":
				return $this->_get("author_id");
			case "blacklist":
				return $this->get_blacklist();
			case "book":
				return $this->get_book();
			case "chapter":
				return $this->get_chapter();
			case "children":
				return $this->get_children(false);
			case "colabs":
			case "collabs":
			case "collaborators":
				return $this->get_colabs();
			case "description":
				return $this->_get("description");
			case "created":
				return $this->_get("created");
			case "edited":
			case "modified":
				return $this->_get("modified");
			case "id":
				return $this->id;
			case "isBook":
				return $this->is_book();
			case "isChapter":
				return $this->is_chapter();
			case "level":
				return $this->get_level();
			case "locked":
				return $this->is_locked();
			case "opened":
				return $this->is_opened();
			case "parent":
				return $this->get_parent();
			case "parents":
				return $this->get_parents(true);
			case "path":
				return $this->get_path();
			case "selector":
				return $this->_get("selector");
			case "text":
				return $this->_get("content");
			case "title":
				return $this->_get("title");
			case "whitelist":
				return $this->get_whitelist();
			default:
				ERRORS::log(ERRORS::PAGE_ERROR, "Page::__get() Attempted to get unknown property '%s' of page", $name);
		}
	}

	public function __set($name, $value) {
		switch($name) {
			case "locked":
				if ($value) $this->lock();
				else $this->unlock();
				break;
			case "opened":
				if ($value) $this->open();
				else $this->close();
				break;
			case "parent":
				$this->set_parent($value);
				break;
			case "text":
				$this->set_text($value);
				break;
			case "title":
				$this->set_title($value);
				break;
			case "all_children":
			case "author":
			case "book":
			case "chapter":
			case "children":
			case "colabs":
			case "collabs":
			case "collaborators":
			case "created":
			case "edited":
			case "id":
			case "isBook":
			case "isChapter":
			case "level":
			case "parent":
			case "parents":
			case "path":
			case "selector":
				ERRORS::log(ERRORS::PAGE_ERROR, "Page::__set() Attempted to set read-only property '%s' of page", $name);
				break;
			default:
				ERRORS::log(ERRORS::PAGE_ERROR, "Page::__set() Attempted to set unknown property '%s' of page", $name);
		}
	}

	public function can_see($user) {
		if ($user->has_permission(User::ACT_VIEW_ALL_PAGES)) return true;
		if ($this->author == $user->id && $user->has_permission(User::ACT_VIEW_OWN_PAGES)) return true;
		if ($this->is_blacklisted_user($user)) return false;
		if ($this->is_locked() == true && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return true;
		if ($this->is_colab($user) && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return true;
		if ($this->is_whitelisted_user($user) && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return true;

		return false;
	}

	public function can_edit($user) {
		if ($user->has_permission(User::ACT_EDIT_ALL_PAGES)) return true;
		if ($this->author == $user->id && $user->has_permission(User::ACT_EDIT_OWN_PAGES)) return true;
		if ($this->is_blacklisted_user($user)) return true;
		if ($this->is_opened() && $user->has_permission(User::ACT_EDIT_OPEN_PAGES)) return true;
		if ($this->is_colab($user) && $user->has_permission(User::ACT_EDIT_OPEN_PAGES)) return true;
		elseif ($this->is_colab($user)) echo(sprintf("Collaborator %s cannot edit the page\n"));

		return false;
	}

	public function can_lock($user) {
		if ($user->has_permission(User::ACT_LOCK_ALL_PAGES)) return true;
		if ($this->author == $user->id && $user->has_permission(User::ACT_LOCK_OWN_PAGES)) return true;

		return false;
	}

	public function can_open($user) {
		if ($user->has_permission(User::ACT_OPEN_ALL_PAGES)) return true;
		if ($this->author == $user->id && $user->has_permission(User::ACT_OPEN_OWN_PAGES)) return true;

		return false;
	}

	public function get_colabs() {
		$sql = "SELECT collaborator_id FROM page_colabs WHERE page_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [$this->id]);
		$colabs = array();
		if (is_array($rows) && count($rows) > 0) {
			foreach($rows as $i=>$colab_id) {
				$colabs[] = new User($colab_id['collaborator_id']);
			}
		}
		return $colabs;
	}

	public function is_colab($user) {
		$colabs = $this->get_colabs();
		foreach ($colabs as $i => $colab) {
			if ($colab->id == $user->id) return true;
		}
		return false;
	}

	public function add_collaborator($user) {
		$this->unblacklist_user($user);
		if(!$this->is_colab($user)) MYSQL::run_query("INSERT INTO page_colabs (page_id, collaborator_id) VALUES (?, ?)", 'ii', [$this->id, $user->id]);
	}

	public function remove_collaborator($user) {
		if ($this->is_colab($user)) {
			$sql = "DELETE FROM page_colabs WHERE page_id = ? AND collaborator_id = ?";
			MYSQL::run_query($sql, 'ii', [$this->id, $user->id]);
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
			if ($listed_user->$id == $user->$id) return true;
		}
		return false;
	}

	public function list_user($user, $color=false) {
		$sql = "INSERT INTO page_whitelists (page_id, user_id, color) VALUES (?, ?, ?)";
		MYSQL::run_query($sql, 'iii', [$this->id, $user->id, &$color]);
	}

	public function unlist_user($user) {
		$sql = "DELETE FROM page_whitelists WHERE page_id = ? AND user_id = ?";
		MYSQL::run_query($sql, 'ii', [$this->id, $user->id]);
	}

	public function get_whitelist() {
		$sql = "SELECT user_id FROM page_whitelists WHERE page_id = ? AND color = ?";
		$rows = MYSQL::run_query($sql, 'ii', [$this->id, true]);
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
			if ($listed_user->id == $user->id) return true;
		}
		return false;
	}

	public function whitelist_user($user) {
		$this->unblacklist_user($user);
		if ($this->is_whitelisted_user($user) == false) $this->list_user($user, true);
	}

	public function unwhitelist_user($user) {
		if ($this->is_whitelisted_user($user)) $this->unlist_user($user);
	}

	public function get_blacklist() {
		$sql = "SELECT user_id FROM page_whitelists WHERE page_id = ? AND color = ?";
		$rows = MYSQL::run_query($sql, 'ii', [&$this->id, false]);
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
			if ($listed_user->id == $user->id) return true;
		}
		return false;
	}

	public function blacklist_user($user) {
		$this->unwhitelist_user($user);
		$this->remove_collaborator($user);
		if ($this->is_blacklisted_user($user) == false) $this->list_user($user, false);
	}

	public function unblacklist_user($user) {
		if ($this->is_blacklisted_user($user)) $this->unlist_user($user);
	}

	public function get_parents($recursive=false) {
		$parent_id = $this->_get("parent_id");
		$parent_ids = array();
		if (isset($parent_id)) {
			$parent_ids[$parent_id] = $parent_id;
			if ($recursive){
				$new_parent = new Page($parent_id);
				foreach ($new_parent->get_parents($recursive) as $j=>$grandparent){
					$parent_ids[$grandparent->id] = $grandparent->id;
				}
			}
		}
		$parents = array();
		foreach($parent_ids as $i=>$pid) $parents[] = new Page($pid);
		return $parents;
	}

	public function get_parent() {
		$parents = $this->get_parents(false);
		if (!empty($parents)) return $parents[0];
		else return NULL;
	}

	public function set_parent($parent) {
		MYSQL::run_query("UPDATE pages SET parent_id = ? WHERE id = ?", 'ii', [&$parent->id, &$this->id]);
	}

	public function is_parent($page, $recursive=false) {
		$parents = $this->get_parents($recursive);
		foreach ($parents as $i => $parent) {
			if ($parent->id == $page->id) return true;
		}
		return false;
	}

	public function has_parent(){
		$parents = $this->get_parents(false);
		return !empty($parents);
	}

	public function get_children($recursive=false) {
		$sql = "SELECT id FROM pages WHERE parent_id = ?";
		$rows = MYSQL::run_query($sql, 'i', [&$this->id]);
		$child_ids = array();
		if (!empty($rows)) {
			foreach ($rows as $i => $row) {
				$child_ids[$row['id']] = $row['id'];
				if ($recursive){
					$new_child = new Page($row['id']);
					foreach ($new_child->get_children($recursive) as $j=>$grandchild){
						$child_ids[$grandchild->id] = $grandchild->id;
					}
				}
			}
		}
		$children = array();
		foreach($child_ids as $i=>$child_id) $children[] = new Page($child_id);
		return $children;
	}

	public function is_child($page, $recursive=false) {
		$children = $this->get_children($recursive);
		foreach ($children as $i => $child) {
			if ($child->id == $page->id) return true;
		}
		return false;
	}

	public function add_child($child) {
		if ($child->has_parent()) ERRORS::log(ERRORS::PAGE_ERROR, "Child page %d already has parent\n", $child->id);
		MYSQL::run_query("UPDATE pages SET parent_id = ? WHERE id = ?", 'ii', [&$this->id, &$child->id]);
	}

	public function has_children(){
		$children = $this->get_children(false);
		return !empty($children);
	}

	public function get_level(){
		$parents = $this->get_parents(true);
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

	public function get_path() {
		$titles = $this->title;
		$selectors = [$this->selector];

		if ($this->has_parent()) {
			$parent_path = $this->get_parent()->path;
			$titles = $parent_path['titles'] . "/" . $titles;
			array_splice($selectors, 0, 0, $parent_path['selectors']);
		} else {
			$author = new User($this->author);
			$titles = "u/" . $author->username . "/" . $titles;
		}

		return ['titles' => $titles, 'selectors' => $selectors];
	}

	public function set_text($text) {
		if (Pages::validate_text($text) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Page::set_text() Text format invalid for \n%s", $text);
		else $this->_set("content", $text, 'b');
	}

	public function set_title($title) {
		if (Pages::validate_title($title) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Page::set_title() Title format invalid for %s", $title);
		else $this->_set("title", $title, 's');
	}

	public function set_description($desc) {
		if (Pages::validate_description($desc) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Page::set_description() Description format invalid for %s", $desc);
		else $this->_set("description", $desc, 's');
	}

	public function is_locked() {
		$sql = "SELECT locked FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', [$this->id]);
		if (empty($rows) == false) {
			return $rows[0]['locked'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::is_locked()", $this->id));
	}

	public function set_locked($locked) {
		MYSQL::run_query("UPDATE pages SET locked = ? WHERE id = ?", 'ii', [&$locked, $this->id]);
	}

	public function lock() {
		$this->set_locked(true);
	}

	public function unlock() {
		$this->set_locked(false);
	}

	public function is_opened() {
		$sql = "SELECT opened FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', [$this->id]);
		if (empty($rows) == false) {
			return $rows[0]['opened'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::is_opened()", $this->id));
	}

	public function set_opened($opened) {
		MYSQL::run_query("UPDATE pages SET opened = ? WHERE id = ?", 'ii', [&$opened, $this->id]);
	}

	public function open() {
		$this->set_opened(true);
	}

	public function close() {
		$this->set_opened(false);
	}
}

?>