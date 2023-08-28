<?php
declare(strict_types=1);

namespace Filter\Test\TestCase\Controller\Component;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Filter\Test\TestCase\MockObjects\DocumentCategoriesTable;
use Filter\Test\TestCase\MockObjects\DocumentsTable;
use Filter\Test\TestCase\MockObjects\DocumentTestsController;

/**
    CakePHP Filter Plugin

    Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
    <http://lecterror.com/>

    Multi-licensed under:
        MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
        LGPL <http://www.gnu.org/licenses/lgpl.html>
        GPL <http://www.gnu.org/licenses/gpl.html>
 */

class FilterComponentTest extends TestCase
{
    /**
     * @var string[]
     */
    public $fixtures =
        [
            'plugin.Filter.DocumentCategories',
            'plugin.Filter.Documents',
            'plugin.Filter.Items',
            'plugin.Filter.Metadata',
        ];

    /**
     * @var \Filter\Test\TestCase\MockObjects\DocumentTestsController
     */
    public $Controller = null;

    public function setUp()
    {
        parent::setUp();
        $request = new ServerRequest([
            'params' => [
                'controller' => 'DocumentTests',
                'action' => 'index',
            ],
        ]);
        $this->Controller = new DocumentTestsController($request);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->Controller->getRequest()->getSession()->destroy();
        unset($this->Controller);
    }

    /**
     * Test bailing out when no filters are present.
     *
     * @return void
     */
    public function testNoFilters()
    {
        $this->assertEmpty($this->Controller->Filter->settings);
        $this->assertFalse($this->Controller->Document->hasBehavior('Filtered'));

        $this->assertFalse(in_array('Filter.Filter', $this->Controller->viewBuilder()->getHelpers()));
    }

    /**
     * @return void
     */
    public function testNoActionFilters()
    {
        $testSettings =
            [
                'someotheraction' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];

        $this->Controller->filters = $testSettings;
        $this->Controller->dispatchEvent('Controller.initialize');
        $this->assertFalse($this->Controller->Document->hasBehavior('Filtered'));

        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];

        $this->Controller->filters = $testSettings;
        $this->Controller->dispatchEvent('Controller.initialize');
        $this->assertTrue($this->Controller->Document->hasBehavior('Filtered'));
    }

    /**
     * Test basic filter settings.
     *
     * @return void
     */
    public function testBasicFilters()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $expected =
            [
                $this->Controller->getName() => $testSettings,
            ];
        $this->Controller->dispatchEvent('Controller.initialize');
        $this->assertEquals($expected, $this->Controller->Filter->settings);
    }

    /**
     * Test running a component with no filter data.
     *
     * @return void
     */
    public function testEmptyStartup()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->assertTrue(in_array('Filter.Filter', $this->Controller->viewBuilder()->getHelpers()));
    }

    /**
     * @return void
     */
    public function testSessionStartupDataFakeNonexistantModel()
    {
        $testSettings =
        [
            'index' =>
            [
                'FakeNonexistant' =>
                [
                    'drink' => ['type' => 'select'],
                ],
            ],
        ];
        $this->Controller->filters = $testSettings;
        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );
        $filterValues = [];
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);
        $this->expectException('PHPUnit\Framework\Error\Notice');
        $this->Controller->dispatchEvent('Controller.initialize');
    }

    /**
     * Test loading filter data from session (both full and empty).
     *
     * @return void
     */
    public function testSessionStartupData()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );

        $filterValues = [];
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);
        $this->Controller->dispatchEvent('Controller.initialize');

        $this->Controller->dispatchEvent('Controller.startup');
        $actualFilterValues = $this->Controller->Document->getFilterValues();
        $this->assertEquals(
            $filterValues,
            $actualFilterValues[$this->Controller->Document->getAlias()]
        );

        $filterValues = ['Document' => ['title' => 'in']];
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);

        $this->Controller->dispatchEvent('Controller.startup');
        $actualFilterValues = $this->Controller->Document->getFilterValues();
        $this->assertEquals(
            $filterValues,
            $actualFilterValues[$this->Controller->Document->getAlias()]
        );

        $this->Controller->getRequest()->getSession()->delete($sessionKey);
    }

    /**
     * Test loading filter data from a post request.
     *
     * @return void
     */
    public function testPostStartupData()
    {
        $request = new ServerRequest([
            'params' => [
                'controller' => 'DocumentTests',
                'action' => 'index',
            ],
            'environment' => [
                'REQUEST_METHOD' => 'POST',
            ],
        ]);
        $this->Controller = new DocumentTestsController($request);
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];

        $this->Controller->filters = $testSettings;

        $filterValues = ['Document' => ['title' => 'in'], 'Filter' => ['filterFormId' => 'Document']];
        $this->Controller->request = $this->Controller->getRequest()->withParsedBody($filterValues);

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );
        $sessionData = $this->Controller->getRequest()->getSession()->read($sessionKey);
        $this->assertEquals($filterValues, $sessionData);

        $actualFilterValues = $this->Controller->Document->getFilterValues();
        $this->assertEquals(
            $filterValues,
            $actualFilterValues[$this->Controller->Document->getAlias()]
        );
    }

    /**
     * Test exiting beforeRender when in an action with no settings.
     *
     * @return void
     */
    public function testBeforeRenderAbort()
    {
        $testSettings =
            [
                'veryMuchNotIndex' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $this->assertFalse(isset($this->Controller->viewVars['viewFilterParams']));
    }

    /**
     * Test triggering an error when the plugin runs into a setting
     * for filtering a model which cannot be found.
     *
     * @return void
     */
    public function testNoModelFound()
    {
        $testSettings =
            [
                'index' =>
                [
                    'ThisModelDoesNotExist' =>
                    [
                        'ThisModelDoesNotExist.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;
        $this->expectException('PHPUnit\Framework\Error\Notice');
        $this->Controller->dispatchEvent('Controller.initialize');
    }

    /**
     * Test the view variable generation for very basic filtering.
     * Also tests model name detection and custom label.
     *
     * @return void
     */
    public function testBasicViewInfo()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'title',
                        'DocumentCategory.id' => [
                            'type' => 'select',
                            'label' => 'Category',
                            'className' => DocumentCategoriesTable::class,
                        ],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected =
            [
                ['name' => 'Document.title', 'options' => ['type' => 'text']],

                [
                    'name' => 'DocumentCategory.id',
                    'options' =>
                    [
                        'type' => 'select',
                        'options' =>
                        [
                            1 => 'Testing Doc',
                            2 => 'Imaginary Spec',
                            3 => 'Nonexistant data',
                            4 => 'Illegal explosives DIY',
                            5 => 'Father Ted',
                        ],
                        'empty' => false,
                        'label' => 'Category',
                    ],
                ],
            ];

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test passing additional inputOptions to the form
     * helper, used to customize search form.
     *
     * @return void
     */
    public function testAdditionalInputOptions()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'title' => ['inputOptions' => 'disabled'],
                        'DocumentCategory.id' =>
                        [
                            'type' => 'select',
                            'label' => 'Category',
                            'inputOptions' => ['class' => 'important'],
                            'className' => DocumentCategoriesTable::class,
                        ],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected =
            [

                [
                    'name' => 'Document.title',
                    'options' =>
                    [
                        'type' => 'text',
                        'disabled',
                    ],
                ],

                [
                    'name' => 'DocumentCategory.id',
                    'options' =>
                    [
                        'type' => 'select',
                        'options' =>
                        [
                            1 => 'Testing Doc',
                            2 => 'Imaginary Spec',
                            3 => 'Nonexistant data',
                            4 => 'Illegal explosives DIY',
                            5 => 'Father Ted',
                        ],
                        'empty' => false,
                        'label' => 'Category',
                        'class' => 'important',
                    ],
                ],
            ];

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test data fetching for select input when custom selector
     * and custom options are provided.
     *
     * @return void
     */
    public function testCustomSelector()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'DocumentCategory.id' =>
                        [
                            'type' => 'select',
                            'label' => 'Category',
                            'selector' => 'customSelector',
                            'selectOptions' => [
                                'conditions' => ['DocumentCategory.description LIKE' => '%!%'],
                            ],
                            'className' => DocumentCategoriesTable::class,
                        ],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected =
            [

                [
                    'name' => 'DocumentCategory.id',
                    'options' =>
                    [
                        'type' => 'select',
                        'options' =>
                        [
                            1 => 'Testing Doc',
                            5 => 'Father Ted',
                        ],
                        'empty' => false,
                        'label' => 'Category',
                    ],
                ],
            ];

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test checkbox input filtering.
     *
     * @return void
     */
    public function testCheckboxOptions()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.is_private' =>
                        [
                            'type' => 'checkbox',
                            'label' => 'Private?',
                            'default' => true,
                        ],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected =
            [

                [
                    'name' => 'Document.is_private',
                    'options' =>
                    [
                        'type' => 'checkbox',
                        'checked' => true,
                        'label' => 'Private?',
                    ],
                ],
            ];

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test basic filter settings.
     *
     * @return void
     */
    public function testSelectMultiple()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'DocumentCategory.id' =>
                        [
                            'type' => 'select',
                            'multiple' => true,
                        ],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $expected =
            [
                $this->Controller->getName() => $testSettings,
            ];

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->assertEquals($expected, $this->Controller->Filter->settings);
    }

    /**
     * Test select input for the model filtered.
     *
     * @return void
     */
    public function testSelectInputFromSameModel()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' =>
                        [
                            'type' => 'select',
                            'className' => DocumentsTable::class,
                        ],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected =
            [

                [
                    'name' => 'Document.title',
                    'options' =>
                    [
                        'type' => 'select',
                        'options' =>
                        [
                            'Testing Doc' => 'Testing Doc',
                            'Imaginary Spec' => 'Imaginary Spec',
                            'Nonexistant data' => 'Nonexistant data',
                            'Illegal explosives DIY' => 'Illegal explosives DIY',
                            'Father Ted' => 'Father Ted',
                            'Duplicate title' => 'Duplicate title',
                        ],
                        'empty' => '',
                    ],
                ],
            ];

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test disabling persistence for single action
     * and for the entire controller.
     *
     * @return void
     */
    public function testPersistence()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'Document.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;
        $this->Controller->components()->unload('Filter');
        $this->Controller->loadComponent('Filter.Filter', ['nopersist' => true]);

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            'SomeOtherController',
            $this->Controller->getRequest()->getParam('action')
        );
        $filterValues = ['Document' => ['title' => 'in'], 'Filter' => ['filterFormId' => 'Document']];
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );
        $filterValues = ['Document' => ['title' => 'in'], 'Filter' => ['filterFormId' => 'Document']];
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);

        $this->Controller->Filter->nopersist = [];
        $this->Controller->Filter->nopersist[$this->Controller->getName()] = true;
        $this->Controller->Filter->nopersist['SomeOtherController'] = true;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');

        $expected = [
            $this->Controller->getName() => [
                $this->Controller->getRequest()->getParam('action') => $filterValues,
            ],
        ];
        $this->assertEquals($expected, $this->Controller->getRequest()->getSession()->read('FilterPlugin.Filters'));
    }

    /**
     * Test whether filtering by belongsTo model text field
     * works correctly.
     *
     * @return void
     */
    public function testBelongsToFilteringByText()
    {
        $testSettings =
            [
                'index' =>
                [
                    'Document' =>
                    [
                        'DocumentCategory.title' => ['type' => 'text'],
                    ],
                ],
            ];
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected =
            [

                [
                    'name' => 'DocumentCategory.title',
                    'options' =>
                    [
                        'type' => 'text',
                    ],
                ],
            ];

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }
}
