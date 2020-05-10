var cssList = new lx.CssContext();

cssList.addClass('lx-process-slot', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	backgroundColor: 'white'
});

cssList.addClass('lx-process-send', {
	backgroundColor: 'orange'
});

cssList.addClass('lx-process-run', {
	backgroundColor: 'green'
});

cssList.addClass('lx-process-stop', {
	backgroundColor: 'yellow'
});

cssList.addClass('lx-process-close', {
	backgroundColor: 'red'
});

cssList.addClass('lx-process-status-active', {
	backgroundColor: 'green'
});

cssList.addClass('lx-process-status-closed', {
	backgroundColor: 'yellow'
});

cssList.addClass('lx-process-status-crashed', {
	backgroundColor: 'red'
});


return cssList.toString();
