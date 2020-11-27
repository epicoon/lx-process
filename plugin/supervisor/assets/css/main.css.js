#lx:use #lx:php(\lx::$app->assets->getCssColorSchema());
#lx:use lx.MainCssContext;

cssContext.addClass('lx-process-slot', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	backgroundColor: 'white'
});

cssContext.inheritClasses({
	'lx-process-send' : { backgroundColor: coldMainColor,    '@icon': ['\\2709', 16] },
	'lx-process-run'  : { backgroundColor: checkedMainColor, '@icon': ['\\21BB', 16] },
	'lx-process-stop' : { backgroundColor: neutralMainColor, '@icon': ['\\2296', 16] },
	'lx-process-close': { backgroundColor: hotMainColor,     '@icon': ['\\2297', 16] }
}, 'ActiveButton');

cssContext.inheritClasses({
	'lx-process-status-active':  {backgroundColor: checkedSoftColor},
	'lx-process-status-closed':  {backgroundColor: neutralSoftColor},
	'lx-process-status-crashed': {backgroundColor: hotSoftColor}
}, 'Input');

return cssContext.toString();
