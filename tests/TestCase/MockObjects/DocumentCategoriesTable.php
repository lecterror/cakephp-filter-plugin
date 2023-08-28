<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\ORM\Table;

/**
 * @property \Filter\Test\TestCase\MockObjects\DocumentsTable $Documents
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class DocumentCategoriesTable extends Table
{
    /**
     * {@inheritDoc}
     *
     * @param mixed[] $config
     * @see \Cake\ORM\Table::initialize()
     */
    public function initialize(array $config)
    {
        $this->hasMany('Documents');
    }

    /**
     * @param mixed[] $options
     * @return mixed[]|int|null
     */
    public function customSelector($options = array())
    {
        $options['nofilter'] = true;
        return $this->find('list', $options)
            ->where([
                'DocumentCategory.title LIKE' => '%T%',
            ])
            ->toArray();
    }
}
