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
     * @param string $modelName
     * @param mixed[] $options
     * @return string
     */
    public function filterForm($modelName, $options)
    {
        $view =& $this->_View;

        $output = $view->element
            (
                'filter_form_begin',
                array
                (
                    'plugin' => 'Filter',
                    'modelName' => $modelName,
                    'options' => $options
                ),
                array('plugin' => 'Filter')
            );

        $output .= $view->element
            (
                'filter_form_fields',
                array('plugin' => 'Filter'),
                array('plugin' => 'Filter')
            );

        $output .= $view->element
            (
                'filter_form_end',
                array('plugin' => 'Filter'),
                array('plugin' => 'Filter')
            );

        return $output;
    }

    /**
     * @param string $modelName
     * @param mixed[] $options
     * @return string
     */
    public function beginForm($modelName, $options)
    {
        $view =& $this->_View;
        $output = $view->element
            (
                'filter_form_begin',
                array
                (
                    'plugin' => 'Filter',
                    'modelName' => $modelName,
                    'options' => $options
                ),
                array('plugin' => 'Filter')
            );

        return $output;
    }

    /**
     * @param string[] $fields
     * @return string
     */
    public function inputFields($fields = array())
    {
        $view =& $this->_View;
        $output = $view->element
            (
                'filter_form_fields',
                array
                (
                    'plugin' => 'Filter',
                    'includeFields' => $fields
                ),
                array('plugin' => 'Filter')
            );

        return $output;
    }

    /**
     * @return string
     */
    public function endForm()
    {
        $view = $this->_View;
        $output = $view->element
            (
                'filter_form_end',
                array(),
                array('plugin' => 'Filter')
            );

        return $output;
    }
}
