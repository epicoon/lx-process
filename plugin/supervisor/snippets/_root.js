/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.ActiveBox;

var listBox = new lx.ActiveBox({
	key: 'listBox',
	geom: true,
	header: 'Processes'
});
listBox.setSnippet('listBox');

var messageBox = new lx.ActiveBox({
	key: 'messageBox',
	geom: [20, 15, 60, 60,],
	header: 'Message'
});
messageBox.setSnippet('messageBox');
messageBox.hide();

Snippet.addSnippet({plugin:'lx/tools:snippets', snippet:'inputPopup'});

var blocker = new lx.Box({
	key: 'blocker',
	geom: true
});
blocker.fill('black');
blocker.style('opacity', 0.5);
blocker.style('zIndex', 5000);
blocker.hide();
