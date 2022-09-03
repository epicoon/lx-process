#lx:use lx.Form;

class Plugin extends lx.Plugin {
	initCss(css) {
		css.addClass('lx-process-slot', {
			overflow: 'hidden',
			whiteSpace: 'nowrap',
			textOverflow: 'ellipsis',
			backgroundColor: css.preset.altMainBackgroundColor
		});

		css.inheritClasses({
			'lx-process-send' : { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.coldLightColor,    '@icon': ['\\2709', 16] },
			'lx-process-run'  : { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.checkedLightColor, '@icon': ['\\21BB', 16] },
			'lx-process-stop' : { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.neutralLightColor, '@icon': ['\\2296', 16] },
			'lx-process-close': { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.hotLightColor,     '@icon': ['\\2297', 16] }
		}, 'ActiveButton');

		css.inheritClasses({
			'lx-process-status-active':  { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.checkedLightColor },
			'lx-process-status-closed':  { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.neutralLightColor },
			'lx-process-status-crashed': { color:css.preset.widgetColoredIconColor, backgroundColor: css.preset.hotLightColor }
		}, 'Input');
	}

	run() {
		this.processesList = lx.ModelCollection.create({
			schema: [
				'serviceName',
				'name',
				'index',
				'pid',
				'status'
			]
		});

		__init(this);
		__loadProcessesData(this);
	}
}

class ProcessStatuses {
	#lx:const
		ACTIVE = lx\process\ProcessConst::PROCESS_STATUS_ACTIVE,
		CLOSED = lx\process\ProcessConst::PROCESS_STATUS_CLOSED,
		CRASHED = lx\process\ProcessConst::PROCESS_STATUS_CRASHED;

	static sendIsUnavailable(val) {
		return val != this.ACTIVE;
	}

	static runIsUnavailable(val) {
		return val == this.ACTIVE;
	}

	static stopIsUnavailable(val) {
		return val != this.ACTIVE;
	}
}

function __init(plugin) {
	plugin->>listStream.matrix({
		items: plugin.processesList,
		itemBox: lx.Form,
		itemRender: function(form, model) {
			form.streamProportional({direction:lx.HORIZONTAL, step:'10px'});
			form.fields({
				pid        : [lx.Box, { width: 2, css: 'lx-process-slot' }],
				serviceName: [lx.Box, { width: 2, css: 'lx-process-slot' }],
				name       : [lx.Box, { width: 4, css: 'lx-process-slot' }],
				index      : [lx.Box, { width: 1, css: 'lx-process-slot' }]
			});

			var status = new lx.Box({width: 2, css: 'lx-process-slot'});
			status.setField('status', function(val) {
				this.removeClass(
					'lx-process-status-active',
					'lx-process-status-closed',
					'lx-process-status-crashed'
				);

				switch (val) {
					case ProcessStatuses.ACTIVE:
						this.addClass('lx-process-status-active');
						this.text('active');
						break;
					case ProcessStatuses.CLOSED:
						this.addClass('lx-process-status-closed');
						this.text('closed');
						break;
					case ProcessStatuses.CRASHED:
						this.addClass('lx-process-status-crashed');
						this.text('crashed');
						break;
				}
			});

			form.getChildren().forEach(c=>c.align(lx.CENTER, lx.MIDDLE));

			var butSend  = new lx.Box({
				width: '40px', css: 'lx-process-send',  click: function() { __onProcessSend(this, plugin); }
			});
			var butRun   = new lx.Box({
				width: '40px', css: 'lx-process-run',   click: function() { __onProcessRun(this, plugin); }
			});
			var butStop  = new lx.Box({
				width: '40px', css: 'lx-process-stop',  click: function() { __onProcessStop(this, plugin); }
			});
			var butClose = new lx.Box({
				width: '40px', css: 'lx-process-close', click: function() { __onProcessClose(this, plugin); }
			});

			butSend.setField('status', val=>butSend.disabled(ProcessStatuses.sendIsUnavailable(val)));
			butRun.setField('status', val=>butRun.disabled(ProcessStatuses.runIsUnavailable(val)));
			butStop.setField('status', val=>butStop.disabled(ProcessStatuses.stopIsUnavailable(val)));
		}
	});

	plugin->>butCloseMessageBox.click(function() { __closeMessageBox(plugin); });

	plugin->>butSendMessage.click(()=>{
		var messageBox = plugin->>messageBox;
		var message = messageBox->>messageTextBox.value();
		if (message == '') {
			lx.tostWarning('Message is empty');
			return;
		}

		var proc = messageBox.__process;
		__closeMessageBox(plugin);
		plugin.root->blocker.show();
		^Respondent.sendMessage(proc.name, proc.index, message).then(res=>{
			plugin.root->blocker.hide();
			if (!res.success) {
				lx.tostError(res.data || 'Unknown error');
				return;
			}

			lx.tostMessage('Message sent');
		});
	});

	plugin->>butAddProcess.click(()=>{
		plugin.root->inputPopup.open(['Service name', 'Process name']).confirm((values)=>{
			var serviceName = values[0],
				processName = values[1];
			if (serviceName == '') {
				lx.tostWarning('Service name is empty');
				return;
			}
			if (processName == '') {
				lx.tostWarning('Process name is empty');
				return;
			}

			plugin.root->blocker.show();
			^Respondent.addProcess(serviceName, processName).then(res=>{
				plugin.root->blocker.hide();
				if (!res.success) {
					lx.tostError(res.data || 'Unknown error');
					return;
				}

				__loadProcessesData(plugin);
				lx.tostMessage('Process has added');
			});
		});
	});
}

function __onProcessSend(self, plugin) {
	var index = self.parent.index,
		proc = plugin.processesList.at(index);

	var messageBox = plugin->>messageBox;
	messageBox.__process = proc;
	messageBox.setHeaderText('Message to process: ' + proc.serviceName + ', ' + proc.name + ', ' + proc.index);
	messageBox.show();
	messageBox->>messageTextBox.focus();
}

function __onProcessRun(self, plugin) {
	var index = self.parent.index,
		proc = plugin.processesList.at(index);
	plugin.root->blocker.show();
	^Respondent.rerunProcess(proc.name, proc.index).then(res=>{
		plugin.root->blocker.hide();
		if (!res.success) {
			lx.tostError(res.data || 'Unknown error');
			return;
		}
		__loadProcessesData(plugin);
	}).catch(res=>{
		plugin.root->blocker.hide();
		if (res == '')
			lx.tostError('Internal server error');
		else
			lx.tostError('Unknown error');
	});
}

function __onProcessStop(self, plugin) {
	var index = self.parent.index,
		proc = plugin.processesList.at(index);
	plugin.root->blocker.show();
	^Respondent.stopProcess(proc.name, proc.index).then(res=>{
		plugin.root->blocker.hide();
		if (!res.success) {
			lx.tostError(res.data || 'Unknown error');
			return;
		}

		__loadProcessesData(plugin);
	});
}

function __onProcessClose(self, plugin) {
	var index = self.parent.index,
		proc = plugin.processesList.at(index);
	plugin.root->blocker.show();
	^Respondent.deleteProcess(proc.name, proc.index).then(res=>{
		plugin.root->blocker.hide();
		if (!res.success) {
			lx.tostError(res.data || 'Unknown error');
			return;
		}

		__loadProcessesData(plugin);
	});
}

function __closeMessageBox(plugin) {
	var messageBox = plugin->>messageBox;
	delete messageBox.__process;
	messageBox.hide();
	messageBox->>messageTextBox.value('');
}

function __loadProcessesData(plugin) {
	^Respondent.loadProcessesData().then(res=>plugin.processesList.reset(res.data));
}


