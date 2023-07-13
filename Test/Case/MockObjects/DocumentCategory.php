<?php

class DocumentCategory extends CakeTestModel
{
	public $name = 'DocumentCategory';

	/**
	 * @var string[]
	 */
	public $hasMany = array('Document');

	/**
	 * @param mixed[] $options
	 * @return string[]
	 */
	public function customSelector($options = array())
	{
		$options['conditions']['DocumentCategory.title LIKE'] = '%T%';
		$options['nofilter'] = true;

		return $this->find('list', $options);
	}
}
