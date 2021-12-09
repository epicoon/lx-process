/**
 * @const {lx.Plugin} Plugin
 * @const {lx.Snippet} Snippet
 */

#lx:use lx.Form;

const processesList = lx.ModelCollection.create({
	schema: [
		'serviceName',
		'name',
		'index',
		'pid',
		'status'
	]
});

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

Snippet->>listStream.matrix({
	items: processesList,
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

		var butSend  = new lx.Box({ width: '40px', css: 'lx-process-send',  click: onProcessSend  });
		var butRun   = new lx.Box({ width: '40px', css: 'lx-process-run',   click: onProcessRun   });
		var butStop  = new lx.Box({ width: '40px', css: 'lx-process-stop',  click: onProcessStop  });
		var butClose = new lx.Box({ width: '40px', css: 'lx-process-close', click: onProcessClose });

		butSend.setField('status', val=>butSend.disabled(ProcessStatuses.sendIsUnavailable(val)));
		butRun.setField('status', val=>butRun.disabled(ProcessStatuses.runIsUnavailable(val)));
		butStop.setField('status', val=>butStop.disabled(ProcessStatuses.stopIsUnavailable(val)));
	}
});

function onProcessSend() {
	var index = this.parent.index,
		proc = processesList.at(index);

	var messageBox = Snippet->>messageBox;
	messageBox.__process = proc;
	messageBox.setHeaderText('Message to process: ' + proc.serviceName + ', ' + proc.name + ', ' + proc.index);
	messageBox.show();
	messageBox->>messageTextBox.focus();
}

function onProcessRun() {
	var index = this.parent.index,
		proc = processesList.at(index);
	Snippet->blocker.show();
	^Respondent.rerunProcess(proc.name, proc.index).then(res=>{
		Snippet->blocker.hide();
		if (!res.success) {
			lx.Tost.error(res.data || 'Unknown error');
			return;
		}
		loadProcessesData();
	}).catch(res=>{
		Snippet->blocker.hide();
		if (res == '')
			lx.Tost.error('Internal server error');
		else
			lx.Tost.error('Unknown error');
	});
}

function onProcessStop() {
	var index = this.parent.index,
		proc = processesList.at(index);
	Snippet->blocker.show();
	^Respondent.stopProcess(proc.name, proc.index).then(res=>{
		Snippet->blocker.hide();
		if (!res.success) {
			lx.Tost.error(res.data || 'Unknown error');
			return;
		}

		loadProcessesData();
	});
}

function onProcessClose() {
	var index = this.parent.index,
		proc = processesList.at(index);
	Snippet->blocker.show();
	^Respondent.deleteProcess(proc.name, proc.index).then(res=>{
		Snippet->blocker.hide();
		if (!res.success) {
			lx.Tost.error(res.data || 'Unknown error');
			return;
		}

		loadProcessesData();
	});
}


Snippet->>butCloseMessageBox.click(closeMessageBox);
function closeMessageBox() {
	var messageBox = Snippet->>messageBox;
	delete messageBox.__process;
	messageBox.hide();
	messageBox->>messageTextBox.value('');
}


Snippet->>butSendMessage.click(()=>{
	var messageBox = Snippet->>messageBox;
	var message = messageBox->>messageTextBox.value();
	if (message == '') {
		lx.Tost.warning('Message is empty');
		return;
	}

	var proc = messageBox.__process;
	closeMessageBox();
	Snippet->blocker.show();
	^Respondent.sendMessage(proc.name, proc.index, message).then(res=>{
		Snippet->blocker.hide();
		if (!res.success) {
			lx.Tost.error(res.data || 'Unknown error');
			return;
		}

		lx.Tost('Message sent');
	});
});


Snippet->>butAddProcess.click(()=>{
	Plugin.root->inputPopup.open(['Service name', 'Process name']).confirm((values)=>{
		var serviceName = values[0],
			processName = values[1];
		if (serviceName == '') {
			lx.Tost.warning('Service name is empty');
			return;
		}
		if (processName == '') {
			lx.Tost.warning('Process name is empty');
			return;
		}

		Snippet->blocker.show();
		^Respondent.addProcess(serviceName, processName).then(res=>{
			Snippet->blocker.hide();
			if (!res.success) {
				lx.Tost.error(res.data || 'Unknown error');
				return;
			}

			loadProcessesData();
			lx.Tost('Process has added');
		});
	});
});


function loadProcessesData() {
	^Respondent.loadProcessesData().then(res=>processesList.reset(res.data));
}


loadProcessesData();
