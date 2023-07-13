<?php

/**
 * @property \DocumentCategory $DocumentCategory
 * @property \Item $Item
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class Document3 extends CakeTestModel
{
	public $name = 'Document';
	public $alias = 'Document';

	/**
	 * @var mixed[]
	 */
	public $belongsTo = array('DocumentCategory');

	/**
	 * @var mixed[]
	 */
	public $hasMany = array('Item');

	/**
	 * @var string|null
	 */
	public $itemToUnset = null;

	/**
	 * @param mixed[] $query
	 * @param mixed[] $options
	 * @return mixed[]
	 */
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
