
const moment = require('moment');
const user = require('../../users/user');
const usertables = require('../tables/user_tables');
const page = require('../../pages/page');
const pagetables = require('../tables/page_tables');

const User = user.User;
const LoginToken = user.LoginToken;
const Page = page.Page;

async function parenthood(pgs, tree) {
	for (let i=0; i<pgs.length; i++) {
		let pg = pgs[i];
		let clist = tree[i].children;
		let plist = tree[i].parents;
		let children = await pgs[i].children();
		let parents = await pgs[i].parents();
		let chids = [];
		let pids = [];

		children.forEach(child => {
			chids.push(child.id);
		});
		parents.forEach(parent => {
			pids.push(parent.id);
		});

		if (clist.length != cids.length) throw new Error(`${pg.props.title} does not have the right number of children!`);
		if (plist.length != pids.length) throw new Error(`${pg.props.title} does not have the right number of parents!`);
		for (let j=0; j<clist.length; j++) {
			if (!cids.contains(pgs[clist[j]].id)) throw new Error(`${pg.props.title} is missing child ${pgs[clist[j]].props.title}!`);
		}
		for (let j=0; j<plist.length; j++) {
			if (!pids.contains(pgs[plist[j]].id)) throw new Error(`${pg.props.title} is missing parent ${pgs[plist[j]].props.title}!`);
		}
	}
}

async function test() {
	const pages_data = [
		{title: "Book", description: "First Book", content: "I am a book"},
		{title: "Chapter1", description: "First Chapter", content: "I am a chapter"},
		{title: "Chapter2", description: "Second Chapter", content: "I am another chapter"},
		{title: "Page1", description: "First Page", content: "I am a page"},
		{title: "Page2", description: "Second Page", content: "I am another page"},
		{title: "Page3", description: "Third Page", content: "I am yet another page"},
		{title: "PageNull", description: "", content: "I am a nondescript page"}
	];
	const identifiers = ['id', 'selector', 'sel'];

	console.log("Creating authors...");
	const author = User.create("author", "authorfoobar", "author@compendium.com");
	const author = User.create("author2", "author2foobar", "author2@compendium.com");
	if (!(await User.is(author))) throw new Error("Could not create author");
	if (!(await User.is(author))) throw new Error("Could not create author2");
	await author.permissions_level(user.PERM_ROOT);
	await author2.permissions_level(user.PERM_ROOT);
	console.log("Successfully created the authors!");

	console.log("Creating test pages...");
	var pgs = [];
	for (let i=0; i<pages_data.length; i++) {
		let pgdata = pages_data[i];
		let parent = null;
		if (i > 0) parent = pgs[0].props.id;
		if (i > 2) parent = pgs[1];
		pgs.push(await Page.create(pgdata.title, pgdata.description, pgdata.content, author.id, parent));
	}

	console.log("Successfully requested page creation!");

	for (let i=0; i<pgs.length; i++) {
		let pg = pgs[i];
		let pgdata = page_data[i];
		if (!(await Page.is(pg))) throw new Error(`Could not find ${pgdata.title} by its object!`);

		for (let j=0; j < identifiers.length; j++) {
			if (!(await Page.is(pg.props[identifiers[j]], identifiers[j]))) {
				throw new Error(`Could not find ${pgdata.title} by its '${identifiers[j]}'`);
			}
		}
	}

	console.log("Successfully found requested pages!");

	for (let i=0; i<pgs.length; i++) {
		let pg = pgs[i];
		let pgdata = page_data[i];
		if (pg.props.title != pgdata.title) throw new Error(`The title for ${pgdata.title} was set to '${pg.props.title}'`);
		if (pg.props.description != pgdata.description) throw new Error(`The description for ${pgdata.title} was set to '${pg.props.description}'`);
		if (pg.props.content != pgdata.content) throw new Error(`The content for ${pgdata.title} was set to '${pg.props.content}'`);
	}

	console.log("Successfully verified page properties!");

	const book = pgs[0];
	const c1 = pgs[1];
	const c2 = pgs[2];
	const p1 = pgs[3];
	const p2 = pgs[4];
	const p3 = pgs[5];
	const pn = pgs[6];

	if (book.props.parent !== null) throw new Error(`Book's parent set to ${book.props.parent} (by props)`);
	if (await book.parent() !== null) throw new Error(`Book's parent set to ${book.props.parent} (by get)`);
	if (c1.props.parent !== book.id) throw new Error(`C1's parent set to ${c1.props.parent} (by props)`);
	if (await c1.parent() !== book.id) throw new Error(`C1's parent set to ${c1.props.parent} (by get)`);
	if (c2.props.parent !== book.id) throw new Error(`C2's parent set to ${c2.props.parent} (by props)`);
	if (await c2.parent() !== book.id) throw new Error(`C2's parent set to ${c2.props.parent} (by get)`);
	if (p1.props.parent !== c1.id) throw new Error(`P1's parent set to ${p1.props.parent} (by props)`);
	if (await p1.parent() !== c1.id) throw new Error(`P1's parent set to ${p1.props.parent} (by get)`);
	if (p2.props.parent !== c1.id) throw new Error(`P2's parent set to ${p2.props.parent} (by props)`);
	if (await p2.parent() !== c1.id) throw new Error(`P2's parent set to ${p2.props.parent} (by get)`);
	if (p3.props.parent !== c1.id) throw new Error(`P3's parent set to ${p3.props.parent} (by props)`);
	if (await p3.parent() !== c1.id) throw new Error(`P3's parent set to ${p3.props.parent} (by get)`);
	if (pn.props.parent !== c1.id) throw new Error(`PN's parent set to ${pn.props.parent} (by props)`);
	if (await pn.parent() !== c1.id) throw new Error(`PN's parent set to ${pn.props.parent} (by get)`);

	console.log("Successfully verified page parents!");

	const initial_tree = [
		{parents: [], children: [1, 2]},
		{parents: [0], children: [3, 4, 5, 6]},
		{parents: [0], children: []},
		{parents: [0, 1], children: []},
		{parents: [0, 1], children: []},
		{parents: [0, 1], children: []},
		{parents: [0, 1], children: []}
	];

	await parenthood(pgs, initial_tree);

	console.log("Successfully verified page parent / child lists!");

	return true;
}
exports.test = test;

function cleanup() {
	if (!true) {
		process.exit();
		return;
	}
	pagetables.tables(true, false).then(() => {
		return usertables.tables(true, false);
	}).then(() => {
		process.exit();
	}).catch((err) => {
		console.log(err);
		process.exit();
	});
}

if (require.main == module) {
	console.log(" ----------------------------------------------------");
	console.log(" -------------------- PAGE TESTS --------------------");
	console.log(" ----------------------------------------------------");
	usertables.tables(true, true).then(() => {
		return pagetables.tables(true, true);
	}).then(() => {
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