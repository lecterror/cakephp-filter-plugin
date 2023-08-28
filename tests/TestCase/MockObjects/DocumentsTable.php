<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

/**
 * @property \Filter\Test\TestCase\MockObjects\DocumentCategoriesTable $DocumentCategories
 * @property \Filter\Test\TestCase\MockObjects\ItemsTable $Items
 * @property \Filter\Test\TestCase\MockObjects\MetadataTable $Metadata
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class DocumentsTable extends Table
{
	/**
	 * {@inheritDoc}
	 *
	 * @see \Cake\ORM\Table::initialize()
	 */
	public function initialize(array $config)
	{
		$this->belongsTo('DocumentCategories');
		$this->hasMany('Items');
		$this->hasOne('Metadata');
	}
}
