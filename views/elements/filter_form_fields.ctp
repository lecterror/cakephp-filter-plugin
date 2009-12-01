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

if (isset($viewFilterParams))
{
	foreach ($viewFilterParams as $field)
	{
		if(empty($includeFields))
		{
			echo $form->input($field['name'], $field['options']);
		}
		else
		{
			if (in_array($field['name'], $includeFields))
			{
				echo $form->input($field['name'], $field['options']);
			}
		}
	}
}

