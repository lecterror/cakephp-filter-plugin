<?php

/**
 * @property \DocumentCategory $DocumentCategory
 * @property \Item $Item
 * @method setFilterValues($Model, $values = array())
 */
class Document2 extends CakeTestModel
{
	public $name = 'Document';
	public $alias = 'Document';

	/**
	 * @var (string|mixed[])[]
	 */
	public $belongsTo = array('DocumentCategory');

	/**
	 * @var (string|mixed[])[]
	 */
	public $hasMany = array('Item');

	/**
	 * @var bool
	 */
	public $returnValue = false;

	/**
	 * @param mixed[] $query
	 * @param mixed[] $options
	 * @return mixed[]|bool
	 */
	public function beforeDataFilter($query, $options)
	{
		return $this->returnValue;
	}
}
