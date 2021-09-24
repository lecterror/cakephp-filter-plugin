<?php

class Document extends CakeTestModel
{
	public $name = 'Document';
	public $belongsTo = array('DocumentCategory');
	public $hasMany = array('Item');
	public $hasOne = array('Metadata');
}
