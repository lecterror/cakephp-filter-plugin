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

App::uses('Sanitize', 'Utility');

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
			$fieldModelName = $Model->alias;
			$fieldName = $field;

			if (strpos($field, '.') !== false)
			{
				list($fieldModelName, $fieldName) = explode('.', $field);
			}

			if (!isset($values[$fieldModelName][$fieldName]) && isset($options['default']))
			{
				$values[$fieldModelName][$fieldName] = $options['default'];
			}

			if ($options['required'] && !isset($values[$fieldModelName][$fieldName]))
			{
				// TODO: implement a bit of a user friendly handling of this scenario..
				trigger_error(__('No value present for required field %s and default value not present', $field));
				return;
			}

			if (!isset($values[$fieldModelName][$fieldName]) || is_null($values[$fieldModelName][$fieldName]))
			{
				// no value to filter with, just skip this field
				continue;
			}

			// the value we get as condition and where it comes from is not the same as the
			// model and field we're using to filter the data
			$filterByField = $fieldName;
			$filterByModel = $Model->alias;
			$relationType = null;

			if ($fieldModelName != $Model->name)
			{
				$relationTypes = array('hasMany', 'hasOne');

				foreach ($relationTypes as $type)
				{
					if (isset($Model->{$type}) && isset($Model->{$type}[$fieldModelName]))
					{
						$filterByModel = 'Filter'.$fieldModelName;
						$relationType = $type;
						break;
					}
				}
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

			if (isset($Model->{$relationType}) && isset($Model->{$relationType}[$fieldModelName]))
			{
				$relatedModel = $Model->$fieldModelName;
				$relatedModelAlias = 'Filter'.$relatedModel->alias;

				if (!Set::matches(sprintf('/joins[alias=%s]', $relatedModelAlias), $query))
				{
					$conditions = array();

					if (isset($Model->{$relationType}[$fieldModelName]['foreignKey'])
						&& $Model->{$relationType}[$fieldModelName]['foreignKey'])
					{
						$conditions[] = sprintf
							(
								'%s.%s = %s.%s',
								$Model->alias, $Model->primaryKey,
								$relatedModelAlias, $Model->{$relationType}[$fieldModelName]['foreignKey']
							);
					}

					// merge any custom conditions from the relation, but change
					// the alias to our $relatedModelAlias
					if (isset($Model->{$relationType}[$fieldModelName]['conditions']) && !empty($Model->{$relationType}[$fieldModelName]['conditions']))
					{
						$customConditions = $Model->{$relationType}[$fieldModelName]['conditions'];

						if (!is_array($Model->{$relationType}[$fieldModelName]['conditions']))
						{
							$customConditions = array($customConditions);
						}

						$filterConditions = preg_replace(sprintf('#(?<![A-Za-z])%s(?![A-Za-z])#', $relatedModel->alias), $relatedModelAlias, $customConditions);
						$conditions = array_merge($conditions, $filterConditions);
					}

					$query['joins'][] = array
						(
							'table' => $relatedModel->table,
							'alias' => $relatedModelAlias,
							'type' => 'INNER',
							'conditions' => $conditions,
						);
				}
			}

			// TODO: handle NULLs?
			switch ($options['type'])
			{
				case 'text':
					if (strlen(trim(strval($values[$fieldModelName][$fieldName]))) == 0)
					{
						continue;
					}

					switch ($options['condition'])
					{
						case 'like':
						case 'contains':
							{
								$query['conditions'][$realFilterField.' like'] = '%'.$values[$fieldModelName][$fieldName].'%';
							}
							break;
						case 'startswith':
							{
								$query['conditions'][$realFilterField.' like'] = $values[$fieldModelName][$fieldName].'%';
							}
							break;
						case 'endswith':
							{
								$query['conditions'][$realFilterField.' like'] = '%'.$values[$fieldModelName][$fieldName];
							}
							break;
						case '=':
							{
								$query['conditions'][$realFilterField] = $values[$fieldModelName][$fieldName];
							}
							break;
						default:
							{
								$query['conditions'][$realFilterField.' '.$options['condition']] = $values[$fieldModelName][$fieldName];
							}
							break;
					}

					break;
				case 'select':
					if (strlen(trim(strval($values[$fieldModelName][$fieldName]))) == 0)
					{
						continue;
					}

					$query['conditions'][$realFilterField] = $values[$fieldModelName][$fieldName];
					break;
				case 'checkbox':
					$query['conditions'][$realFilterField] = $values[$fieldModelName][$fieldName];
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
