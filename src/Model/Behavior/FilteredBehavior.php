<?php

namespace Filter\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Association;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\Table;

/**
	CakePHP Filter Plugin

	Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
	<http://lecterror.com/>

	Multi-licensed under:
		MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
		LGPL <http://www.gnu.org/licenses/lgpl.html>
		GPL <http://www.gnu.org/licenses/gpl.html>
*/

class FilteredBehavior extends Behavior
{
	/**
	 * Keeps current values after filter form post.
	 *
	 * @var mixed[]
	 */
	protected $_filterValues = array();

	/**
	 * 2.x compartible settings (supports having dots in the keys, f.ex. 'Model.id').
	 *
	 * @var mixed[]
	 */
	public $settings = [];

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed[] $settings The configuration settings provided to this behavior.
	 * @return void
	 */
	public function initialize(array $settings)
	{
		foreach ($settings as $key => $value)
		{
			if (!is_array($value))
			{
				$key = $value;
				$value = array();
			}

			$this->settings[$this->getTable()->getAlias()][$key] = array_merge
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

		$this->_filterValues[$this->getTable()->getAlias()] = array();
	}

	/**
	 * {@inheritDoc}
	 *
	 * Callback method that listens to the `beforeFind` event in the bound
	 * table. It modifies the passed query by applying search filters.
	 *
	 * @param \Cake\Event\Event $event            The beforeFind event that was fired.
	 * @param \Cake\ORM\Query $Query              Query.
	 * @param \ArrayObject<string,mixed> $options The options for the query.
	 * @return void
	 */
	public function beforeFind(Event $event, Query $Query, ArrayObject $options)
	{
		if (isset($Query->getOptions()['nofilter']) && $Query->getOptions()['nofilter'] === true)
		{
			return;
		}
		$Table = $this->getTable();
		$alias = $Table->getAlias();
		if (method_exists($Table, 'beforeDataFilter'))
		{
			$callbackOptions['values'] = $this->_filterValues[$alias];
			$callbackOptions['settings'] = $this->settings[$alias];

			if (!$Table->beforeDataFilter($Query, $callbackOptions))
			{
				return;
			}
		}

		if (!isset($this->settings[$alias]))
		{
			return;
		}

		$settings = $this->settings[$alias];
		$values = $this->_filterValues[$alias];

		foreach ($settings as $field => $options)
		{
			$this->addFieldToFilter($Table, $Query, $values, $field, $options);
		}

		if (method_exists($Table, 'afterDataFilter'))
		{
			$callbackOptions['values'] = $this->_filterValues[$alias];
			$callbackOptions['settings'] = $this->settings[$alias];

			$Table->afterDataFilter($Query, $callbackOptions);
		}
	}

	/**
	 * Adds field filters.
	 *
	 * @param \Cake\ORM\Table $Table Model table object.
	 * @param \Cake\ORM\Query $Query Query object.
	 * @param mixed[] $values        Filter values.
	 * @param string $field          Field name.
	 * @param mixed[] $fieldOptions  Field options.
	 * @return void
	 */
	protected function addFieldToFilter(Table $Table, Query $Query, $values, $field, $fieldOptions)
	{
		$configurationModelName = $Table->getAlias();
		$configurationFieldName = $field;

		if (strpos($field, '.') !== false)
		{
			list($configurationModelName, $configurationFieldName) = explode('.', $field);
		}

		if (!isset($values[$configurationModelName][$configurationFieldName]) && isset($fieldOptions['default']))
		{
			$values[$configurationModelName][$configurationFieldName] = $fieldOptions['default'];
		}

		if ($fieldOptions['required'] && !isset($values[$configurationModelName][$configurationFieldName]))
		{
			// TODO: implement a bit of a user friendly handling of this scenario..
			trigger_error(sprintf('No value present for required field "%s" and default value not present', $field));
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

		if ($configurationModelName != $Table->getAlias())
		{
			if ($Table->hasAssociation($configurationModelName)) {
				$relationType = $Table->getAssociation($configurationModelName)->type();
				if ($relationType == Association::MANY_TO_MANY) {
					$linkModelName = $Table->{$configurationModelName}->junction()->getAlias();
				}
				$filterModelName = 'Filter'.$configurationModelName;
			}
		}

		if (isset($fieldOptions['filterField']))
		{
			if (strpos($fieldOptions['filterField'], '.') !== false)
			{
				list($filterModelName, $filterFieldName) = explode('.', $fieldOptions['filterField']);

				if ($filterModelName != $Table->getAlias())
				{
					$filterModelName = 'Filter'.$filterModelName;
				}
			}
			else
			{
				$filterModelName = $Table->getAlias();
				$filterFieldName = $fieldOptions['filterField'];
			}
		}

		$realFilterField = sprintf('%s.%s', $filterModelName, $filterFieldName);
		if ($Table->hasAssociation($configurationModelName))
		{
			$relatedModel = $Table->{$configurationModelName}->getTarget();
			if (!$this->__isAlreadyJoined($Query, $relatedModel))
			{
				$joinStatements = $this->buildFilterJoin($Table, $relatedModel, $linkModelName);
				foreach ($joinStatements as $joinStatement)
				{
					$Query->join($joinStatement);
				}
			}
		}

		$this->buildFilterConditions
			(
				$Query,
				$realFilterField,
				$fieldOptions,
				$values[$configurationModelName][$configurationFieldName]
			);
	}

	/**
	 * Checks whether the given query object already contains a given table join.
	 *
	 * @param \Cake\ORM\Query $Query Query object.
	 * @param \Cake\ORM\Table $Table Related model.
	 * @return boolean
	 */
	private function __isAlreadyJoined(Query $Query, Table $Table)
	{
		$relatedModelAlias = 'Filter' . $Table->getAlias();
		$containedAliases = array_keys($Query->getContain());
		$joinAliases = $this->__extractJoinAliases($Query);
		return in_array($relatedModelAlias, $containedAliases) || in_array($relatedModelAlias, $joinAliases);
	}

	/**
	 * Extract the JOIN clause aliases from the given query object.
	 *
	 * @param \Cake\ORM\Query $Query Query object.
	 * @return string[]
	 */
	private function __extractJoinAliases(Query $Query)
	{
		$aliases = [];
		$joins = $Query->clause('join');
		foreach ($joins as $join) {
			if (array_key_exists('alias', $join)) {
				$aliases[] = $join['alias'];
			}
		}
		return $aliases;
	}

	/**
	 * Build join conditions from Model to relatedModel.
	 *
	 * @param \Cake\ORM\Table $Table        Model table object.
	 * @param \Cake\ORM\Table $RelatedTable Related model table object.
	 * @param string $linkModelName         Linked model name (alias) in MANY_TO_MANY association.
	 * @return mixed[]                      Cake join array.
	 */
	protected function buildFilterJoin(Table $Table, Table $RelatedTable, $linkModelName)
	{
		$conditions = array();
		$alias = $Table->getAlias();
		$primaryKey = $Table->getPrimaryKey();
		$relatedTableAlias = $RelatedTable->getAlias();
		$relatedModelAlias = null;
		$relationType = null;
		$association = null;
		$foreignKey = null;
		$associationPrimaryKey = null;
		$associationConditions = null;
		if (!$Table->hasAssociation($relatedTableAlias)) {
			return [];
		}
		$relatedModelAlias = 'Filter'.$relatedTableAlias;
		$association = $Table->getAssociation($relatedTableAlias);
		$linkModelAlias = null;
		if (!empty($linkModelName) && ($association instanceof BelongsToMany))
		{
			$linkModelAlias = $association->junction()->getAlias();
		}
		$relationType = $association->type();
		$foreignKey = $association->getForeignKey();
		$associationConditions = $association->getConditions();
		$associationPrimaryKey = $RelatedTable->getPrimaryKey();
		$linkConditions = [];
		if (!empty($foreignKey) && is_string($foreignKey))
		{
			if ($relationType == Association::MANY_TO_ONE && is_string($associationPrimaryKey))
			{
				$conditions[] = sprintf
					(
						'%s.%s = %s.%s',
						$alias, $foreignKey,
						$relatedModelAlias, $associationPrimaryKey
					);
			}
			else if (
				in_array($relationType, [Association::ONE_TO_MANY, Association::ONE_TO_ONE]) &&
				is_string($primaryKey)
			) {
				$conditions[] = sprintf
					(
						'%s.%s = %s.%s',
						$alias, $primaryKey,
						$relatedModelAlias, $foreignKey
					);
			}
			else if (
				$relationType == Association::MANY_TO_MANY &&
				is_string($primaryKey) &&
				is_string($associationPrimaryKey)
			) {
				$associationForeignKey = $RelatedTable->getAssociation($alias)->getForeignKey();
				if (is_string($associationForeignKey)) {
					$conditions[] = sprintf
					(
						'%s.%s = %s.%s',
						$linkModelAlias, $associationForeignKey,
						$relatedModelAlias, $associationPrimaryKey
					);
				}

				$linkConditions[] = sprintf
				(
					'%s.%s = %s.%s',
					$alias, $primaryKey,
					$linkModelAlias, $foreignKey
				);
			}
		}

		// merge any custom conditions from the relation, but change
		// the alias to our $relatedModelAlias
		if (!empty($associationConditions))
		{
			$customConditions = $associationConditions;

			if (!is_array($associationConditions))
			{
				$customConditions = array($customConditions);
			}
			$formatAlias = sprintf('#(?<![A-Za-z])%s(?![A-Za-z])#', $relatedTableAlias);
			if (is_string($relatedModelAlias) && is_array($customConditions)) {
				$filterConditions = preg_replace($formatAlias, $relatedModelAlias, $customConditions);
				if (is_array($filterConditions)) {
					$conditions = array_merge($conditions, $filterConditions);
				}
			}
		}

		$return = array
			(
				array
				(
					'table' => $RelatedTable->getTable(),
					'alias' => $relatedModelAlias,
					'type' => 'LEFT',
					'conditions' => $conditions,
				)
			);

		if (!empty($linkModelName) && ($association instanceof BelongsToMany))
		{
			$return = array
				(
					array
					(
						'table' => $association->junction()->getTable(),
						'alias' => $linkModelAlias,
						'type' => 'LEFT',
						'conditions' => $linkConditions,
					),
					array
					(
						'table' => $RelatedTable->getTable(),
						'alias' => $relatedModelAlias,
						'type' => 'LEFT',
						'conditions' => $conditions,
					)
				);
		}
		return $return;
	}

	/**
	 * Build query conditions and add them to $Query.
	 *
	 * @param \Cake\ORM\Query $Query Cake query array.
	 * @param string $field          Filter field.
	 * @param mixed[] $options       Configuration options for this field.
	 * @param mixed $value           Field value.
	 * @return void
	 */
	protected function buildFilterConditions(Query $Query, $field, $options, $value)
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
				if (is_array($value)) {
					$Query->andWhere([$field . ' IN' => $value]);
				} else {
					$Query->andWhere([$field => $value]);
				}
				break;
			case 'checkbox':
				if (is_array($value)) {
					$Query->andWhere([$field . ' IN' => $value]);
				} else {
					$Query->andWhere([$field => $value]);
				}
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
						$Query->andWhere([$formattedField => $formattedValue]);
						break;
					default:
						{
							$Query->andWhere([$field.' '.$condition => $value]);
						}
						break;
				}

				break;
		}
	}

	/**
	 * Sets filter values.
	 *
	 * @param mixed[] $values Filter values.
	 * @return void
	 */
	public function setFilterValues($values = array())
	{
		$alias = $this->getTable()->getAlias();
		$this->_filterValues[$alias] = array_merge($this->_filterValues[$alias], (array)$values);
	}

	/**
	 * Gets filter values.
	 *
	 * @return mixed[]
	 */
	public function getFilterValues()
	{
		return $this->_filterValues;
	}

}
