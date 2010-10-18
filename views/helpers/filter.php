<?php
/**
	CakePHP Filter Plugin

	Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
	<http://lecterror.com/>

	Multi-licensed under:
		MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
		LGPL <http://www.gnu.org/licenses/lgpl.html>
		GPL <http://www.gnu.org/licenses/gpl.html>
*/

class FilterHelper extends AppHelper
{
	function filterForm($modelName, $options)
	{
		$view =& ClassRegistry::getObject('view');

		$output = $view->element('filter_form_begin', array('plugin' => 'filter', 'modelName' => $modelName, 'options' => $options));
		$output .= $view->element('filter_form_fields', array('plugin' => 'filter'));
		$output .= $view->element('filter_form_end', array('plugin' => 'filter'));

		return $this->output($output);
	}

	function beginForm($modelName, $options)
	{
		$view =& ClassRegistry::getObject('view');
		return $this->output($view->element('filter_form_begin', array('plugin' => 'filter', 'modelName' => $modelName, 'options' => $options)));
	}

	function inputFields($fields = array())
	{
		$view =& ClassRegistry::getObject('view');
		return $this->output($view->element('filter_form_fields', array('plugin' => 'filter', 'includeFields' => $fields)));
	}

	function endForm()
	{
		$view =& ClassRegistry::getObject('view');
		return $this->output($view->element('filter_form_end', array('plugin' => 'filter')));
	}
}
