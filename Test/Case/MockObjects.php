<?php
/**
	CakePHP Filter Plugin

	Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
	<http://lecterror.com/>

	Multi-licensed under:
		MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
		LGPL <http://www.gnu.org/licenses/lgpl.html>
		GPL <http://www.gnu.org/licenses/gpl.html>
*/

App::uses('Behavior', 'Filter.Filtered');
App::uses('Controller', 'Controller');

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

class Document extends CakeTestModel
{
	public $name = 'Document';
	public $belongsTo = array('DocumentCategory');
	public $hasMany = array('Item');
	public $hasOne = array('Metadata');
}

class Document2 extends CakeTestModel
{
	public $name = 'Document';
	public $alias = 'Document';
	public $belongsTo = array('DocumentCategory');
	public $hasMany = array('Item');

	public $returnValue = false;

	public function beforeDataFilter($query, $options)
	{
		return $this->returnValue;
	}
}

class Document3 extends CakeTestModel
{
	public $name = 'Document';
	public $alias = 'Document';
	public $belongsTo = array('DocumentCategory');
	public $hasMany = array('Item');

	public $itemToUnset = null;

	public function afterDataFilter($query, $options)
	{
		if (!is_string($this->itemToUnset))
		{
			return $query;
		}

		if (isset($query['conditions'][$this->itemToUnset]))
		{
			unset($query['conditions'][$this->itemToUnset]);
		}

		return $query;
	}
}

class Metadata extends CakeTestModel
{
	public $name = 'Metadata';
	public $hasOne = array('Document');
}

class Item extends CakeTestModel
{
	public $name = 'Item';
	public $belongsTo = array('Document');
}

class DocumentTestsController extends Controller
{
	public $name = 'DocumentTests';

	public function index()
	{
	}

	// must override this or the tests never complete..
	// @TODO: mock partial?
	public function redirect($url, $status = NULL, $exit = true)
	{
	}
}
