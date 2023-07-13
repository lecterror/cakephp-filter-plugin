<?php

/**
 * @method setFilterValues($Model, $values)
 */
class Document3 extends CakeTestModel
{
	public $name = 'Document';
	public $alias = 'Document';
	public $belongsTo = array('DocumentCategory');
	public $hasMany = array('Item');

	public $itemToUnset = null;

	public function afterDataFilter($query, $options)
	{
		if (!is_string($this->itemToUnset))
		{
			return $query;
		}

		if (isset($query['conditions'][$this->itemToUnset]))
		{
			unset($query['conditions'][$this->itemToUnset]);
		}

		return $query;
	}
}
