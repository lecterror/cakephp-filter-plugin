<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

/**
 * @property \Filter\Test\TestCase\MockObjects\DocumentsTable $Documents
 */
class ItemsTable extends Table
{
	/**
	 * {@inheritDoc}
	 *
	 * @param mixed[] $config
	 * @see \Cake\ORM\Table::initialize()
	 */
	public function initialize(array $config)
	{
		$this->belongsTo('Documents');
	}
}
