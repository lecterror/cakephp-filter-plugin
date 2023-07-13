<?php

/**
 * @property \Document $Document
 */
class Item extends CakeTestModel
{
	public $name = 'Item';

	/**
	 * @var mixed[]
	 */
	public $belongsTo = array('Document');
}
