<?php

class DocumentCategory extends CakeTestModel
{
	public $name = 'DocumentCategory';
	public $hasMany = array('Document');

	public function customSelector($options = array())
	{
		$options['conditions']['DocumentCategory.title LIKE'] = '%T%';
		$options['nofilter'] = true;

		return $this->find('list', $options);
	}
}
