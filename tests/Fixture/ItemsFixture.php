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

class ItemsFixture extends TestFixture
{
    /**
     * @var mixed[]
     */
    public $fields =
        [
            'id' => ['type' => 'integer'],
            'document_id' => ['type' => 'integer', 'null' => false],
            'code' => ['type' => 'string', 'length' => '20', 'null' => false],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];

    /**
     * @var (int|string)[][]
     */
    public $records =
        [
            ['id' => 1, 'document_id' => 1, 'code' => 'The item #01'],
            ['id' => 2, 'document_id' => 1, 'code' => 'The item #02'],
            ['id' => 3, 'document_id' => 1, 'code' => 'The item #03'],
            ['id' => 4, 'document_id' => 2, 'code' => 'The item #01'],
            ['id' => 5, 'document_id' => 2, 'code' => 'The item #02'],
            ['id' => 6, 'document_id' => 2, 'code' => 'The item #03'],
            ['id' => 7, 'document_id' => 2, 'code' => 'The item #04'],
            ['id' => 8, 'document_id' => 3, 'code' => 'The item #01'],
            ['id' => 9, 'document_id' => 4, 'code' => 'The item #01'],
            ['id' => 10, 'document_id' => 5, 'code' => 'The item #01'],
        ];
}
