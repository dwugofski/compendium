
const moment = require('moment');
const user = require('../../users/user');
const usertables = require('../tables/user_tables');

const User = user.User;
const LoginToken = user.LoginToken;

async function test() {
	console.log("Creating root user...");
	const root_usr = await User.create('root', 'rootfoobar', 'root@compendium.com');
	const admin_usr = await User.create('admin', 'adminfoobar', 'admin@compendium.com');
	if (!(await User.is(root_usr))) throw new Error("Could not verify root's existence");
	if (!(await User.is(admin_usr))) throw new Error("Could not verify admin's existence");

	console.log('Successfully created root and admin users');

	console.log("Requesting root token...");
	var root_token = await root_usr.generate_token();
	if (!(await LoginToken.is(root_token.selector, 'selector'))) throw new Error("Did not find token after creation!");

	console.log("Successfully created root token");

	console.log("Verifying based on root token...");
	var good = await root_usr.verify(root_token, true);
	if (!good) throw new Error("Did not recognize root user's token!");
	var bad = await admin_usr.verify(root_token, true);
	if (bad) throw new Error("admin user accepted root user's token!");
	var wtf = false;
	try {
		wtf = await root_usr.verify(root_token);
	} catch(err) {}
	if (wtf) throw new Error("root user accepted token in lieu of a password!");
	var wtff = false;
	try {
		wtff = await admin_usr.verify(root_token);
	} catch(err) {}
	if (wtff) throw new Error("admin user accepted root token in lieu of a password!");

	console.log("Successfully verified token verification");

	console.log("Attempting to delete root token...");
	await LoginToken.delete(root_token.selector, 'selector');
	if (await LoginToken.is(root_token.selector, 'selector')) throw new Error("Root token found after deletion");
	if (await root_usr.verify(root_token, true)) throw new Error("Root token accepted after deletion");

	console.log("Successfully deleted root user's token");

	console.log("Verifying token expiration...");
	root_token = await root_usr.generate_token(moment.duration(2, 'seconds'));
	good = await root_usr.verify(root_token, true);
	if (!good) throw new Error("Did not recognize root user's token!");
	console.log("Waiting for expiration...");
	Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 4000);
	bad = await root_usr.verify(root_token, true);
	if (bad) throw new Error("root user accepted expired token!");
	if (await LoginToken.is(root_token.selector, 'selector')) throw new Error("Root token found after expiration + check");

	console.log("Successfully expired root user's token");

	return true
}
exports.test = test;

function cleanup() {
	if (!true) {
		process.exit();
		return;
	}
	usertables.tables(true, false).then(() => {
		process.exit();
	}).catch((err) => {
		console.log(err);
		process.exit();
	});
}

if (require.main == module) {
	console.log(" ----------------------------------------------------------");
	console.log(" -------------------- USER TOKEN TESTS --------------------");
	console.log(" ----------------------------------------------------------");
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