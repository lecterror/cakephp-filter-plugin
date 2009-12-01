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

class FilteredBehavior extends ModelBehavior
{
	/**
	 * Keeps current values after filter form post.
	 *
	 * @var array 
	 */
	var $_filterValues = array();

	var $mapMethods = array('/setFilterValues/' => '_setFilterValues');

	function setup(&$Model, $settings = array())
	{
		foreach ($settings as $key => $value)
		{
			if (!is_array($value))
			{
				$key = $value;
				$value = array();
			}

			$this->settings[$Model->alias][$key] = array_merge
				(
					array
					(
						'type' => 'text',
						'condition' => 'like',
						'required' => false,
						'selectOptions' => array()
					),
					$value
				);
		}

		$this->_filterValues[$Model->alias] = array();
	}

	function beforeFind(&$Model, $query)
	{
		if (isset($query['nofilter']) && $query['nofilter'] === true)
		{
			return $query;
		}

		if (method_exists($Model, 'beforeDataFilter'))
		{
			$callbackOptions['values'] = $this->_filterValues[$Model->alias];
			$callbackOptions['settings'] = $this->settings[$Model->alias];

			if (!$Model->beforeDataFilter($query, $callbackOptions))
			{
				return $query;
			}
		}

		if (!isset($this->settings[$Model->alias]))
		{
			return $query;
		}

		$settings = $this->settings[$Model->alias];
		$values = $this->_filterValues[$Model->alias];

		foreach ($settings as $field => $options)
		{
			$fieldModel = $Model->alias;
			$fieldName = $field;

			if (strpos($field, '.') !== false)
			{
				list($fieldModel, $fieldName) = explode('.', $field);
			}

			if (!isset($values[$fieldModel][$fieldName]) && isset($options['default']))
			{
				$values[$fieldModel][$fieldName] = $options['default'];
			}

			if ($options['required'] && !isset($values[$fieldModel][$fieldName]))
			{
				// TODO: implement a bit of a user friendly handling of this scenario..
				trigger_error(sprintf(__('No value present for required field %s and default value not present', true), $field));
				return;
			}

			//if (!$options['required'] && (!isset($values[$fieldModel][$fieldName]) || is_null($values[$fieldModel][$fieldName])))
			if (!isset($values[$fieldModel][$fieldName]) || is_null($values[$fieldModel][$fieldName]))
			{
				continue; // TODO: recosider this..
			}

			// allow for cross-database filters..
			if (isset($options['filterField']))
			{
				$field = $options['filterField'];
			}

			// TODO: handle NULLs?
			if ($options['type'] == 'text')
			{
				if (strlen(trim(strval($values[$fieldModel][$fieldName]))) == 0)
				{
					continue;
				}

				if ($options['condition'] == 'like')
				{
					$query['conditions'][$field.' like'] = '%'.$values[$fieldModel][$fieldName].'%';
				}
				else if ($options['condition'] == '=')
				{
					$query['conditions'][$field] = $values[$fieldModel][$fieldName];
				}
				else
				{
					$query['conditions'][$field.' '.$options['condition']] = $values[$fieldModel][$fieldName];
				}
			}
			else if ($options['type'] == 'select')
			{
				if (strlen(trim(strval($values[$fieldModel][$fieldName]))) == 0)
				{
					continue;
				}

				$query['conditions'][$field] = $values[$fieldModel][$fieldName];
			}
			else if ($options['type'] == 'checkbox')
			{
				$query['conditions'][$field] = $values[$fieldModel][$fieldName];
			}
		}

		if (method_exists($Model, 'afterDataFilter'))
		{
			$callbackOptions['values'] = $this->_filterValues[$Model->alias];
			$callbackOptions['settings'] = $this->settings[$Model->alias];

			$result = $Model->afterDataFilter($query, $callbackOptions);

			if (is_array($result))
			{
				$query = $result;
			}
		}

		return $query;
	}

	function _setFilterValues(&$Model, $method, $values)
	{
		$values = Sanitize::clean
			(
				$values,
				array
				(
					'connection' => $Model->useDbConfig,
					'odd_spaces' => false,
					'encode' => false,
					'dollar' => false,
					'carriage' => false,
					'unicode' => false,
					'escape' => true,
					'backslash' => false
				)
			);

		$this->_filterValues[$Model->alias] = array_merge($this->_filterValues[$Model->alias], $values);
	}
}
