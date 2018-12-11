<?php

include_once(__DIR__."/../util/errors.php");
include_once(__DIR__."/../util/mysql.php");
include_once(__DIR__."/../util/comp_accessor.php");
include_once(__DIR__."/../user/user.php");
include_once(__DIR__."/../page/page.php");

class Comment extends CompAccessor {

// --------------------------------------------------
// Begin static features
// --------------------------------------------------
	// CompAccessor necessary properties
	const TABLE_NAME = 'comments';
	const PRIMARY_KEY = 'id';

	const COLUMN_NAMES = [
		'id',
		'content',
		'author_id',
		'page_id',
		'selector',
		'created',
		'modified',
		'parent_id',
		'deleted'
	];

	const COLUMN_TYPES = [
		'id' => 'i',
		'content' => 's',
		'author_id' => 'i',
		'page_id' => 'i',
		'selector' => 's',
		'created' => 's',
		'modified' => 's',
		'parent_id' => 'i',
		'deleted' => 'i'

	];

	const IDENTIFIERS = [
		'id' => 'id',
		'selector' => 'selector',
		'sel' => 'selector'
	];

	static public function create_new_comment($author, $page, $content, $parent=null) {
		if (!User::is($author)) ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::create_new_comment() Cannot find author with id '%d'", $author->id);
		elseif (!Page::is($page)) ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::create_new_comment() Cannot find page with id '%d'", $page->id);
		elseif (!Page::can_comment($author)) ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::create_new_comment() User %d cannot edit page %d", $author->id, $page->id);
		elseif (isset($parent) && !self::is($parent)) ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::create_new_comment() Parent with id '%d' cannot be found", $parent->id);
		elseif (!self::validate_content($content)) ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::create_new_comment() Content string invalid: \n'%s'", $content);
		else {
			$selector = self::_make_selector();
			$parent = (isset($parent)) ? $parent->id : null;
			$author = (is_a($author, 'User')) ? $author->id : $author;
			$page = (is_a($page, 'Page')) ? $page->id : $page;
			MYSQL::run_query(
				"INSERT INTO comments (author_id, page_id, content, selector, parent_id) VALUES (?, ?, ?, ?, ?)",
				'iissi',
				[$author, $page, $content, $selector, $parent]
			);
			return new Comment($selector, 'sel');
		}

		return null;
	}

	static public function validate_content($content) {
		return Page::validate_text($content);
	}

// --------------------------------------------------
// Begin non-static features
// --------------------------------------------------

	public function __construct($value, $identifier='id') {
		parent::__construct($value, $identifier);
	}

	public function __get($name) {
		switch($name){
			case "author":
				return ($this->deleted) ? null : new User($this->_get('author_id'), 'id');
			case "children":
				return $this->get_children(false);
			case "content":
				return $this->_get('content');
			case "created":
				return strtotime($this->_get('created'));
			case "deleted":
				return ($this->_get('deleted') == 1) ? true : false;
			case "edited":
			case "modified":
				return strtotime($this->_get('modified'));
			case "id":
				return $this->id;
			case "page":
				return new Page($this->_get('page_id'), 'id');
			case "parent":
				return ($this->has_parent()) ? new Comment($this->_get('parent_id'), 'id') : null;
			case "selector":
				return $this->_get('selector');
			default:
				ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::__get() Attempted to get unknown property '%s' of user", $name);
		}
	}

	public function __set($name, $value) {
		switch($name) {
			case "content":
				$this->_set($name, $value);
				break;
			case "author":
			case "children":
			case "created":
			case "deleted":
			case "edited":
			case "modified":
			case "id":
			case "page":
			case "parent":
			case "selector":
				ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::__set() Attempted to set read-only property '%s' of user", $name);
				break;
			default:
				ERRORS::log(ERRORS::COMMENT_ERROR, "Comment::__set() Attempted to set unknown property '%s' of user", $name);
		}
	}

	public function can_see($user) {
		if ($this->can_edit($user)) return true;
		else return $this->page->can_see($user);
	}

	public function can_edit($user) {
		if ($user->has_permission(User::ACT_EDIT_ALL_COMMENTS)) return true;
		elseif (!$this->deleted && $this->author->id == $user->id && $user->has_permission(User::ACT_EDIT_OWN_COMMENTS)) return true;

		return false;
	}

	public function has_parent() {
		return !is_null($this->_get('parent_id'));
	}

	public function get_parents($recursive=false){
		$parents = [];
		$parent = $this->parent;
		$parents[] = $parent;
		if ($recursive) {
			while($parent->has_parent) {
				$parent = $parent->parent;
				$parents[] = $parent;
			}
		}

		return $parents;
	}

	public function has_children() {
		return !empty($this->get_children());
	}

	public function get_children($recursive=false){
		$children = [];
		$rows = MYSQL::run_query("SELECT id FROM comments WHERE parent_id = ?", 'i', [$this->id]);
		foreach ($rows as $row) {
			$new_child = new Comment($row['id'], 'id');
			$children[] = $new_child;
			if ($recursive) {
				foreach ($new_child->get_children($recursive) as $grandchild) {
					$children[] = $grandchild;
				}
			}
		}
		return $children;
	}

	public function delete() {
		if ($this->has_children()) {
			$this->content = "[deleted]";
			$this->_set("author_id", null);
		} else {
			MYSQL::run_query("DELETE FROM comments WHERE id = ?", $this->id);
			$this->id = null;
		}
	}
}




?>