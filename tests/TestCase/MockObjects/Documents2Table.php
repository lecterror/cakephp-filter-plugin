<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

/**
 * @property \Filter\Test\TestCase\MockObjects\DocumentCategoriesTable $DocumentCategories
 * @property \Filter\Test\TestCase\MockObjects\ItemsTable $Items
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class Documents2Table extends Table
{
    /**
     * {@inheritDoc}
     *
     * @param mixed[] $config
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
     * @var bool
     */
    public $returnValue = false;

    /**
     * @param \Cake\ORM\Query $query Query.
     * @param mixed[] $options
     * @return mixed[]|bool
     */
    public function beforeDataFilter($query, $options)
    {
        return $this->returnValue;
    }
}
