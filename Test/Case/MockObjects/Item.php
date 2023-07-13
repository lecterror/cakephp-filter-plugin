<?php

/**
 * @property \Document $Document
 */
class Item extends CakeTestModel
{
	public $name = 'Item';

	/**
	 * @var (string|mixed[])[]
	 */
	public $belongsTo = array('Document');
}
