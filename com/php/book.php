<?php

include_once(dirname(__DIR__)."\errors.php");
include_once(dirname(__DIR__)."\mysql.php");

class BOOK{
	public $id;
	public $title;
	public $pages;

	static public function get_user_books($userid) {}

	static public function get_book($bookid) {}

	public function __construct($title) {}

	public function get_pages() {}

	public function can_see($userid) {}

	public function add_collaborator($userid) {}

	public function remove_collaborator($userid) {}

	public function whitelist_user($userid) {}

	public function unwhitelist_user($userid) {}

	public function blacklist_user($userid) {}

	public function unblacklist_user($userid) {}

	public function unlist_user($userid) {}
}

?>