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

class DocumentFixture extends CakeTestFixture
{
	var $name = 'Document';

	var $fields = array
		(
			'id'					=> array('type' => 'integer', 'key' => 'primary'),
			'title'					=> array('type' => 'string', 'length' => '255', 'null' => false),
			'document_category_id'	=> array('type' => 'integer', 'null' => false),
			'owner_id'				=> array('type' => 'integer', 'null' => false),
			'is_private'			=> array('type' => 'integer', 'length' => 1, 'null' => false),
			'created'				=> array('type' => 'datetime', 'null' => false),
			'updated'				=> array('type' => 'datetime', 'null' => true)
		);

	var $records = array
		(
			array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-06-28 10:39:23', 'updated' => '2010-06-29 11:22:48'),
			array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
			array('id' => 3, 'title' => 'Nonexistant data', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-04-28 11:12:33', 'updated' => '2010-05-05 15:03:24'),
			array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1, 'created' => '2010-01-08 05:15:03', 'updated' => '2010-05-22 03:15:24'),
			array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'),
			array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'),
			array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'),
		);
}
