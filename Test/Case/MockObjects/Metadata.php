<?php

class Metadata extends CakeTestModel
{
	public $name = 'Metadata';

	/**
	 * @var (string|mixed[])[]
	 */
	public $hasOne = array('Document');
}
