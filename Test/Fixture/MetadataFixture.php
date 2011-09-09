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

class MetadataFixture extends CakeTestFixture
{
	var $name = 'Metadata';

	var $fields = array
		(
			'id'					=> array('type' => 'integer', 'key' => 'primary'),
			'document_id'			=> array('type' => 'integer', 'null' => false),
			'weight'				=> array('type' => 'integer', 'null' => false),
			'size'					=> array('type' => 'integer', 'null' => false),
			'permissions'			=> array('type' => 'string', 'length' => 10, 'null' => false),
		);

	var $records = array
		(
			array('id' => 1, 'document_id' => 1, 'weight' => 5, 'size' => 256, 'permissions' => 'rw-r--r--'),
			array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
			array('id' => 3, 'document_id' => 3, 'weight' => 2, 'size' => 78, 'permissions' => 'rw-rw-r--'),
			array('id' => 4, 'document_id' => 4, 'weight' => 1, 'size' => 412, 'permissions' => 'rw-r--r--'),
			array('id' => 5, 'document_id' => 5, 'weight' => 4, 'size' => 790, 'permissions' => 'rw-rw-r--'),
		);
}
