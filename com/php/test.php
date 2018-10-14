<?php

$ob_file = fopen('test.log','w');

function ob_file_callback($buffer)
{
  global $ob_file;
  fwrite($ob_file,$buffer);
}

ob_start('ob_file_callback');

include_once __DIR__."/table_scripts/tables.php";
include_once __DIR__."/errors.php";
include_once __DIR__."/user.php";
include_once __DIR__."/page.php";

tables(TRUE, FALSE);

echo("\n");
echo("==============================\n");
echo("===== USER TESTS =============\n");
echo("==============================\n");
echo("\n");

echo("\nCreating root\n");
$root = User::create_new_user("root", "iamroot", "root@groot.com");
echo(sprintf("Root's username is: %s\n", $root->username));
echo(sprintf("Root's email is: %s\n", $root->email));
echo(sprintf("Root's password is %s'bar'\n", User::validate_user($root->username, "bar") ? "" : "not "));
echo("Making root the root user\n");
$root->grant_permissions(User::PERM_ROOT);
echo("Root now has following permissions:\n");
if ($root->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($root->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($root->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($root->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");

echo("\nCreating admin\n");
$admin = User::create_new_user("Adam", "plusleast", "plus@least.com");
echo(sprintf("Admin's username is: %s\n", $admin->username));
echo(sprintf("Admin's email is: %s\n", $admin->email));
echo(sprintf("Admin's password is %s'bar'\n", User::validate_user($admin->username, "bar") ? "" : "not "));
echo("Making admin an admin user\n");
$admin->grant_permissions(User::PERM_ADMIN);
echo("Admin now has following permissions:\n");
if ($admin->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($admin->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($admin->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($admin->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");

echo("\nCreating user\n");
$user = User::create_new_user("Foo", "bar", "foo@bar.com");
echo(sprintf("User's username is: %s\n", $user->username));
echo(sprintf("User's email is: %s\n", $user->email));
echo(sprintf("User's password is %s'bar'\n", User::validate_user($user->username, "bar") ? "" : "not "));
echo("Making user a user\n");
$user->grant_permissions(User::PERM_USER);
echo("User now has following permissions:\n");
if ($user->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($user->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($user->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");

echo("\nCreating second, identical user\n");
try {
	$user2 = User::create_new_user("Foo", "bar", "foo@bar.com");
}
catch(Exception $e){
	echo("Failed to create second, identical user\n");
}

echo("\nCreating second, unique user\n");
$user2 = User::create_new_user("Bar", "foo", "bar@foo.com");
echo(sprintf("User2's username is: %s\n", $user2->username));
echo(sprintf("User2's email is: %s\n", $user2->email));
echo(sprintf("User2's password is %s'bar'\n", User::validate_user($user2->username, "bar") ? "" : "not "));
echo("Making user2 a user\n");
$user2->grant_permissions(User::PERM_USER);
echo("User2 now has following permissions:\n");
if ($user2->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($user2->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($user2->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($user2->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");

echo("\nCreating guest\n");
$guest = User::create_new_user("Hello", "World!", "hello@world.com");
echo(sprintf("Guest's username is: %s\n", $guest->username));
echo(sprintf("Guest's email is: %s\n", $guest->email));
echo(sprintf("Guest's password is %s'bar'\n", User::validate_user($guest->username, "bar") ? "" : "not "));
echo("Guest now has following permissions:\n");
if ($guest->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($guest->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($guest->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($guest->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");

echo("\n");
echo("==============================\n");
echo("===== PAGE TESTS =============\n");
echo("==============================\n");
echo("\n");

echo("\nCreating user's book\n");
$user_book = Page::create_new_page($user, "Foobook", "This should be a book");
echo(sprintf("User's book's title is '%s'\n", $user_book->title));
echo(sprintf("User's book's text is '%s'\n", $user_book->text));
echo(sprintf("User's book's level is '%d'\n", $user_book->level));
echo(sprintf("User's book is %sa book\n", $user_book->isBook ? "" : "not "));
echo(sprintf("User's book is %sa chapter\n", $user_book->isChapter ? "" : "not "));
echo(sprintf("User's book is %slocked\n", $user_book->locked ? "" : "not "));
echo(sprintf("User's book is %sopened\n", $user_book->opened ? "" : "not "));
echo("\nLocking user's book\n");
$user_book->lock();
echo(sprintf("User's book is %slocked\n", $user_book->locked ? "" : "not "));
echo("\nUnlocking user's book\n");
$user_book->unlock();
echo(sprintf("User's book is %slocked\n", $user_book->locked ? "" : "not "));
echo("\nOpening user's book\n");
$user_book->open();
echo(sprintf("User's book is %sopened\n", $user_book->opened ? "" : "not "));
echo("\nClosing user's book\n");
$user_book->close();
echo(sprintf("User's book is %sopened\n", $user_book->opened ? "" : "not "));

echo("\n\nChecking access\n");
function user_access_tests() {
	global $user_book, $root, $admin, $user, $user2, $guest;

	echo("\nChecking all users' access\n");
	echo(sprintf("User's book's is %slocked\n", $user_book->locked ? "" : "not "));
	echo(sprintf("User's book's is %sopened\n", $user_book->opened ? "" : "not "));
	echo(sprintf("Root can%s see user's book\n", $user_book->can_see($root) ? "" : "not"));
	echo(sprintf("Root can%s edit user's book\n", $user_book->can_edit($root) ? "" : "not"));
	echo(sprintf("Root can%s lock user's book\n", $user_book->can_lock($root) ? "" : "not"));
	echo(sprintf("Root can%s open user's book\n", $user_book->can_open($root) ? "" : "not"));
	echo(sprintf("Admin can%s see user's book\n", $user_book->can_see($admin) ? "" : "not"));
	echo(sprintf("Admin can%s edit user's book\n", $user_book->can_edit($admin) ? "" : "not"));
	echo(sprintf("Admin can%s lock user's book\n", $user_book->can_lock($admin) ? "" : "not"));
	echo(sprintf("Admin can%s open user's book\n", $user_book->can_open($admin) ? "" : "not"));
	echo(sprintf("User can%s see user's book\n", $user_book->can_see($user) ? "" : "not"));
	echo(sprintf("User can%s edit user's book\n", $user_book->can_edit($user) ? "" : "not"));
	echo(sprintf("User can%s lock user's book\n", $user_book->can_lock($user) ? "" : "not"));
	echo(sprintf("User can%s open user's book\n", $user_book->can_open($user) ? "" : "not"));
	echo(sprintf("User2 can%s see user's book\n", $user_book->can_see($user2) ? "" : "not"));
	echo(sprintf("User2 can%s edit user's book\n", $user_book->can_edit($user2) ? "" : "not"));
	echo(sprintf("User2 can%s lock user's book\n", $user_book->can_lock($user2) ? "" : "not"));
	echo(sprintf("User2 can%s open user's book\n", $user_book->can_open($user2) ? "" : "not"));
	echo(sprintf("Guest can%s see user's book\n", $user_book->can_see($guest) ? "" : "not"));
	echo(sprintf("Guest can%s edit user's book\n", $user_book->can_edit($guest) ? "" : "not"));
	echo(sprintf("Guest can%s lock user's book\n", $user_book->can_lock($guest) ? "" : "not"));
	echo(sprintf("Guest can%s open user's book\n", $user_book->can_open($guest) ? "" : "not"));

	echo("\nChecking all users' access to the locked page\n");
	$user_book->lock();
	echo(sprintf("User's book's is %slocked\n", $user_book->locked ? "" : "not "));
	echo(sprintf("User's book's is %sopened\n", $user_book->opened ? "" : "not "));
	echo(sprintf("Root can%s see user's book\n", $user_book->can_see($root) ? "" : "not"));
	echo(sprintf("Root can%s edit user's book\n", $user_book->can_edit($root) ? "" : "not"));
	echo(sprintf("Root can%s lock user's book\n", $user_book->can_lock($root) ? "" : "not"));
	echo(sprintf("Root can%s open user's book\n", $user_book->can_open($root) ? "" : "not"));
	echo(sprintf("Admin can%s see user's book\n", $user_book->can_see($admin) ? "" : "not"));
	echo(sprintf("Admin can%s edit user's book\n", $user_book->can_edit($admin) ? "" : "not"));
	echo(sprintf("Admin can%s lock user's book\n", $user_book->can_lock($admin) ? "" : "not"));
	echo(sprintf("Admin can%s open user's book\n", $user_book->can_open($admin) ? "" : "not"));
	echo(sprintf("User can%s see user's book\n", $user_book->can_see($user) ? "" : "not"));
	echo(sprintf("User can%s edit user's book\n", $user_book->can_edit($user) ? "" : "not"));
	echo(sprintf("User can%s lock user's book\n", $user_book->can_lock($user) ? "" : "not"));
	echo(sprintf("User can%s open user's book\n", $user_book->can_open($user) ? "" : "not"));
	echo(sprintf("User2 can%s see user's book\n", $user_book->can_see($user2) ? "" : "not"));
	echo(sprintf("User2 can%s edit user's book\n", $user_book->can_edit($user2) ? "" : "not"));
	echo(sprintf("User2 can%s lock user's book\n", $user_book->can_lock($user2) ? "" : "not"));
	echo(sprintf("User2 can%s open user's book\n", $user_book->can_open($user2) ? "" : "not"));
	echo(sprintf("Guest can%s see user's book\n", $user_book->can_see($guest) ? "" : "not"));
	echo(sprintf("Guest can%s edit user's book\n", $user_book->can_edit($guest) ? "" : "not"));
	echo(sprintf("Guest can%s lock user's book\n", $user_book->can_lock($guest) ? "" : "not"));
	echo(sprintf("Guest can%s open user's book\n", $user_book->can_open($guest) ? "" : "not"));
	$user_book->unlock();

	echo("\nChecking all users' access to the opened page\n");
	$user_book->open();
	echo(sprintf("User's book's is %slocked\n", $user_book->locked ? "" : "not "));
	echo(sprintf("User's book's is %sopened\n", $user_book->opened ? "" : "not "));
	echo(sprintf("Root can%s see user's book\n", $user_book->can_see($root) ? "" : "not"));
	echo(sprintf("Root can%s edit user's book\n", $user_book->can_edit($root) ? "" : "not"));
	echo(sprintf("Root can%s lock user's book\n", $user_book->can_lock($root) ? "" : "not"));
	echo(sprintf("Root can%s open user's book\n", $user_book->can_open($root) ? "" : "not"));
	echo(sprintf("Admin can%s see user's book\n", $user_book->can_see($admin) ? "" : "not"));
	echo(sprintf("Admin can%s edit user's book\n", $user_book->can_edit($admin) ? "" : "not"));
	echo(sprintf("Admin can%s lock user's book\n", $user_book->can_lock($admin) ? "" : "not"));
	echo(sprintf("Admin can%s open user's book\n", $user_book->can_open($admin) ? "" : "not"));
	echo(sprintf("User can%s see user's book\n", $user_book->can_see($user) ? "" : "not"));
	echo(sprintf("User can%s edit user's book\n", $user_book->can_edit($user) ? "" : "not"));
	echo(sprintf("User can%s lock user's book\n", $user_book->can_lock($user) ? "" : "not"));
	echo(sprintf("User can%s open user's book\n", $user_book->can_open($user) ? "" : "not"));
	echo(sprintf("User2 can%s see user's book\n", $user_book->can_see($user2) ? "" : "not"));
	echo(sprintf("User2 can%s edit user's book\n", $user_book->can_edit($user2) ? "" : "not"));
	echo(sprintf("User2 can%s lock user's book\n", $user_book->can_lock($user2) ? "" : "not"));
	echo(sprintf("User2 can%s open user's book\n", $user_book->can_open($user2) ? "" : "not"));
	echo(sprintf("Guest can%s see user's book\n", $user_book->can_see($guest) ? "" : "not"));
	echo(sprintf("Guest can%s edit user's book\n", $user_book->can_edit($guest) ? "" : "not"));
	echo(sprintf("Guest can%s lock user's book\n", $user_book->can_lock($guest) ? "" : "not"));
	echo(sprintf("Guest can%s open user's book\n", $user_book->can_open($guest) ? "" : "not"));
	$user_book->close();

	echo("\nChecking all users' access to the locked and opened page\n");
	$user_book->lock();
	$user_book->open();
	echo(sprintf("User's book's is %slocked\n", $user_book->locked ? "" : "not "));
	echo(sprintf("User's book's is %sopened\n", $user_book->opened ? "" : "not "));
	echo(sprintf("Root can%s see user's book\n", $user_book->can_see($root) ? "" : "not"));
	echo(sprintf("Root can%s edit user's book\n", $user_book->can_edit($root) ? "" : "not"));
	echo(sprintf("Root can%s lock user's book\n", $user_book->can_lock($root) ? "" : "not"));
	echo(sprintf("Root can%s open user's book\n", $user_book->can_open($root) ? "" : "not"));
	echo(sprintf("Admin can%s see user's book\n", $user_book->can_see($admin) ? "" : "not"));
	echo(sprintf("Admin can%s edit user's book\n", $user_book->can_edit($admin) ? "" : "not"));
	echo(sprintf("Admin can%s lock user's book\n", $user_book->can_lock($admin) ? "" : "not"));
	echo(sprintf("Admin can%s open user's book\n", $user_book->can_open($admin) ? "" : "not"));
	echo(sprintf("User can%s see user's book\n", $user_book->can_see($user) ? "" : "not"));
	echo(sprintf("User can%s edit user's book\n", $user_book->can_edit($user) ? "" : "not"));
	echo(sprintf("User can%s lock user's book\n", $user_book->can_lock($user) ? "" : "not"));
	echo(sprintf("User can%s open user's book\n", $user_book->can_open($user) ? "" : "not"));
	echo(sprintf("User2 can%s see user's book\n", $user_book->can_see($user2) ? "" : "not"));
	echo(sprintf("User2 can%s edit user's book\n", $user_book->can_edit($user2) ? "" : "not"));
	echo(sprintf("User2 can%s lock user's book\n", $user_book->can_lock($user2) ? "" : "not"));
	echo(sprintf("User2 can%s open user's book\n", $user_book->can_open($user2) ? "" : "not"));
	echo(sprintf("Guest can%s see user's book\n", $user_book->can_see($guest) ? "" : "not"));
	echo(sprintf("Guest can%s edit user's book\n", $user_book->can_edit($guest) ? "" : "not"));
	echo(sprintf("Guest can%s lock user's book\n", $user_book->can_lock($guest) ? "" : "not"));
	echo(sprintf("Guest can%s open user's book\n", $user_book->can_open($guest) ? "" : "not"));
	$user_book->close();
	$user_book->unlock();
}
user_access_tests();

echo("\nWhitelisting User2\n");
$user_book->whitelist_user($user2);
user_access_tests();

echo("\nBlacklisting User2\n");
$user_book->blacklist_user($user2);
user_access_tests();

echo("\nWhitelisting User2\n");
$user_book->whitelist_user($user2);
user_access_tests();

echo("\nUnlisting User2\n");
$user_book->unlist_user($user2);
user_access_tests();

echo("\nAdding User2 as a collaborator\n");
$user_book->add_collaborator($user2);
user_access_tests();

echo("\nWhitelisting User2\n");
$user_book->whitelist_user($user2);
user_access_tests();

echo("\nBlacklisting User2\n");
$user_book->blacklist_user($user2);
user_access_tests();

echo("\nWhitelisting User2\n");
$user_book->whitelist_user($user2);
user_access_tests();

echo("\nAdding User2 back as a collaborator\n");
$user_book->add_collaborator($user2);
user_access_tests();

echo("\nBlacklisting User2\n");
$user_book->blacklist_user($user2);
user_access_tests();

echo("\nAdding User2 back as a collaborator\n");
$user_book->add_collaborator($user2);
user_access_tests();

echo("\nUnlisting User2\n");
$user_book->unlist_user($user2);
user_access_tests();

echo("\nRemoving User2 as a collaborator\n");
$user_book->remove_collaborator($user2);
user_access_tests();

echo("\nWhitelisting User2\n");
$user_book->whitelist_user($user2);
user_access_tests();

echo("\nBlacklisting User2\n");
$user_book->blacklist_user($user2);
user_access_tests();

echo("\nWhitelisting User2\n");
$user_book->whitelist_user($user2);
user_access_tests();

echo("\nUnlisting User2\n");
$user_book->unlist_user($user2);
user_access_tests();

echo("\n\nChecking subpages\n");
function page_children($pagename, $page){
	if (!$page->has_children()) echo(sprintf("%s has no children\n", $pagename));
	else {
		echo(sprintf("%s's direct children are\n", $pagename));
		foreach ($page->children as $i => $child) {
			echo(sprintf("    %s\n", $child->title));
		}
		echo(sprintf("All %s's children are\n", $pagename));
		foreach ($page->all_children as $i => $child) {
			echo(sprintf("    %s\n", $child->title));
		}
	}
}
function page_parents($pagename, $page){
	if ($page->has_parent()) {
		echo(sprintf("%s's parent is %s\n", $pagename, $page->parent->title));
		echo(sprintf("%s's parents are\n", $pagename));
		foreach ($page->parents as $i => $parent) {
			echo(sprintf("    %s\n", $parent->title));
		}
	}
	else echo(sprintf("%s has no parents\n", $pagename));
	
}

echo("\nCreating user chapter\n");
$user_chapter = Page::create_new_page($user, "Foochap", "This should be a chapter");
echo(sprintf("User's chapter is %sa book\n", $user_chapter->isBook ? "" : "not "));
echo(sprintf("User's chapter is %sa chapter\n", $user_chapter->isChapter ? "" : "not "));
page_children("User's book", $user_book);
page_parents("User's book", $user_book);
page_children("User's chapter", $user_chapter);
page_parents("User's chapter", $user_chapter);

echo("\nAdding user chapter to book\n");
$user_book->add_child($user_chapter);
echo(sprintf("User's chapter is %sa book\n", $user_chapter->isBook ? "" : "not "));
echo(sprintf("User's chapter is %sa chapter\n", $user_chapter->isChapter ? "" : "not "));
page_children("User's book", $user_book);
page_parents("User's book", $user_book);
page_children("User's chapter", $user_chapter);
page_parents("User's chapter", $user_chapter);

?>