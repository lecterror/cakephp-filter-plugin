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

class ItemFixture extends CakeTestFixture
{
	var $name = 'Item';

	var $fields = array
		(
			'id'					=> array('type' => 'integer', 'key' => 'primary'),
			'document_id'			=> array('type' => 'integer', 'null' => false),
			'code'					=> array('type' => 'string', 'length' => '20', 'null' => false)
		);

	var $records = array
		(
			array('id' => 1, 'document_id' => 1, 'code' => 'The item #01'),
			array('id' => 2, 'document_id' => 1, 'code' => 'The item #02'),
			array('id' => 3, 'document_id' => 1, 'code' => 'The item #03'),
			array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
			array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
			array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
			array('id' => 7, 'document_id' => 2, 'code' => 'The item #04'),
			array('id' => 8, 'document_id' => 3, 'code' => 'The item #01'),
			array('id' => 9, 'document_id' => 4, 'code' => 'The item #01'),
			array('id' => 10, 'document_id' => 5, 'code' => 'The item #01')
		);
}
