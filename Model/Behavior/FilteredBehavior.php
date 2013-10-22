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

	function setup(Model $Model, $settings = array())
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

	function beforeFind(Model $Model, $query)
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
			$this->addFieldToFilter($Model, $query, $settings, $values, $field, $options);
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

	protected function addFieldToFilter(&$Model, &$query, $settings, $values, $field, $field_options)
	{
		$configurationModelName = $Model->alias;
		$configurationFieldName = $field;

		if (strpos($field, '.') !== false)
		{
			list($configurationModelName, $configurationFieldName) = explode('.', $field);
		}

		if (!isset($values[$configurationModelName][$configurationFieldName]) && isset($field_options['default']))
		{
			$values[$configurationModelName][$configurationFieldName] = $field_options['default'];
		}

		if ($field_options['required'] && !isset($values[$configurationModelName][$configurationFieldName]))
		{
			// TODO: implement a bit of a user friendly handling of this scenario..
			trigger_error(__('No value present for required field %s and default value not present', $field));
			return;
		}

		if (!isset($values[$configurationModelName][$configurationFieldName]) || (empty($values[$configurationModelName][$configurationFieldName]) && $values[$configurationModelName][$configurationFieldName] != 0))
		{
			// no value to filter with, just skip this field
			return;
		}

		// the value we get as condition and where it comes from is not the same as the
		// model and field we're using to filter the data
		$filterFieldName = $configurationFieldName;
		$filterModelName = $configurationModelName;
		$relationType = null;

		if ($configurationModelName != $Model->alias)
		{
			$relationTypes = array('hasMany', 'hasOne', 'belongsTo');

			foreach ($relationTypes as $type)
			{
				if (isset($Model->{$type}) && isset($Model->{$type}[$configurationModelName]))
				{
					$filterModelName = 'Filter'.$configurationModelName;
					$relationType = $type;
					break;
				}
			}
		}

		if (isset($field_options['filterField']))
		{
			if (strpos($field_options['filterField'], '.') !== false)
			{
				list($filterModelName, $filterFieldName) = explode('.', $field_options['filterField']);

				if ($filterModelName != $Model->alias)
				{
					$filterModelName = 'Filter'.$filterModelName;
				}
			}
			else
			{
				$filterModelName = $Model->alias;
				$filterFieldName = $field_options['filterField'];
			}
		}

		$realFilterField = sprintf('%s.%s', $filterModelName, $filterFieldName);

		if (isset($Model->{$relationType}) && isset($Model->{$relationType}[$configurationModelName]))
		{
			$relatedModel = $Model->{$configurationModelName};
			$relatedModelAlias = 'Filter'.$relatedModel->alias;

			if (!Set::matches(sprintf('/joins[alias=%s]', $relatedModelAlias), $query))
			{
				$joinStatement = $this->buildFilterJoin($Model, $relatedModel);
				$query['joins'][] = $joinStatement;
			}
		}

		$this->buildFilterConditions
			(
				$query,
				$realFilterField,
				$field_options,
				$values[$configurationModelName][$configurationFieldName]
			);
	}

	/**
	 * Build join conditions from Model to relatedModel.
	 *
	 * @param Model $Model
	 * @param Model $relatedModel
	 * @return array Cake join array
	 */
	protected function buildFilterJoin(Model &$Model, Model &$relatedModel)
	{
		$conditions = array();
		$relationTypes = array('hasMany', 'hasOne', 'belongsTo');

		$relatedModelAlias = null;
		$relationType = null;

		foreach ($relationTypes as $type)
		{
			if (isset($Model->{$type}) && isset($Model->{$type}[$relatedModel->alias]))
			{
				$relatedModelAlias = 'Filter'.$relatedModel->alias;
				$relationType = $type;
				break;
			}
		}

		if (isset($Model->{$relationType}[$relatedModel->alias]['foreignKey'])
			&& $Model->{$relationType}[$relatedModel->alias]['foreignKey'])
		{
			if ($relationType == 'belongsTo')
			{
				$conditions[] = sprintf
					(
						'%s.%s = %s.%s',
						$Model->alias, $Model->{$relationType}[$relatedModel->alias]['foreignKey'],
						$relatedModelAlias, $relatedModel->primaryKey
					);
			}
			else if (in_array($relationType, array('hasMany', 'hasOne')))
			{
				$conditions[] = sprintf
					(
						'%s.%s = %s.%s',
						$Model->alias, $Model->primaryKey,
						$relatedModelAlias, $Model->{$relationType}[$relatedModel->alias]['foreignKey']
					);
			}
		}

		// merge any custom conditions from the relation, but change
		// the alias to our $relatedModelAlias
		if (isset($Model->{$relationType}[$relatedModel->alias]['conditions']) &&
			!empty($Model->{$relationType}[$relatedModel->alias]['conditions']))
		{
			$customConditions = $Model->{$relationType}[$relatedModel->alias]['conditions'];

			if (!is_array($Model->{$relationType}[$relatedModel->alias]['conditions']))
			{
				$customConditions = array($customConditions);
			}

			$filterConditions = preg_replace(sprintf('#(?<![A-Za-z])%s(?![A-Za-z])#', $relatedModel->alias), $relatedModelAlias, $customConditions);
			$conditions = array_merge($conditions, $filterConditions);
		}

		return array
			(
				'table' => $relatedModel->table,
				'alias' => $relatedModelAlias,
				'type' => 'INNER',
				'conditions' => $conditions,
			);
	}

	/**
	 * Build query conditions and add them to $query.
	 *
	 * @param array $query Cake query array.
	 * @param string $field Filter field.
	 * @param array $options Configuration options for this field.
	 * @param mixed $value Field value.
	 */
	protected function buildFilterConditions(array &$query, $field, $options, $value)
	{
		$conditionFieldFormats = array
			(
				'like' => '%s like',
				'ilike' => '%s ilike',
				'contains' => '%s like',
				'startswith' => '%s like',
				'endswith' => '%s like',
				'equal' => '%s',
				'equals' => '%s',
				'=' => '%s',
			);
		$conditionValueFormats = array
			(
				'like' => '%%%s%%',
				'ilike' => '%%%s%%',
				'contains' => '%%%s%%',
				'startswith' => '%s%%',
				'endswith' => '%%%s',
				'equal' => '%s',
				'equals' => '%s',
				'=' => '%s',
			);

		switch ($options['type'])
		{
			case 'text':
				if (strlen(trim(strval($value))) == 0)
				{
					continue;
				}

				$condition = $options['condition'];

				switch ($condition)
				{
					case 'like':
					case 'ilike':
					case 'contains':
					case 'startswith':
					case 'endswith':
					case 'equal':
					case 'equals':
					case '=':
						$formattedField = sprintf($conditionFieldFormats[$condition], $field);
						$formattedValue = sprintf($conditionValueFormats[$condition], $value);

						$query['conditions'][$formattedField] = $formattedValue;
						break;
					default:
						{
							$query['conditions'][$field.' '.$condition] = $value;
						}
						break;
				}

				break;
			case 'select':
				if (is_string($value) && strlen(trim(strval($value))) == 0)
				{
					continue;
				}

				$query['conditions'][$field] = $value;
				break;
			case 'checkbox':
				$query['conditions'][$field] = $value;
				break;
		}
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
