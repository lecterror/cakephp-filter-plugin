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
