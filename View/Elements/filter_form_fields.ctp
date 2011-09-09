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

if (isset($viewFilterParams))
{
	foreach ($viewFilterParams as $field)
	{
		if(empty($includeFields))
		{
			echo $this->Form->input($field['name'], $field['options']);
		}
		else
		{
			if (in_array($field['name'], $includeFields))
			{
				echo $this->Form->input($field['name'], $field['options']);
			}
		}
	}
}
