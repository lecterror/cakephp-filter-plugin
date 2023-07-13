<?php

/**
 * @method setFilterValues($Model, $values)
 */
class Document2 extends CakeTestModel
{
	public $name = 'Document';
	public $alias = 'Document';
	public $belongsTo = array('DocumentCategory');
	public $hasMany = array('Item');

	public $returnValue = false;

	public function beforeDataFilter($query, $options)
	{
		return $this->returnValue;
	}
}
