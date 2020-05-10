/**
 * @const {lx.Application} App
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Button;
#lx:use lx.Textarea;

var grid = new lx.Box({
	geom: true
});
grid.streamProportional({indent: '10px'});

grid.begin();
	new lx.Textarea({key: 'messageTextBox'});

	var buts = new lx.Box({height: '40px'});
	buts.gridProportional({cols: 2, step: '10px'});
	buts.begin();
		new lx.Button({ key: 'butSendMessage',     text: 'Send'  });
		new lx.Button({ key: 'butCloseMessageBox', text: 'Close' });
	buts.end();
grid.end();
