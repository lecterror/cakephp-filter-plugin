<?php
/**
    CakePHP Filter Plugin

    Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
    <http://lecterror.com/>

    Multi-licensed under:
        MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
        LGPL <http://www.gnu.org/licenses/lgpl.html>
        GPL <http://www.gnu.org/licenses/gpl.html>

 @var array<mixed> $options
 @var string $modelName
 @var \Cake\View\View $this
 */

?>
<div class="filterForm">
    <?php echo $this->Form->create(
        false,
        [
            'url' => [
                'plugin' => $this->getRequest()->getParam('plugin'),
                'controller' => $this->getRequest()->getParam('controller'),
                'action' => $this->getRequest()->getParam('action'),
            ],
            'id' => $modelName . 'Filter',
        ] + $options
    ); ?>
        <fieldset>
            <?php
            if (isset($options['legend'])) {
                ?><legend><?php echo $options['legend']; ?></legend><?php
            }
            ?>
            <?php echo $this->Form->control('Filter.filterFormId', ['type' => 'hidden', 'value' => $modelName]); ?>
