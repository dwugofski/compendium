
const nunjucks = require('nunjucks')
const express = require('express');
const bparser = require('body-parser');
const session = require('express-session');
const cookies = require('cookie-parser');
const user = require('./users/user');
const crypto = require('crypto');
const compendium = express();
const port = 3000;

const User = user.User;
const LoginToken = user.LoginToken;

const USER_SESSION = 'user_sess';

compendium.listen(port, () => console.log(`Example app listening on port ${port}`));

nunjucks.configure('views', {
	autoescape: true,
	express: compendium
});

compendium.use(express.static("public"));

compendium.use(bparser.urlencoded({extended : true}));

compendium.use(cookies());
compendium.use(session({
	key: USER_SESSION,
	secret: crypto.randomBytes(64).toString('hex'),
	resave: false,
	saveUninitialized: false,
	cookie: {
		maxAge: 24*60*60*1000, // Expiry date in ms: expires in 1 day
	}
}));
compendium.use((req, res, next) => {
	let token = req.cookies['compendium_login_token'];
	if (token) {
		token = JSON.parse(token);
		LoginToken.is(token.selector, 'selector').then(is_token => {
			if (is_token) {
				return LoginToken.build(token.selector, 'selector').then(login_token => {
					return User.build(login_token.props.userid, 'id');
				}).then(usr => {
					if (!(req.session.user) || req.session.user._id != usr.props.id) {
						return usr.verify(token, true).then(is_valid => {
							if (is_valid) req.session.user = usr;
							else res.clearCookie('compendium_login_token');
							next();
						});
					} else next();
				});
			} else {
				res.clearCookie('compendium_login_token');
				next();
			}
		}).catch(err => {next(err);});
	}
	else next();
});
compendium.use((req, res, next) => {
	if (req.cookies[USER_SESSION] && !req.session.user) res.clearCookie(USER_SESSION);
	next();
});

compendium.get('/', (req, res, next) => {
	var locals = {};
	//locals.user = req.session.user;

	locals.sidebar_items = [
		{id: "siitem0", classname: "first", link: "./", text: "Foobar"},
		{id: "siitem1", classname: "", link: "./", text: "Foobar"},
		{id: "siitem2", classname: "parent", link: "./", text: "Foobar", children: [
			{id: "siitem3", classname: "first", link: "./", text: "Foobar"},
			{id: "siitem4", classname: "parent", link: "./", text: "Foobar", children: [
				{id: "siitem5", classname: "first", link: "./", text: "Foobarrrr"},
				{id: "siitem6", classname: "parent", link: "./", text: "Foobarrrr", children: [
					{id: "siitem7", classname: "first last", link: "./", text: "Foobarrrr"}
				]},
				{id: "siitem8", classname: "restart last", link: "./", text: "Foobarrrr"}
			]},
			{id: "siitem9", classname: "restart", link: "./", text: "Foobar"},
			{id: "siitem10", classname: "last", link: "./", text: "Foobar"}
		]},
		{id: "siitem11", classname: "restart", link: "./", text: "Foobar"},
		{id: "siitem12", classname: "last", link: "./", text: "Foobar"},
	];

	var mp = new Promise((resolve, reject) => {resolve();}).then(() => {
		if (req.session.user) {
			return User.is(req.session.user._username, 'username').then((is_user) => {
				if (is_user) {
					return User.build(req.session.user._username, 'username').then((usr) => {
						locals.user = usr.props;
					});
				} else return;
			});
		} else locals.user = undefined;
	}).then(() => {
		res.render('home.njk', locals);
	}).catch(err => {next(err);});
});

compendium.post('/signup', user.signup);
compendium.post('/login', user.login);

compendium.use((err, req, res, next) => {
	console.log(err);
	res.status(500).send('Something broke!');
});