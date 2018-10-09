<?php

include_once(dirname(__DIR__)."\errors.php");
include_once(dirname(__DIR__)."\mysql.php");

class Page implements Hashable {
	public $id;

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

		$sql = "INSERT INTO pages (author_id, title, content) VALUES (?, ?, ?)";
		MYSQL::run_query($sql, 'isb', [&$author, &$title, &$text]);
		return MYSQL::get_index();
	}

	public function __construct($pageid) {
		if (self::is_page($pageid)) $this->id = $pageid;
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Page '%d' not found --> Page::__construct()", $pageid));
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
		$colabs = $this->get_whitelist();
		foreach ($colabs as $i => $colab) {
			if ($colab->$id == $user->$id) return TRUE;
		}
		return FALSE;
	}

	public function add_collaborator($user) {
		$this->unblacklist_user($user);
		MYSQL::run_query("INSERT INTO page_colabs (page_id, collaborator_id) VALUES (?, ?)", 'ii', [&$this->id, &$user->id]);
	}

	public function remove_collaborator($user) {
		$sql = "DELETE FROM page_colabs WHERE page_id = ? AND collaborator_id = ?";
		MYSQL::run_query($sql, 'ii', [&$this->id, &$user->id]);
	}

	public function get_listed_users() {
		$sql = "SELECT user_id FROM page_whitelist WHERE page_id = ?";
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
		$sql = "INSERT INTO page_whitelist (page_id, user_id, color) VALUES (?, ?, ?)";
		MYSQL::run_query($sql, 'iii', [&$this->id, &$user->id, &$color]);
	}

	public function unlist_user($user) {
		$sql = "DELETE FROM page_whitelist WHERE page_id = ? AND user_id = ?";
		MYSQL::run_query($sql, 'ii', [&$this->id, &$user->id])
	}

	public function get_whitelist() {
		$sql = "SELECT user_id FROM page_whitelist WHERE page_id = ? AND color = ?";
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
		$sql = "SELECT user_id FROM page_whitelist WHERE page_id = ? AND color = ?";
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
		$parent_ids = Set();
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

	public function has_children(){
		$children = $this->get_children(FALSE);
		return !is_empty($children);
	}

	public function is_book() {
		return !$this->has_parent();
	}

	public function get_text() {
		$sql = "SELECT content FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', $this->id);
		if (is_empty($rows) == FALSE) {
			return $rows[0]['content'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::get_text()", $this->id));
	}

	public function set_text($title) {
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

	public function lock() {
		$sql = "UPDATE pages SET locked = ? WHERE id = ?";
		MYSQL::run_query($sql, 'si', [TRUE, &$this->id]);
	}

	public function unlock() {
		$sql = "UPDATE pages SET locked = ? WHERE id = ?";
		MYSQL::run_query($sql, 'si', [FALSE, &$this->id]);
	}

	public function is_opened() {
		$sql = "SELECT opened FROM pages WHERE id = ?";
		$rows = MYSQL::run_query($sql, 'i', $this->id);
		if (is_empty($rows) == FALSE) {
			return $rows[0]['opened'];
		}
		else ERRORS::log(ERRORS::PAGE_ERROR, sprintf("Could not find page '%d' --> Page::is_opened()", $this->id));
	}

	public function open() {
		$sql = "UPDATE pages SET open = ? WHERE id = ?";
		MYSQL::run_query($sql, 'si', [TRUE, &$this->id]);
	}

	public function close() {
		$sql = "UPDATE pages SET open = ? WHERE id = ?";
		MYSQL::run_query($sql, 'si', [FALSE, &$this->id]);
	}

	// Hashable functions

	public function equals($other_page) {
		return ($this->id == $other_page->id);
	}

	public function hash() {
		return hash('ripemd160', $this->id);
	}
}

?>