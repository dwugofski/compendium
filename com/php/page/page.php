<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../util/mysql.php");
include_once(__DIR__."/../util/comp_accessor.php");

class Page extends CompAccessor {

// --------------------------------------------------
// Begin static features
// --------------------------------------------------
	const TABLE_NAME = 'pages';
	const PRIMARY_KEY = 'id';

	const COLUMN_NAMES = [
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

	const COLUMN_TYPES = [
		"id" => 'i',
		"title" => 's',
		"description" => 's',
		"content" => 'b',
		"author_id" => 'i',
		"locked" => 'i',
		"opened" => 'i',
		"selector" => 's',
		"created" => 's',
		"modified" => 's',
		"parent_id" => 'i'
	];
	
	const IDENTIFIERS = [
		'id' => 'id',
		'selector' => 'selector',
		'sel' => 'selector'
	];

	static public function compare($page1, $page2) {
		if ($page1->title == $page2->title) return 0;
		return ($page1->title < $page2->title) ? -1 : 1;
	}

	static public function validate_title($title) {
		return is_string($title);
	}

	static public function validate_description($description) {
		return is_string($description);
	}

	static public function validate_text($text) {
		return is_string($text);
	}

	static public function get_user_pages($user) {
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

	static public function get_user_books($user) {
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

	static public function is_page($value, $identifier='id') {
		return self::is($value, $identifier);
	}

	static public function create_new_page($author, $title="Untitled", $description="", $text=""){
		if ($author->has_permission('epo') == false) ERRORS::log(ERRORS::PERMISSIONS_ERROR, "User '%d' cannot create pages", $author->id);
		if (self::validate_title($title) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Invalid page title: %s", $title);
		if (self::validate_description($description) == false) ERRORS::log(ERRORS::PAGE_ERROR,"Invalid page description: %s", $description);
		if (self::validate_text($text) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Invalid page text \n%s", $text);

		$selector = self::_make_selector();
		$sql = "INSERT INTO pages (author_id, title, description, content, selector) VALUES (?, ?, ?, ?, ?)";
		MYSQL::run_query($sql, 'issss', [$author->id, &$title, &$description, &$text, &$selector]);
		return new Page(MYSQL::get_index());
	}

	static public function delete_page($value, $identifier='id') {
		if (is_null($value)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::delete_page() No value entered");
		elseif (empty($identifier)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::delete_page() No identifier entered");

		$rows = self::_find($value, $identifier);
		if (empty($rows)) ERRORS::log(ERRORS::PAGE_ERROR, "Page::delete_page() page '%s' => '%s' not found", $identifier, $value);
		else {
			$page_id = $rows[0]['id'];
			MYSQL::run_query("DELETE FROM pages WHERE id = ?", 'i', [$page_id]);
		}
	}

// --------------------------------------------------
// Begin non-static features
// --------------------------------------------------
	public function __construct($value, $identifier='id') {
		parent::__construct($value, $identifier);
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
			case "created":
				return strtotime($this->_get('created'));
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
			case "modified":
				return strtotime($this->_get('modified'));
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
			case "modified":
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
		elseif ($this->author == $user->id && $user->has_permission(User::ACT_VIEW_OWN_PAGES)) return true;
		elseif ($this->is_blacklisted_user($user)) return false;
		elseif ($this->is_locked() == true && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return true;
		elseif ($this->is_colab($user) && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return true;
		elseif ($this->is_whitelisted_user($user) && $user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) return true;

		return false;
	}

	public function can_edit($user) {
		if ($user->has_permission(User::ACT_EDIT_ALL_PAGES)) return true;
		elseif ($this->author == $user->id && $user->has_permission(User::ACT_EDIT_OWN_PAGES)) return true;
		elseif ($this->is_blacklisted_user($user)) return false;
		elseif ($this->is_opened() && $user->has_permission(User::ACT_EDIT_OPEN_PAGES)) return true;
		elseif ($this->is_colab($user) && $user->has_permission(User::ACT_EDIT_OPEN_PAGES)) return true;
		elseif ($this->is_colab($user)) echo(sprintf("Collaborator %s cannot edit the page\n"));

		return false;
	}

	public function can_lock($user) {
		if ($user->has_permission(User::ACT_LOCK_ALL_PAGES)) return true;
		elseif ($this->author == $user->id && $user->has_permission(User::ACT_LOCK_OWN_PAGES)) return true;

		return false;
	}

	public function can_open($user) {
		if ($user->has_permission(User::ACT_OPEN_ALL_PAGES)) return true;
		elseif ($this->author == $user->id && $user->has_permission(User::ACT_OPEN_OWN_PAGES)) return true;

		return false;
	}

	public function can_comment($user) {
		if ($user->has_permission(User::ACT_ADD_ALL_COMMENTS)) return true;
		elseif ($this->author == $user->id && $user->has_permission(User::ACT_ADD_OWN_COMMENTS)) return true;
		elseif ($this->can_edit($user) && $user->has_permission(User::ACT_ADD_OPEN_COMMENTS)) return true;
		elseif ($this->can_see($user) && $user->has_permission(User::ACT_ADD_UNLOCKED_COMMENTS)) return true;

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
		$parent_ids = array();
		if ($this->has_parent()) {
			$parent = $this->parent;
			$parent_ids[$parent->id] = $parent->id;
			if ($recursive && $parent->has_parent()) {
				foreach ($parent->get_parents($recursive) as $j=>$grandparent){
					$parent_ids[$grandparent->id] = $grandparent->id;
				}
			}
		}
		$parents = array();
		foreach($parent_ids as $i=>$pid) $parents[] = new Page($pid);
		return $parents;
	}

	public function get_parent() {
		$parent_id = $this->_get("parent_id");
		if (isset($parent_id)) return new Page($parent_id);
		else return null;
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
		return $this->get_parent() !== null;
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
		while(isset($book) && !$book->is_book()){
			$book = $book->get_parent();
		}
		return $book;
	}

	public function get_chapter(){
		$chapter = $this;
		while(isset($chapter) && !$chapter->is_chapter()){
			$chapter = $chapter->parent;
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
			$parent_path = $this->parent->path;
			$titles = $parent_path['titles'] . "/" . $titles;
			array_splice($selectors, 0, 0, $parent_path['selectors']);
		} else {
			error_log($this->title . " does not have a parent");
			$author = new User($this->author);
			$titles = "u/" . $author->username . "/" . $titles;
		}

		return ['titles' => $titles, 'selectors' => $selectors];
	}

	public function set_text($text) {
		if (Pages::validate_text($text) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Page::set_text() Text format invalid for \n%s", $text);
		else $this->_set("content", $text);
	}

	public function set_title($title) {
		if (Pages::validate_title($title) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Page::set_title() Title format invalid for %s", $title);
		else $this->_set("title", $title);
	}

	public function set_description($desc) {
		if (Pages::validate_description($desc) == false) ERRORS::log(ERRORS::PAGE_ERROR, "Page::set_description() Description format invalid for %s", $desc);
		else $this->_set("description", $desc);
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