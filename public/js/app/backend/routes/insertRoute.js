(function () {
	var $actionsList = $('#actions-list');
	var moduleActions;

	$('#route-module').on('input', function () {
		var moduleName = $(this).val();
		var actions = Lighp.vars().actionsList;

		$actionsList.empty();

		moduleActions = actions[moduleName];
		if (!moduleActions) {
			return;
		}

		for (var i = 0; i < moduleActions.length; i++) {
			var action = moduleActions[i];

			$actionsList.append('<option value="'+action.action+'"></option>');
		}
	});

	$('#route-action').on('input', function () {
		var actionName = $(this).val();

		for (var i = 0; i < moduleActions.length; i++) {
			var action = moduleActions[i];

			if (action.action == actionName) {
				$('#route-vars').val((action.vars || []).join());
				break;
			}
		}
	});
})();