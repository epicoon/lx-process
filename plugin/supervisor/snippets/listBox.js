/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;

var mainStream = new lx.Box({
	geom: true
});
mainStream.stream({indent: '10px'});

mainStream.begin();
	var stream = new lx.Box({ key: 'listStream' });
	stream.stream({direction: lx.VERTICAL, step: '10px'});

	var b = new lx.Button({ key: 'butAddProcess', text: 'Add' });
mainStream.end();
