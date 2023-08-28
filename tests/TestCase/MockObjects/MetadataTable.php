<?php
declare(strict_types=1);

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

class MetadataTable extends Table
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
