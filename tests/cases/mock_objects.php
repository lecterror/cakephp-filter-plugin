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

App::import('Behavior', 'Filter.Filtered');

class DocumentCategory extends CakeTestModel
{
	var $name = 'DocumentCategory';
	var $hasMany = array('Document');
}

class Document extends CakeTestModel
{
	var $name = 'Document';
	var $belongsTo = array('DocumentCategory');
	var $hasMany = array('Item');
	var $hasOne = array('Metadata');
}

class Document2 extends CakeTestModel
{
	var $name = 'Document';
	var $alias = 'Document';
	var $belongsTo = array('DocumentCategory');
	var $hasMany = array('Item');

	var $returnValue = false;

	function beforeDataFilter($query, $options)
	{
		return $this->returnValue;
	}
}

class Document3 extends CakeTestModel
{
	var $name = 'Document';
	var $alias = 'Document';
	var $belongsTo = array('DocumentCategory');
	var $hasMany = array('Item');

	var $itemToUnset = null;

	function afterDataFilter($query, $options)
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
	var $name = 'Metadata';
	var $hasOne = array('Document');
}

class Item extends CakeTestModel
{
	var $name = 'Item';
	var $belongsTo = array('Document');
}

class DocumentTestsController extends Controller
{
	var $name = 'DocumentTests';

	function index()
	{
	}
}
