<?php
/*
This file is part of CakePHP Filter Plugin.
 
CakePHP Filter Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
 
CakePHP Filter Plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with CakePHP Filter Plugin. If not, see <http://www.gnu.org/licenses/>.
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
