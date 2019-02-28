
const nunjucks = require('nunjucks')
const express = require('express');
const bparser = require('body-parser');
const session = require('express-session');
const cookies = require('cookie-parser');
const user = require('./users/user');
const crypto = require('crypto');
const compendium = express();
const port = 3000;

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
	if (req.cookies[USER_SESSION] && !req.session.user) res.clearCookie(USER_SESSION);
	next();
});

compendium.get('/', (req, res) => {
	var locals = {};
	//locals.user = req.session.user;

	res.render('home.njk');
});

compendium.post('/signup', user.signup);

compendium.use((err, req, res, next) => {
	console.log(err);
	res.status(500).send('Something broke!');
});