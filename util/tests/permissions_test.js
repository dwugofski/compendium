
const user = require('../../users/user');
const usertables = require('../tables/user_tables');

const User = user.User;
const UserExistsError = user.UserExistsError;
const UserInvalidValueError = user.UserInvalidValueError;

async function test() {
	const actions = [
		user.ACT_EDIT_ALL_ADMINS,
		user.ACT_EDIT_ALL_USERS,
		user.ACT_EDIT_ALL_THEMES,
		user.ACT_DELETE_ALL_PAGES,
		user.ACT_EDIT_ALL_PAGES,
		user.ACT_LOCK_ALL_PAGES,
		user.ACT_OPEN_ALL_PAGES,
		user.ACT_EDIT_ALL_COMMENTS,
		user.ACT_ADD_ALL_COMMENTS,
		user.ACT_VIEW_ALL_PAGES,
		user.ACT_EDIT_OWN_USER,
		user.ACT_EDIT_OWN_THEMES,
		user.ACT_CREATE_OWN_PAGES,
		user.ACT_DELETE_OWN_PAGES,
		user.ACT_EDIT_OWN_PAGES,
		user.ACT_LOCK_OWN_PAGES,
		user.ACT_OPEN_OWN_PAGES,
		user.ACT_EDIT_OWN_COMMENTS,
		user.ACT_ADD_OWN_COMMENTS,
		user.ACT_VIEW_OWN_PAGES,
		user.ACT_EDIT_UNLOCKED_PAGES,
		user.ACT_ADD_UNLOCKED_COMMENTS,
		user.ACT_ADD_OPEN_COMMENTS,
		user.ACT_VIEW_OPEN_PAGES
	];
	const root_actions = [
		user.ACT_EDIT_ALL_ADMINS
	];
	const admin_actions = [
		user.ACT_EDIT_ALL_USERS,
		user.ACT_EDIT_ALL_THEMES,
		user.ACT_DELETE_ALL_PAGES,
		user.ACT_EDIT_ALL_PAGES,
		user.ACT_LOCK_ALL_PAGES,
		user.ACT_OPEN_ALL_PAGES,
		user.ACT_EDIT_ALL_COMMENTS,
		user.ACT_ADD_ALL_COMMENTS,
		user.ACT_VIEW_ALL_PAGES
	];
	const user_actions = [
		user.ACT_EDIT_OWN_USER,
		user.ACT_EDIT_OWN_THEMES,
		user.ACT_CREATE_OWN_PAGES,
		user.ACT_DELETE_OWN_PAGES,
		user.ACT_EDIT_OWN_PAGES,
		user.ACT_LOCK_OWN_PAGES,
		user.ACT_OPEN_OWN_PAGES,
		user.ACT_EDIT_OWN_COMMENTS,
		user.ACT_ADD_OWN_COMMENTS,
		user.ACT_VIEW_OWN_PAGES,
		user.ACT_EDIT_UNLOCKED_PAGES,
		user.ACT_ADD_UNLOCKED_COMMENTS,
		user.ACT_ADD_OPEN_COMMENTS
	];
	const guest_actions = [
		user.VIEW_OPEN_PAGES
	];

	console.log("Creating users...");
	const root_usr = await User.create('root', 'rootfoobar', 'root@compendium.com');
	const admin_usr = await User.create('admin', 'adminfoobar', 'admin@compendium.com');
	const user_usr = await User.create('user', 'userfoobar', 'user@compendium.com');
	const guest_usr = await User.create('guest', 'guestfoobar', 'guest@compendium.com');
	if (!(await User.is(root_usr))) throw new Error("Could not verify root's existence");
	if (!(await User.is(admin_usr))) throw new Error("Could not verify admin's existence");
	if (!(await User.is(user_usr))) throw new Error("Could not verify admin's existence");
	if (!(await User.is(user_usr))) throw new Error("Could not verify admin's existence");

	console.log('Successfully created root and admin users');

	console.log("Requesting permission grants...");
	await root_usr.permission_level(user.PERM_ROOT);
	await admin_usr.permission_level(user.PERM_ADMIN);
	await user_usr.permission_level(user.PERM_USER);
	await guest_usr.permission_level(user.PERM_GUEST);

	if ((await root_usr.permission_level()) != user.PERM_ROOT) throw new Error("Root user does not have root permissions level");
	if ((await admin_usr.permission_level()) != user.PERM_ADMIN) throw new Error("Admin user does not have admin permissions level");
	if ((await user_usr.permission_level()) != user.PERM_USER) throw new Error("User user does not have user permissions level");
	if ((await guest_usr.permission_level()) != user.PERM_GUEST) throw new Error("Guest user does not have guest permissions level");

	console.log("Successfully granted access levels!");

	console.log("Checking actions...");
	const root_acts = await root_usr.actions();
	const admin_acts = await admin_usr.actions();
	var user_acts = await user_usr.actions();
	const guest_acts = await guest_usr.actions();

	for (let i = 0; i < actions.length; i++) {
		let action = actions[i];
		if (root_actions.includes(action)) {
			if (!root_acts.includes(action)) throw new Error(`Root user does not have the ${action} action`);
			if (admin_acts.includes(action)) throw new Error(`Admin user has the ${action} action`);
			if (user_acts.includes(action)) throw new Error(`User user has the ${action} action`);
			if (guest_acts.includes(action)) throw new Error(`Guest user has the ${action} action`);
		}
		if (admin_actions.includes(action)) {
			if (!root_acts.includes(action)) throw new Error(`Root user does not have the ${action} action`);
			if (!admin_acts.includes(action)) throw new Error(`Admin user does not have the ${action} action`);
			if (user_acts.includes(action)) throw new Error(`User user has the ${action} action`);
			if (guest_acts.includes(action)) throw new Error(`Guest user has the ${action} action`);
		}
		if (user_actions.includes(action)) {
			if (!root_acts.includes(action)) throw new Error(`Root user does not have the ${action} action`);
			if (!admin_acts.includes(action)) throw new Error(`Admin user does not have the ${action} action`);
			if (!user_acts.includes(action)) throw new Error(`User does not have the ${action} action`);
			if (guest_acts.includes(action)) throw new Error(`Guest user has the ${action} action`);
		}
		if (guest_actions.includes(action)) {
			if (!root_acts.includes(action)) throw new Error(`Root user does not have the ${action} action`);
			if (!admin_acts.includes(action)) throw new Error(`Admin user does not have the ${action} action`);
			if (!user_acts.includes(action)) throw new Error(`User does not have the ${action} action`);
			if (!guest_acts.includes(action)) throw new Error(`Guest user does not have the ${action} action`);
		}
	}

	console.log("Successfully verified each user's permissions");

	console.log("Upgrading User's permissions...");
	await user_usr.permission_level(user.PERM_ADMIN);
	user_acts = await user_usr.actions();
	for (let i = 0; i < actions.length; i++) {
		let action = actions[i];
		if (root_actions.includes(action)) {
			if (user_acts.includes(action)) throw new Error(`Upgraded user has the ${action} action`);
		}
		if (admin_actions.includes(action)) {
			if (!user_acts.includes(action)) throw new Error(`Upgraded user does not have the ${action} action`);
		}
		if (user_actions.includes(action)) {
			if (!user_acts.includes(action)) throw new Error(`Upgraded user does not have the ${action} action`);
		}
		if (guest_actions.includes(action)) {
			if (!user_acts.includes(action)) throw new Error(`Upgraded user does not have the ${action} action`);
		}
	}

	console.log("Successfully verified user's permission upgrade");

	console.log("Downgrading User's permissions...");
	await user_usr.permission_level(user.PERM_GUEST);
	user_acts = await user_usr.actions();
	for (let i = 0; i < actions.length; i++) {
		let action = actions[i];
		if (root_actions.includes(action)) {
			if (user_acts.includes(action)) throw new Error(`Downgraded user has the ${action} action`);
		}
		if (admin_actions.includes(action)) {
			if (user_acts.includes(action)) throw new Error(`Downgraded user has the ${action} action`);
		}
		if (admin_actions.includes(action)) {
			if (user_acts.includes(action)) throw new Error(`Downgraded user has the ${action} action`);
		}
		if (guest_actions.includes(action)) {
			if (!user_acts.includes(action)) throw new Error(`Downgraded user does not have the ${action} action`);
		}
	}

	console.log("Successfully verified user's permission downgrade");

	return true;
}
exports.test = test;

function cleanup() {
	usertables.tables(true, false).then(() => {
		process.exit();
	}).catch((err) => {
		console.log(err);
		process.exit();
	});
}

if (require.main == module) {
	console.log(" ----------------------------------------------------------------");
	console.log(" -------------------- USER PERMISSIONS TESTS --------------------");
	console.log(" ----------------------------------------------------------------");
	usertables.tables(true, true).then(() => {
		return test();
	}).then((status) => {
		console.log(" -------------------- TEST SUCCESSFUL --------------------");
		cleanup();
	}).catch((err) => {
		console.log(" -------------------- TEST FAILED --------------------");
		console.log(err);
		console.log(" -------------------- TEST FAILED --------------------");
		cleanup();
	});

}