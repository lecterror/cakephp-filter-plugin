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
	 * @var mixed[]
	 */
	protected $_filterValues = array();

	/**
	 * {@inheritDoc}
	 *
	 * @param \Model $Model Model using this behavior
	 * @param mixed[] $settings Configuration settings for $model
	 * @return void
	 */
	public function setup(Model $Model, $settings = array())
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

	/**
	 * {@inheritDoc}
	 *
	 * @param \Model $Model Model using this behavior
	 * @param mixed[] $query Data used to execute this query, i.e. conditions, order, etc.
	 * @return bool|mixed[]
	 */
	public function beforeFind(Model $Model, $query)
	{
		if (isset($query['nofilter']) && $query['nofilter'] === true)
		{
			return $query;
		}
		$callbackOptions = array();
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

	/**
	 * @param \Model $Model
	 * @param mixed[] $query
	 * @param mixed[] $settings
	 * @param mixed[] $values
	 * @param string $field
	 * @param mixed[] $field_options
	 * @return void
	 */
	protected function addFieldToFilter($Model, &$query, $settings, $values, $field, $field_options)
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
		$linkModelName = null;
		$relationType = null;

		if ($configurationModelName != $Model->alias)
		{
			$relationTypes = array('hasMany', 'hasOne', 'belongsTo', 'hasAndBelongsToMany');

			foreach ($relationTypes as $type)
			{
				if ($type == 'hasAndBelongsToMany') {
					if (!empty($Model->{$configurationModelName})) {
						$configurationModelAlias = $Model->{$configurationModelName}->alias;
						if (!empty($Model->{$type}[$configurationModelAlias])) {
							$linkModelName = $Model->{$type}[$configurationModelAlias]['with'];
						}
					}
				}
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
				$joinStatements = $this->buildFilterJoin($Model, $relatedModel, $linkModelName);
				foreach ($joinStatements as $joinStatement)
				{
					$query['joins'][] = $joinStatement;
				}
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
	 * @param string $linkModelName
	 * @return mixed[] Cake join array
	 */
	protected function buildFilterJoin(Model $Model, Model $relatedModel, $linkModelName)
	{
		$conditions = array();
		$relationTypes = array('hasMany', 'hasOne', 'belongsTo', 'hasAndBelongsToMany');

		$relatedModelAlias = null;
		$relationType = null;
		$linkModelAlias = null;

		foreach ($relationTypes as $type)
		{
			if (isset($Model->{$type}) && isset($Model->{$type}[$relatedModel->alias]))
			{
				if (!empty($linkModelName))
				{
					$linkModelAlias = $Model->{$linkModelName}->alias;
				}
				$relatedModelAlias = 'Filter'.$relatedModel->alias;
				$relationType = $type;
				break;
			}
		}
		$linkConditions = array();
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
			else if ($relationType == 'hasAndBelongsToMany')
			{
				$conditions[] = sprintf
				(
					'%s.%s = %s.%s',
					$Model->{$relationType}[$relatedModel->alias]['with'], $Model->{$relationType}[$relatedModel->alias]['associationForeignKey'],
					$relatedModelAlias, $relatedModel->primaryKey
				);

				$linkConditions[] = sprintf
				(
					'%s.%s = %s.%s',
					$Model->alias, $Model->primaryKey,
					$linkModelAlias, $Model->{$relationType}[$relatedModel->alias]['foreignKey']
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

		$return = array
			(
				array
				(
					'table' => $relatedModel->table,
					'alias' => $relatedModelAlias,
					'type' => 'LEFT',
					'conditions' => $conditions,
				)
			);

		if (!empty($linkModelName))
		{
			$return = array
				(
					array
					(
						'table' => $Model->{$linkModelName}->table,
						'alias' => $linkModelAlias,
						'type' => 'LEFT',
						'conditions' => $linkConditions,
					),
					array
					(
						'table' => $relatedModel->table,
						'alias' => $relatedModelAlias,
						'type' => 'LEFT',
						'conditions' => $conditions,
					)
				);
		}
		return $return;
	}

	/**
	 * Build query conditions and add them to $query.
	 *
	 * @param mixed[] $query Cake query array.
	 * @param string $field Filter field.
	 * @param mixed[] $options Configuration options for this field.
	 * @param mixed $value Field value.
	 * @return void
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
			case 'select':
				if (is_string($value) && strlen(trim(strval($value))) == 0)
				{
					break;
				}

				$query['conditions'][$field] = $value;
				break;
			case 'checkbox':
				$query['conditions'][$field] = $value;
				break;
			default:
				if (strlen(trim(strval($value))) == 0)
				{
					break;
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
		}
	}

	/**
	 * Makes a string SQL-safe.
	 *
	 * @param bool|string|null $string String to sanitize.
	 * @param string $connection Database connection being used.
	 * @return bool|string|null SQL safe string.
	 */
	private function __escape($string, $connection = 'default')
	{
		if (is_numeric($string) || $string === null || is_bool($string)) {
			return $string;
		}
		/** @var \DboSource $db */
		$db = ConnectionManager::getDataSource($connection);
		$string = $db->value($string, 'string');
		$start = 1;
		if ($string[0] === 'N') {
			$start = 2;
		}
		return substr(substr($string, $start), 0, -1);
	}

	/**
	 * Makes an array SQL-safe.
	 *
	 * @param string|mixed[] $data Data to sanitize.
	 * @param string $connection DB connection being used.
	 * @return (bool|string|null)|mixed[] Sanitized data.
	 */
	private function __clean($data, $connection = 'default')
	{
		if (empty($data)) {
			return $data;
		}
		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = $this->__clean($val, $connection);
			}
			return $data;
		}
		return $this->__escape($data, $connection);
	}

	/**
	 * Sets filter values.
	 *
	 * @param Model $Model  Current model.
	 * @param mixed[] $values Filter values.
	 * @return void
	 */
	public function setFilterValues($Model, $values = array())
	{
		$values = $this->__clean($values, $Model->useDbConfig);
		$this->_filterValues[$Model->alias] = array_merge($this->_filterValues[$Model->alias], (array)$values);
	}

	/**
	 * Gets filter values.
	 *
	 * @param Model $Model Current model.
	 * @return mixed[]
	 */
	public function getFilterValues($Model)
	{
		return $this->_filterValues;
	}

}
