<?php
/**
    CakePHP Filter Plugin

    Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
    <http://lecterror.com/>

    Multi-licensed under:
        MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
        LGPL <http://www.gnu.org/licenses/lgpl.html>
        GPL <http://www.gnu.org/licenses/gpl.html>

 @var \Cake\View\View $this
 */

if (isset($viewFilterParams)) {
    foreach ($viewFilterParams as $field) {
        if (empty($includeFields) || in_array($field['name'], $includeFields)) {
            $fieldName = explode('.', $field['name']);
            if (count($fieldName) === 2) {
                $field['options']['name'] = sprintf('data[%s][%s]', $fieldName[0], $fieldName[1]);
            }
            if (!isset($field['required'])) {
                $field['required'] = false;
            }
            echo $this->Form->control($field['name'], $field['options']);
        }
    }
}
