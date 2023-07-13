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
	 * @var (string|mixed[])[]
	 */
	public $belongsTo = array('DocumentCategory');

	/**
	 * @var (string|mixed[])[]
	 */
	public $hasMany = array('Item');

	/**
	 * @var (string|mixed[])[]
	 */
	public $hasOne = array('Metadata');
}
