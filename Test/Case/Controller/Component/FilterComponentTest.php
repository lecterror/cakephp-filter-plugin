<?php
/**
	CakePHP Filter Plugin

	Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
	<http://lecterror.com/>

	Multi-licensed under:
		MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
		LGPL <http://www.gnu.org/licenses/lgpl.html>
		GPL <http://www.gnu.org/licenses/gpl.html>
*/

App::uses('Router', 'Routing');
App::uses('Component', 'Filter.Filter');
require_once(dirname(dirname(dirname(__FILE__))) . DS . 'MockObjects.php');

class FilterTestCase extends CakeTestCase
{
	public $fixtures = array
		(
			'plugin.filter.document_category',
			'plugin.filter.document',
			'plugin.filter.item',
			'plugin.filter.metadata',
		);

	public $Controller = null;

	public function startTest($method)
	{
		Router::connect('/', array('controller' => 'document_tests', 'action' => 'index'));
		$request = new CakeRequest('/');
		$request->addParams(Router::parse('/'));
		$this->Controller = new DocumentTestsController($request);
		$this->Controller->uses = array('Document');

		if (array_search($method, array('testPersistence')) !== false)
		{
			$this->Controller->components = array
				(
					'Session',
					'Filter.Filter' => array('nopersist' => true)
				);
		}
		else
		{
			$this->Controller->components = array
				(
					'Session',
					'Filter.Filter'
				);
		}

		$this->Controller->constructClasses();
		$this->Controller->Session->destroy();
		$this->Controller->Components->trigger('initialize', array($this->Controller));
	}

	public function endTest($method)
	{
		$this->Controller->Session->destroy();
		$this->Controller = null;
	}

	/**
	 * Test bailing out when no filters are present.
	 */
	public function testNoFilters()
	{
		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->assertEmpty($this->Controller->Filter->settings);
		$this->assertFalse($this->Controller->Document->Behaviors->enabled('Filtered'));

		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->assertFalse(in_array('Filter.Filter', $this->Controller->helpers));
	}

	/**
	 * Test bailing out when a filter model can't be found
	 * or when the current action has no filters.
	 */
	public function testNoModelPresentOrNoActionFilters()
	{
		$testSettings = array
			(
				'index' => array
				(
					'DocumentArse' => array
					(
						'DocumentFeck.drink' => array('type' => 'irrelevant')
					)
				)
			);

		$this->setExpectedException('PHPUnit_Framework_Error_Notice');
		$this->Controller->filters = $testSettings;
		$this->Controller->Components->trigger('initialize', array($this->Controller));

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
		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->assertFalse($this->Controller->Document->Behaviors->enabled('Filtered'));

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
		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->assertTrue($this->Controller->Document->Behaviors->enabled('Filtered'));
	}

	/**
	 * Test basic filter settings.
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
				$this->Controller->name => $testSettings
			);

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->assertEquals($expected, $this->Controller->Filter->settings);
	}

	/**
	 * Test running a component with no filter data.
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

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->assertTrue(in_array('Filter.Filter', $this->Controller->helpers));
	}

	/**
	 * Test loading filter data from session (both full and empty).
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
					'FakeNonexistant' => array
					(
						'drink' => array('type' => 'select')
					)
				)
			);
		$this->Controller->filters = $testSettings;

		$sessionKey = sprintf('FilterPlugin.Filters.%s.%s', $this->Controller->name, $this->Controller->action);

		$filterValues = array();
		$this->Controller->Session->write($sessionKey, $filterValues);
		$this->setExpectedException('PHPUnit_Framework_Error_Notice');
		$this->Controller->Components->trigger('initialize', array($this->Controller));

		$this->setExpectedException('PHPUnit_Framework_Error_Notice');
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$actualFilterValues = $this->Controller->Document->getFilterValues();
		$this->assertEquals
			(
				$filterValues,
				$actualFilterValues[$this->Controller->Document->alias]
			);

		$filterValues = array('Document' => array('title' => 'in'));
		$this->Controller->Session->write($sessionKey, $filterValues);

		$this->Controller->Components->trigger('startup', array($this->Controller));
		$actualFilterValues = $this->Controller->Document->getFilterValues();
		$this->assertEquals
			(
				$filterValues,
				$actualFilterValues[$this->Controller->Document->alias]
			);

		$this->Controller->Session->delete($sessionKey);
	}

	/**
	 * Test loading filter data from a post request.
	 */
	public function testPostStartupData()
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';

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
		$this->Controller->data = $filterValues;

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));

		$sessionKey = sprintf('FilterPlugin.Filters.%s.%s', $this->Controller->name, $this->Controller->action);
		$sessionData = $this->Controller->Session->read($sessionKey);
		$this->assertEquals($filterValues, $sessionData);

		$actualFilterValues = $this->Controller->Document->getFilterValues();
		$this->assertEquals
			(
				$filterValues,
				$actualFilterValues[$this->Controller->Document->alias]
			);
	}

	/**
	 * Test exiting beforeRender when in an action with no settings.
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

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

		$this->assertFalse(isset($this->Controller->viewVars['viewFilterParams']));
	}

	/**
	 * Test triggering an error when the plugin runs into a setting
	 * for filtering a model which cannot be found.
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

		$this->setExpectedException('PHPUnit_Framework_Error_Notice');
		$this->Controller->Components->trigger('initialize', array($this->Controller));

		//$this->expectError();
		$this->Controller->Components->trigger('startup', array($this->Controller));

		$this->setExpectedException('PHPUnit_Framework_Error_Notice');
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));
	}

	/**
	 * Test the view variable generation for very basic filtering.
	 * Also tests model name detection and custom label.
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
						'DocumentCategory.id' => array('type' => 'select', 'label' => 'Category'),
					)
				)
			);
		$this->Controller->filters = $testSettings;

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

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
							'inputOptions' => array('class' => 'important')
						),
					)
				)
			);
		$this->Controller->filters = $testSettings;

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

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
							'selectOptions' => array('conditions' => array('DocumentCategory.description LIKE' => '%!%')),
						),
					)
				)
			);
		$this->Controller->filters = $testSettings;

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

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

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

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
				$this->Controller->name => $testSettings
			);

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->assertEquals($expected, $this->Controller->Filter->settings);
	}

	/**
	 * Test select input for the model filtered.
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
						),
					)
				)
			);
		$this->Controller->filters = $testSettings;

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

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
				)
			);
		$this->Controller->filters = $testSettings;

		$sessionKey = sprintf('FilterPlugin.Filters.%s.%s', 'SomeOtherController', $this->Controller->action);
		$filterValues = array('Document' => array('title' => 'in'), 'Filter' => array('filterFormId' => 'Document'));
		$this->Controller->Session->write($sessionKey, $filterValues);

		$sessionKey = sprintf('FilterPlugin.Filters.%s.%s', $this->Controller->name, $this->Controller->action);
		$filterValues = array('Document' => array('title' => 'in'), 'Filter' => array('filterFormId' => 'Document'));
		$this->Controller->Session->write($sessionKey, $filterValues);

		$this->Controller->Filter->nopersist = array();
		$this->Controller->Filter->nopersist[$this->Controller->name] = true;
		$this->Controller->Filter->nopersist['SomeOtherController'] = true;

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));

		$expected = array($this->Controller->name => array($this->Controller->action => $filterValues));
		$this->assertEquals($expected, $this->Controller->Session->read('FilterPlugin.Filters'));
	}

	/**
	 * Test whether filtering by belongsTo model text field
	 * works correctly.
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

		$this->Controller->Components->trigger('initialize', array($this->Controller));
		$this->Controller->Components->trigger('startup', array($this->Controller));
		$this->Controller->Components->trigger('beforeRender', array($this->Controller));

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
