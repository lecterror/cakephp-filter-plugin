<?php
/*
This file is part of CakePHP Filter Plugin.

CakePHP Filter Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

CakePHP Filter Plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CakePHP Filter Plugin. If not, see <http://www.gnu.org/licenses/>.
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
