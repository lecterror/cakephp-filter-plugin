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
App::uses('AppHelper', 'View/Helper');

class FilterHelper extends AppHelper
{
	var $_view = null;

	function __construct(View $view, $settings = array())
	{
		$this->_view = $view;
	}

	function filterForm($modelName, $options)
	{
		$view =& $this->_view;

		$output = $view->element
			(
				'filter_form_begin',
				array
				(
					'plugin' => 'Filter',
					'modelName' => $modelName,
					'options' => $options
				),
				array('plugin' => 'Filter')
			);

		$output .= $view->element
			(
				'filter_form_fields',
				array('plugin' => 'Filter'),
				array('plugin' => 'Filter')
			);

		$output .= $view->element
			(
				'filter_form_end',
				array('plugin' => 'Filter'),
				array('plugin' => 'Filter')
			);

		return $output;
	}

	function beginForm($modelName, $options)
	{
		$view =& $this->_view;
		$output = $view->element
			(
				'filter_form_begin',
				array
				(
					'plugin' => 'Filter',
					'modelName' => $modelName,
					'options' => $options
				),
				array('plugin' => 'Filter')
			);

		return $output;
	}

	function inputFields($fields = array())
	{
		$view =& $this->_view;
		$output = $view->element
			(
				'filter_form_fields',
				array
				(
					'plugin' => 'Filter',
					'includeFields' => $fields
				),
				array('plugin' => 'Filter')
			);

		return $output;
	}

	function endForm()
	{
		$view = $this->_view;
		$output = $view->element
			(
				'filter_form_end',
				array(),
				array('plugin' => 'Filter')
			);

		return $output;
	}
}
