<?php

class Item extends CakeTestModel
{
	public $name = 'Item';

	/**
	 * @var string[]
	 */
	public $belongsTo = array('Document');
}
