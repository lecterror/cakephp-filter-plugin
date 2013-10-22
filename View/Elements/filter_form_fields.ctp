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
			$fieldName = $field['name'];
			$fieldOptions = $field['options'];
			
			if($field['options']['type'] === "date"){
				$selectedEndDate = $fieldOptions['selected_end_date'];
			}
			if( !isset($fieldOptions['required'])){
				$fieldOptions['required'] = false;
			}
			unset($fieldOptions['selected_end_date']);
			$myFormField = $this->Form->input($fieldName, $fieldOptions);
			echo $myFormField;
			
			if($field['options']['type'] === "date"){
				$endDateFieldOptions = $fieldOptions;
				$endDateFieldOptions['label'] = str_replace('From', 'To', $endDateFieldOptions['label']);
				$endDateFieldOptions['selected'] = $selectedEndDate;
				$myEndDateFormField = $this->Form->input($fieldName . "_end_date", $endDateFieldOptions);
				echo $myEndDateFormField;
			}
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
