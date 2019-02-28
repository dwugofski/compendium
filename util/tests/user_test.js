
const user = require('../../users/user');
const usertables = require('../tables/user_tables');

const User = user.User;
const UserExistsError = user.UserExistsError;
const UserInvalidValueError = user.UserInvalidValueError;

async function test() {
	const users_data = [
		{name: 'root', password: 'rootfoobar', email: 'root@compendium.com'},
		{name: 'admin', password: 'adminfoobar', email: 'admin@compendium.com'},
		{name: 'user', password: 'userfoobar', email: 'user@compendium.com'},
		{name: 'user2', password: 'user2foobar', email: 'user2@compendium.com'},
		{name: 'guest', password: 'guestfoobar', email: 'guest@compendium.com'},
	];
	var users = [];

	for (let i = 0; i < users_data.length; i++) {
		let usr_data = users_data[i];
		let new_user = await User.create(usr_data.name, usr_data.password, usr_data.email);
		users.push(new_user);
	}

	console.log('Successfully requested the creation of four members');

	const find_cols = ['id', 'username', 'selector', 'email'];

	for (let i = 0; i < users.length; i++) {
		let usr = users[i];
		const found_by_obj = await User.is(usr);
		if (!found_by_obj) throw new Error(`Could not find ${usr.props.props.username} by its object!`);

		for (let j = 0; j < find_cols.length; j++) {
			const found_by = await User.is(usr.props[find_cols[j]], find_cols[j]);
			if (!found_by) throw new Error(`Could not find ${usr.props.props.username} user by its ${find_cols[j]}!`);
		}
	}

	console.log('Successfully verified all users\' existences!');

	try {
		const failed_usr = await User.create('user', 'newfoobar', 'user3@compendium.com');
		throw new Error('Did not object to creating a second \'user\' user with same username!');
	} catch(err) {
		if (!(err instanceof UserExistsError)) throw err;
	}

	try {
		const failed_usr = await User.create('user3', 'newfoobar', 'user@compendium.com');
		throw new Error('Did not object to creating a second \'user\' user with same email!');
	} catch(err) {
		if (!(err instanceof UserExistsError)) throw err;
	}

	try {
		const failed_usr = await User.create('user', 'newfoobar', 'user@compendium.com');
		throw new Error('Did not object to creating a second \'user\' user with same username and email!');
	} catch(err) {
		if (!(err instanceof UserExistsError)) throw err;
	}

	console.log("Successfully verified duplicate account creation rejection!");

	await users[3].username('user3');

	if (users[3].props.username != 'user3') throw new Error('Did not recognize change to second user\'s username!');
	if (await users[3].username() != 'user3') throw new Error('Did not asynchronously recognize change to second user\'s username!');
	
	await users[3].username('user2');
	if (users[3]._username != 'user2') throw new Error('Did not privately recognize change to second user\'s username!');

	try {
		await users[3].username('gr @@t');
		throw new Error('Did not reject invalid username!');
	} catch (err) {
		if (!(err instanceof UserInvalidValueError)) throw (err);
	}
	if (users[3]._username != 'user2') throw new Error('Did not reject invalid change to second user\'s username!');

	try {
		await users[3].username('user');
		throw new Error('Did not reject duplicate username!');
	} catch (err) {
		if (!(err instanceof UserExistsError)) throw (err);
	}
	if (users[3]._username != 'user2') throw new Error('Did not reject change to second user\'s username for duplication!');

	console.log("Successfully verified user username changes!");

	await users[3].email('user3@compendium.com');

	if (await users[3].props.email != 'user3@compendium.com') throw new Error('Did not recognize change to second user\'s email!');
	if (await users[3].email() != 'user3@compendium.com') throw new Error('Did not asynchronously recognize change to second user\'s email!');
	
	await users[3].email('user2@compendium.com');
	if (users[3]._email != 'user2@compendium.com') throw new Error('Did not privately recognize change to second user\'s email!');

	try {
		await users[3].email('gr @@t');
		throw new Error('Did not reject invalid email!');
	} catch (err) {
		if (!(err instanceof UserInvalidValueError)) throw (err);
	}
	if (users[3]._email != 'user2@compendium.com') throw new Error('Did not reject invalid change to second user\'s email!');

	try {
		await users[3].email('user@compendium.com');
		throw new Error('Did not reject duplicate email!');
	} catch (err) {
		if (!(err instanceof UserExistsError)) throw (err);
	}
	if (users[3]._email != 'user2@compendium.com') throw new Error('Did not reject change to second user\'s email for duplication!');

	console.log("Successfully verified user email changes!");

	for (let i = 0; i < users.length; i++) {
		let usr = users[i];
		let good_pwd = await usr.verify(usr.props.username + 'foobar');
		if (!good_pwd) throw new Error(`Rejected correct password for ${usr.props.username}!`);

		let bad_pwd = await usr.verify(usr.props.username + 'barfoo');
		if (bad_pwd) throw new Error(`Accepted incorrect password for ${usr.props.username}!`);
	}

	console.log('Successfully verified user password verification functionality!');

	for (let i = 0; i < users.length; i++) {
		await User.delete(users[i]);
		let found_deleted_usr = await User.is(users[i]);
		if (found_deleted_usr) throw new Error(`Found user ${users[i].props.username} after attempting deletion!`);

		let usr_data = users_data[i];
		await User.create(usr_data.name, usr_data.password, usr_data.email);
		found_deleted_usr = await User.is(users[i].props.username, 'username');
		if (!found_deleted_usr) throw new Error(`Could not found user ${users[i].props.username} after re-creation!`);

		await User.delete(users[i].props.username, 'username');
		found_deleted_usr = await User.is(users[i].props.username, 'username');
		if (found_deleted_usr) throw new Error(`Found user ${users[i].props.username} after second attempted deletion!`);
	}

	console.log('Successfully verified user deletions!');

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
	console.log(" ----------------------------------------------------");
	console.log(" -------------------- USER TESTS --------------------");
	console.log(" ----------------------------------------------------");
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