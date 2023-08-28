<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

/**
 * @property \Filter\Test\TestCase\MockObjects\DocumentCategoriesTable $DocumentCategories
 * @property \Filter\Test\TestCase\MockObjects\ItemsTable $Items
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class Documents3Table extends Table
{
	/**
	 * {@inheritDoc}
	 *
	 * @see \Cake\ORM\Table::initialize()
	 */
	public function initialize(array $config)
	{
		$this->setAlias('Document');
		$this->setTable('documents');
		$this->belongsTo('DocumentCategories');
		$this->hasMany('Items');
	}

	/**
	 * @var string|null
	 */
	public $itemToUnset = null;

	/**
	 * @param \Cake\ORM\Query $query Query.
	 * @param mixed[] $options
	 * @return mixed[]
	 */
	public function afterDataFilter($query, $options)
	{
		if (!is_string($this->itemToUnset))
		{
			return $query;
		}
		$query->clause('where')->iterateParts(function ($Comparison) {
			/** @var \Cake\Database\Expression\Comparison $Comparison */
			$field = $Comparison->getField();
			if ($field == $this->itemToUnset) {
				return null;
			}
			return $Comparison;
		});
		return $query;
	}
}
