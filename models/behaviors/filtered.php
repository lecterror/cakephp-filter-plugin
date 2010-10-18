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

App::import('Core', 'Sanitize');

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

			if (!isset($values[$fieldModel][$fieldName]) || is_null($values[$fieldModel][$fieldName]))
			{
				// no value to filter with, just skip this field
				continue;
			}

			// the value we get as condition and where it comes from is not the same as the
			// model and field we're using to filter the data
			$filterByField = $fieldName;
			$filterByModel = $Model->alias;

			if ($fieldModel != $Model->name && isset($Model->hasMany) && isset($Model->hasMany[$fieldModel]))
			{
				$filterByModel = $fieldModel;
			}

			if (isset($options['filterField']))
			{
				if (strpos($options['filterField'], '.') !== false)
				{
					list($tmpFieldModel, $tmpFieldName) = explode('.', $options['filterField']);
					$filterByField = $tmpFieldName;
				}
				else
				{
					$filterByField = $options['filterField'];
				}
			}

			$realFilterField = sprintf('%s.%s', $filterByModel, $filterByField);

			if (isset($Model->hasMany) && isset($Model->hasMany[$fieldModel]))
				//|| isset($Model->hasAndBelongsToMany) && isset($Model->hasAndBelongsToMany[$fieldModel]))
			{
				$relatedModel = $Model->$fieldModel;

				if (!Set::check(sprintf('/joins[alias=%s]', $relatedModel->alias), $query))
				{
					$conditions = array();

					if (isset($Model->hasMany[$fieldModel]['foreignKey'])
						&& $Model->hasMany[$fieldModel]['foreignKey'])
					{
						$conditions[] = sprintf
							(
								'%s.%s = %s.%s',
								$Model->alias, $Model->primaryKey,
								$relatedModel->alias, $Model->hasMany[$fieldModel]['foreignKey']
							);
					}

					if (isset($Model->hasMany[$fieldModel]['conditions']) && is_array($Model->hasMany[$fieldModel]['conditions']))
					{
						$conditions = array_merge($conditions, $Model->hasMany[$fieldModel]['conditions']);
					}

					$query['joins'][] = array
						(
							'table' => $relatedModel->table,
							'alias' => $relatedModel->alias,
							'type' => 'INNER',
							'conditions' => $conditions,
							'fields' => false
						);
				}
			}

			// TODO: handle NULLs?
			switch ($options['type'])
			{
				case 'text':
					if (strlen(trim(strval($values[$fieldModel][$fieldName]))) == 0)
					{
						continue;
					}

					if ($options['condition'] == 'like')
					{
						$query['conditions'][$realFilterField.' like'] = '%'.$values[$fieldModel][$fieldName].'%';
					}
					else if ($options['condition'] == '=')
					{
						$query['conditions'][$realFilterField] = $values[$fieldModel][$fieldName];
					}
					else
					{
						$query['conditions'][$realFilterField.' '.$options['condition']] = $values[$fieldModel][$fieldName];
					}
					break;
				case 'select':
					if (strlen(trim(strval($values[$fieldModel][$fieldName]))) == 0)
					{
						continue;
					}

					$query['conditions'][$realFilterField] = $values[$fieldModel][$fieldName];
					break;
				case 'checkbox':
					$query['conditions'][$realFilterField] = $values[$fieldModel][$fieldName];
					break;
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

	function _setFilterValues(&$Model, $method, $values = array())
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
