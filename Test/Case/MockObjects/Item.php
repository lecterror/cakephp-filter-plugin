<?php

class Item extends CakeTestModel
{
	public $name = 'Item';
	public $belongsTo = array('Document');
}
