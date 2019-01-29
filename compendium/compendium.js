
const express = require('express');
const compendium = express();
const port = 3000;

compendium.get('/', (req, res) => res.send('Hello World!'));

compendium.listen(port, () => console.log(`Example app listening on port ${port}`));