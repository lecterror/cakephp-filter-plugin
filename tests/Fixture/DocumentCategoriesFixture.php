<?php

namespace Filter\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
    CakePHP Filter Plugin

    Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
    <http://lecterror.com/>

    Multi-licensed under:
        MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
        LGPL <http://www.gnu.org/licenses/lgpl.html>
        GPL <http://www.gnu.org/licenses/gpl.html>
*/

class DocumentCategoriesFixture extends TestFixture
{
    /**
     * @var mixed[]
     */
    public $fields = array
        (
            'id'            => array('type' => 'integer'),
            'title'            => array('type' => 'string', 'length' => 100, 'null' => false),
            'description'    => array('type' => 'string', 'length' => 255),
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        );

    /**
     * @var (int|string)[][]
     */
    public $records = array
        (
            array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
            array('id' => 2, 'title' => 'Imaginary Spec', 'description' => 'This doc does not exist'),
            array('id' => 3, 'title' => 'Nonexistant data', 'description' => 'This doc is probably empty'),
            array('id' => 4, 'title' => 'Illegal explosives DIY', 'description' => 'Viva la revolucion!'),
            array('id' => 5, 'title' => 'Father Ted', 'description' => 'Feck! Drink! Arse! Girls!')
        );
}
