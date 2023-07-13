<?php

class Metadata extends CakeTestModel
{
	public $name = 'Metadata';

	/**
	 * @var mixed[]
	 */
	public $hasOne = array('Document');
}
