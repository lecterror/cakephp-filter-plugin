<?php

/**
 * @property \Document $Document
 * @method getFilterValues()
 * @method setFilterValues($values = array())
 */
class DocumentCategory extends CakeTestModel
{
	public $name = 'DocumentCategory';

	/**
	 * @var mixed[]
	 */
	public $hasMany = array('Document');

	/**
	 * @param mixed[] $options
	 * @return mixed[]|int|null
	 */
	public function customSelector($options = array())
	{
		$options['conditions']['DocumentCategory.title LIKE'] = '%T%';
		$options['nofilter'] = true;

		return $this->find('list', $options);
	}
}
