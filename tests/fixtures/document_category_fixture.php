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

class DocumentCategoryFixture extends CakeTestFixture
{
	var $name = 'DocumentCategory';

	var $fields = array
		(
			'id'			=> array('type' => 'integer', 'key' => 'primary'),
			'title'			=> array('type' => 'string', 'length' => 100, 'null' => false),
			'description'	=> array('type' => 'string', 'length' => 255)
		);

	var $records = array
		(
			array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
			array('id' => 2, 'title' => 'Imaginary Spec', 'description' => 'This doc does not exist'),
			array('id' => 3, 'title' => 'Nonexistant data', 'description' => 'This doc is probably empty'),
			array('id' => 4, 'title' => 'Illegal explosives DIY', 'description' => 'Viva la revolucion!'),
			array('id' => 5, 'title' => 'Father Ted', 'description' => 'Feck! Drink! Arse! Girls!')
		);
}

