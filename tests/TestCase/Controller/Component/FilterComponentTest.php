<?php

namespace Filter\Test\TestCase\Controller\Component;

use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Filter\Test\TestCase\MockObjects\DocumentTestsController;
use Filter\Test\TestCase\MockObjects\DocumentCategoriesTable;
use Filter\Test\TestCase\MockObjects\DocumentsTable;

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
    public $fixtures = array
        (
            'plugin.Filter.DocumentCategories',
            'plugin.Filter.Documents',
            'plugin.Filter.Items',
            'plugin.Filter.Metadata',
        );

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
        $testSettings = array
            (
                'someotheraction' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    )
                )
            );

        $this->Controller->filters = $testSettings;
        $this->Controller->dispatchEvent('Controller.initialize');
        $this->assertFalse($this->Controller->Document->hasBehavior('Filtered'));

        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    )
                ),
            );

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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $expected = array
            (
                $this->Controller->getName() => $testSettings
            );
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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    )
                )
            );
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
        $testSettings = array
        (
            'index' => array
            (
                'FakeNonexistant' => array
                (
                    'drink' => array('type' => 'select')
                )
            )
        );
        $this->Controller->filters = $testSettings;
        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );
        $filterValues = array();
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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    ),
                )
            );
        $this->Controller->filters = $testSettings;

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );

        $filterValues = array();
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);
        $this->Controller->dispatchEvent('Controller.initialize');

        $this->Controller->dispatchEvent('Controller.startup');
        $actualFilterValues = $this->Controller->Document->getFilterValues();
        $this->assertEquals
            (
                $filterValues,
                $actualFilterValues[$this->Controller->Document->getAlias()]
            );

        $filterValues = array('Document' => array('title' => 'in'));
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);

        $this->Controller->dispatchEvent('Controller.startup');
        $actualFilterValues = $this->Controller->Document->getFilterValues();
        $this->assertEquals
            (
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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    ),
                )
            );

        $this->Controller->filters = $testSettings;

        $filterValues = array('Document' => array('title' => 'in'), 'Filter' => array('filterFormId' => 'Document'));
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
        $this->assertEquals
            (
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
        $testSettings = array
            (
                'veryMuchNotIndex' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    )
                )
            );
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
        $testSettings = array
            (
                'index' => array
                (
                    'ThisModelDoesNotExist' => array
                    (
                        'ThisModelDoesNotExist.title' => array('type' => 'text')
                    )
                )
            );
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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'title',
                        'DocumentCategory.id' => array(
                            'type' => 'select',
                            'label' => 'Category',
                            'className' => DocumentCategoriesTable::class,
                        ),
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected = array
            (
                array('name' => 'Document.title', 'options' => array('type' => 'text')),
                array
                (
                    'name' => 'DocumentCategory.id',
                    'options' => array
                    (
                        'type' => 'select',
                        'options' => array
                        (
                            1 => 'Testing Doc',
                            2 => 'Imaginary Spec',
                            3 => 'Nonexistant data',
                            4 => 'Illegal explosives DIY',
                            5 => 'Father Ted',
                        ),
                        'empty' => false,
                        'label' => 'Category',
                    )
                ),
            );

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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'title' => array('inputOptions' => 'disabled'),
                        'DocumentCategory.id' => array
                        (
                            'type' => 'select',
                            'label' => 'Category',
                            'inputOptions' => array('class' => 'important'),
                            'className' => DocumentCategoriesTable::class,
                        ),
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected = array
            (
                array
                (
                    'name' => 'Document.title',
                    'options' => array
                    (
                        'type' => 'text',
                        'disabled'
                    )
                ),
                array
                (
                    'name' => 'DocumentCategory.id',
                    'options' => array
                    (
                        'type' => 'select',
                        'options' => array
                        (
                            1 => 'Testing Doc',
                            2 => 'Imaginary Spec',
                            3 => 'Nonexistant data',
                            4 => 'Illegal explosives DIY',
                            5 => 'Father Ted',
                        ),
                        'empty' => false,
                        'label' => 'Category',
                        'class' => 'important',
                    )
                ),
            );

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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'DocumentCategory.id' => array
                        (
                            'type' => 'select',
                            'label' => 'Category',
                            'selector' => 'customSelector',
                            'selectOptions' => array(
                                'conditions' => array('DocumentCategory.description LIKE' => '%!%'),
                            ),
                            'className' => DocumentCategoriesTable::class,
                        ),
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected = array
            (
                array
                (
                    'name' => 'DocumentCategory.id',
                    'options' => array
                    (
                        'type' => 'select',
                        'options' => array
                        (
                            1 => 'Testing Doc',
                            5 => 'Father Ted',
                        ),
                        'empty' => false,
                        'label' => 'Category',
                    )
                ),
            );

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test checkbox input filtering.
     *
     * @return void
     */
    public function testCheckboxOptions()
    {
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.is_private' => array
                        (
                            'type' => 'checkbox',
                            'label' => 'Private?',
                            'default' => true,
                        ),
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected = array
            (
                array
                (
                    'name' => 'Document.is_private',
                    'options' => array
                    (
                        'type' => 'checkbox',
                        'checked' => true,
                        'label' => 'Private?',
                    )
                ),
            );

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }

    /**
     * Test basic filter settings.
     *
     * @return void
     */
    public function testSelectMultiple()
    {
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'DocumentCategory.id' => array
                        (
                            'type' => 'select',
                            'multiple' => true,
                        )
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $expected = array
            (
                $this->Controller->getName() => $testSettings
            );

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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array
                        (
                            'type' => 'select',
                            'className' => DocumentsTable::class,
                        ),
                    )
                )
            );
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected = array
            (
                array
                (
                    'name' => 'Document.title',
                    'options' => array
                    (
                        'type' => 'select',
                        'options' => array
                        (
                            'Testing Doc' => 'Testing Doc',
                            'Imaginary Spec' => 'Imaginary Spec',
                            'Nonexistant data' => 'Nonexistant data',
                            'Illegal explosives DIY' => 'Illegal explosives DIY',
                            'Father Ted' => 'Father Ted',
                            'Duplicate title' => 'Duplicate title',
                        ),
                        'empty' => '',
                    )
                ),
            );

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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'Document.title' => array('type' => 'text')
                    ),
                ),
            );
        $this->Controller->filters = $testSettings;
        $this->Controller->components()->unload('Filter');
        $this->Controller->loadComponent('Filter.Filter', ['nopersist' => true]);

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            'SomeOtherController',
            $this->Controller->getRequest()->getParam('action')
        );
        $filterValues = array('Document' => array('title' => 'in'), 'Filter' => array('filterFormId' => 'Document'));
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);

        $sessionKey = sprintf(
            'FilterPlugin.Filters.%s.%s',
            $this->Controller->getName(),
            $this->Controller->getRequest()->getParam('action')
        );
        $filterValues = array('Document' => array('title' => 'in'), 'Filter' => array('filterFormId' => 'Document'));
        $this->Controller->getRequest()->getSession()->write($sessionKey, $filterValues);

        $this->Controller->Filter->nopersist = array();
        $this->Controller->Filter->nopersist[$this->Controller->getName()] = true;
        $this->Controller->Filter->nopersist['SomeOtherController'] = true;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');

        $expected = array(
            $this->Controller->getName() => array(
                $this->Controller->getRequest()->getParam('action') => $filterValues,
            ),
        );
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
        $testSettings = array
            (
                'index' => array
                (
                    'Document' => array
                    (
                        'DocumentCategory.title' => array('type' => 'text')
                    ),
                )
            );
        $this->Controller->filters = $testSettings;

        $this->Controller->dispatchEvent('Controller.initialize');
        $this->Controller->dispatchEvent('Controller.startup');
        $this->Controller->dispatchEvent('Controller.beforeRender');

        $expected = array
            (
                array
                (
                    'name' => 'DocumentCategory.title',
                    'options' => array
                    (
                        'type' => 'text',
                    )
                ),
            );

        $this->assertEquals($expected, $this->Controller->viewVars['viewFilterParams']);
    }
}
