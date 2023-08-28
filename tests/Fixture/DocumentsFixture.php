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

class DocumentsFixture extends TestFixture
{
    /**
     * @var mixed[]
     */
    public $fields =
        [
            'id' => ['type' => 'integer'],
            'title' => ['type' => 'string', 'length' => '255', 'null' => false],
            'document_category_id' => ['type' => 'integer', 'null' => false],
            'owner_id' => ['type' => 'integer', 'null' => false],
            'is_private' => ['type' => 'integer', 'length' => 1, 'null' => false],
            'created' => ['type' => 'datetime', 'null' => false],
            'updated' => ['type' => 'datetime', 'null' => true],
            '_constraints' => [
                'primary' => ['type' => 'primary', 'columns' => ['id']],
            ],
        ];

    /**
     * @var (int|string)[][]
     */
    public $records =
        [
            ['id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-06-28 10:39:23', 'updated' => '2010-06-29 11:22:48'],
            ['id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'],
            ['id' => 3, 'title' => 'Nonexistant data', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-04-28 11:12:33', 'updated' => '2010-05-05 15:03:24'],
            ['id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1, 'created' => '2010-01-08 05:15:03', 'updated' => '2010-05-22 03:15:24'],
            ['id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'],
            ['id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'],
            ['id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'],
        ];
}
