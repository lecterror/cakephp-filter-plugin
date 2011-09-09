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
	var $_view = null;
	function __construct(View $View, $settings = array())
	{
	  $this->_view = $View;
	}

	function filterForm($modelName, $options)
	{
		$view =& $this->_view;
		$output = $view->element('filter_form_begin', array('plugin' => 'Filter', 'modelName' => $modelName, 'options' => $options), array('plugin' => 'Filter'));
		$output .= $view->element('filter_form_fields', array('plugin' => 'Filter'), array('plugin' => 'Filter'));
		$output .= $view->element('filter_form_end', array('plugin' => 'Filter'), array('plugin' => 'Filter'));

		return $this->output($output);
	}

	function beginForm($modelName, $options)
	{
		$view =& $this->_view;
		return $this->output($view->element('filter_form_begin', array('plugin' => 'Filter', 'modelName' => $modelName, 'options' => $options)), array('plugin' => 'Filter'));
	}

	function inputFields($fields = array())
	{
		$view =& $this->_view;
		return $this->output($view->element('filter_form_fields', array('plugin' => 'Filter', 'includeFields' => $fields)), array('plugin' => 'Filter'));
	}

	function endForm()
	{
		$view = $this->_view;
		return $this->output($view->element('filter_form_end', array('plugin' => 'Filter')));
	}
}
