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

class MetadataFixture extends TestFixture
{
    /**
     * @var mixed[]
     */
    public $fields =
        [
            'id' => ['type' => 'integer'],
            'document_id' => ['type' => 'integer', 'null' => false],
            'weight' => ['type' => 'integer', 'null' => false],
            'size' => ['type' => 'integer', 'null' => false],
            'permissions' => ['type' => 'string', 'length' => 10, 'null' => false],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];

    /**
     * @var (int|string)[][]
     */
    public $records =
        [
            ['id' => 1, 'document_id' => 1, 'weight' => 5, 'size' => 256, 'permissions' => 'rw-r--r--'],
            ['id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'],
            ['id' => 3, 'document_id' => 3, 'weight' => 2, 'size' => 78, 'permissions' => 'rw-rw-r--'],
            ['id' => 4, 'document_id' => 4, 'weight' => 1, 'size' => 412, 'permissions' => 'rw-r--r--'],
            ['id' => 5, 'document_id' => 5, 'weight' => 4, 'size' => 790, 'permissions' => 'rw-rw-r--'],
        ];
}
