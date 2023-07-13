<?php

class Metadata extends CakeTestModel
{
	public $name = 'Metadata';

	/**
	 * @var string[]
	 */
	public $hasOne = array('Document');
}
