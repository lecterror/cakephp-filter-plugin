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
?>
<div class="filterForm">
	<?php echo $this->Form->create(false, array('url' => '/'.$this->request->url, 'id' => $modelName.'Filter')); ?>
		<fieldset>
			<?php
			if (isset($options['legend']))
			{
				?><legend><?php echo $options['legend']; ?></legend><?php
			}
			?>
			<?php echo $this->Form->input('Filter.filterFormId', array('type' => 'hidden', 'value' => $modelName)); ?>
