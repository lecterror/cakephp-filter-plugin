<?php

namespace Filter\View\Helper;

use Cake\View\Helper;

/**
    CakePHP Filter Plugin

    Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
    <http://lecterror.com/>

    Multi-licensed under:
        MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
        LGPL <http://www.gnu.org/licenses/lgpl.html>
        GPL <http://www.gnu.org/licenses/gpl.html>
 */

class FilterHelper extends Helper
{
    /**
     * @param string $modelName Model name.
     * @param array<mixed> $options Options.
     * @return string
     */
    public function filterForm($modelName, $options)
    {
        $view =& $this->_View;

        $output = $view->element(
            'filter_form_begin',
            [
                    'plugin' => 'Filter',
                    'modelName' => $modelName,
                    'options' => $options,
                ],
            ['plugin' => 'Filter']
        );

        $output .= $view->element(
            'filter_form_fields',
            ['plugin' => 'Filter'],
            ['plugin' => 'Filter']
        );

        $output .= $view->element(
            'filter_form_end',
            ['plugin' => 'Filter'],
            ['plugin' => 'Filter']
        );

        return $output;
    }

    /**
     * @param string $modelName Model name.
     * @param array<mixed> $options Options.
     * @return string
     */
    public function beginForm($modelName, $options)
    {
        $view =& $this->_View;
        $output = $view->element(
            'filter_form_begin',
            [
                    'plugin' => 'Filter',
                    'modelName' => $modelName,
                    'options' => $options,
                ],
            ['plugin' => 'Filter']
        );

        return $output;
    }

    /**
     * @param array<string> $fields Fileds to include.
     * @return string
     */
    public function inputFields($fields = [])
    {
        $view =& $this->_View;
        $output = $view->element(
            'filter_form_fields',
            [
                    'plugin' => 'Filter',
                    'includeFields' => $fields,
                ],
            ['plugin' => 'Filter']
        );

        return $output;
    }

    /**
     * @return string
     */
    public function endForm()
    {
        $view = $this->_View;
        $output = $view->element(
            'filter_form_end',
            [],
            ['plugin' => 'Filter']
        );

        return $output;
    }
}
