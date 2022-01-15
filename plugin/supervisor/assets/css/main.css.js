#lx:use lx.CssColorSchema;
#lx:use lx.MainCssContext;

const cssContext = lx.MainCssContext.instance;

cssContext.addClass('lx-process-slot', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	backgroundColor: 'white'
});

cssContext.inheritClasses({
	'lx-process-send' : { backgroundColor: lx.CssColorSchema.coldMainColor,    '@icon': ['\\2709', 16] },
	'lx-process-run'  : { backgroundColor: lx.CssColorSchema.checkedMainColor, '@icon': ['\\21BB', 16] },
	'lx-process-stop' : { backgroundColor: lx.CssColorSchema.neutralMainColor, '@icon': ['\\2296', 16] },
	'lx-process-close': { backgroundColor: lx.CssColorSchema.hotMainColor,     '@icon': ['\\2297', 16] }
}, 'ActiveButton');

cssContext.inheritClasses({
	'lx-process-status-active':  {backgroundColor: lx.CssColorSchema.checkedSoftColor},
	'lx-process-status-closed':  {backgroundColor: lx.CssColorSchema.neutralSoftColor},
	'lx-process-status-crashed': {backgroundColor: lx.CssColorSchema.hotSoftColor}
}, 'Input');

return cssContext.toString();
