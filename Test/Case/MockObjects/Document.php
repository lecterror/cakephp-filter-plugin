<?php

class Document extends CakeTestModel
{
	public $name = 'Document';

	/**
	 * @var string[]
	 */
	public $belongsTo = array('DocumentCategory');

	/**
	 * @var string[]
	 */
	public $hasMany = array('Item');

	/**
	 * @var string[]
	 */
	public $hasOne = array('Metadata');
}
