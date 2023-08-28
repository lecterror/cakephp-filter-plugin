<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

class MetadataTable extends Table
{
	/**
	 * {@inheritDoc}
	 *
	 * @see \Cake\ORM\Table::initialize()
	 */
	public function initialize(array $config)
	{
		$this->belongsTo('Documents');
	}
}
