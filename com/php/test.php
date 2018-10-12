<?php

include_once "errors.php";
include_once "user.php";

echo("\nCreating root\n");
$root = User::create_new_user("root", "iamroot", "root@groot.com");
echo(sprintf("Root's username is: %s\n", $root->username));
echo(sprintf("Root's email is: %s\n", $root->email));
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
echo("Making user a user\n");
$user->grant_permissions(User::PERM_USER);
echo("User now has following permissions:\n");
if ($user->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($user->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($user->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($user->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");

echo("\nCreating guest\n");
$guest = User::create_new_user("Hello", "World!", "hello@world.com");
echo(sprintf("Guest's username is: %s\n", $guest->username));
echo(sprintf("Guest's email is: %s\n", $guest->email));
echo("Guest now has following permissions:\n");
if ($guest->has_permission(User::ACT_EDIT_ALL_ADMINS)) echo("    Edit all admins\n");
if ($guest->has_permission(User::ACT_EDIT_ALL_USERS)) echo("    Edit all users\n");
if ($guest->has_permission(User::ACT_EDIT_OWN_USER)) echo("    Edit own user\n");
if ($guest->has_permission(User::ACT_VIEW_UNLOCKED_PAGES)) echo("    View unlocked pages\n");


?>