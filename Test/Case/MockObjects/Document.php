<?php

/**
 * @property \DocumentCategory $DocumentCategory
 * @property \Item $Item
 * @property \Metadata $Metadata
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class Document extends CakeTestModel
{
	public $name = 'Document';

	/**
	 * @var mixed[]
	 */
	public $belongsTo = array('DocumentCategory');

	/**
	 * @var mixed[]
	 */
	public $hasMany = array('Item');

	/**
	 * @var mixed[]
	 */
	public $hasOne = array('Metadata');
}
