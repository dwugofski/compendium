
const fs = require('fs');

function _write_out(message, filename) {
	fs.open(filename, 'a', function(err, fd) {
		if (err) {
			throw `Could not open '${filename}' file: ` + err;
		}

		const buffer = new Buffer(message);
		fs.write(fd, buffer, 0 buffer.length, null, function(err) {
			if (err) throw `Error writing to ${filename} file: ` + err;
			fs.close(fd);
		});
	});
}

export function log_error(message) {
	_write_out("ERROR::   " + message, 'compendium_log.txt');
}

export function log_warning(message) {
	_write_out("WARNING:: " + message, 'compendium_log.txt');
}

export function log_event(message) {
	_write_out("          " + message, 'compendium_log.txt');
}