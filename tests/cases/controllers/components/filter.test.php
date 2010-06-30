<?php
/*
This file is part of CakePHP Filter Plugin.

CakePHP Filter Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

CakePHP Filter Plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CakePHP Filter Plugin. If not, see <http://www.gnu.org/licenses/>.
*/

App::import('Component', 'Filter.Filter');
require_once(dirname(dirname(dirname(__FILE__))) . DS . 'mock_objects.php');

class FilterTestCase extends CakeTestCase
{
	var $fixtures = array('plugin.filter.document_category', 'plugin.filter.document', 'plugin.filter.item');

	var $Controller = null;

	function startTest()
	{
		Router::connect('/', array('controller' => 'document_tests', 'action' => 'index'));
		$this->Controller = ClassRegistry::init('DocumentTestsController');
		$this->Controller->params = Router::parse('/');
		$this->Controller->params['url']['url'] = '/';
		$this->Controller->action = $this->Controller->params['action'];
		$this->Controller->uses = array('Document');
		$this->Controller->components = array('Session', 'Filter.Filter');
		$this->Controller->constructClasses();
	}

	function endTest()
	{
		$this->Controller = null;
	}

	/**
	 * Test bailing out when no filters are present.
	 */
	function testNoFilters()
	{
		$this->Controller->Component->initialize($this->Controller);
		$this->assertTrue(empty($this->Controller->Filter->settings));
		$this->assertFalse($this->Controller->Document->Behaviors->enabled('Filtered'));

		$this->Controller->Component->startup($this->Controller);
		$this->assertFalse(in_array('Filter.Filter', $this->Controller->helpers));
	}

	/**
	 * Test bailing out when a filter model can't be found
	 * or when the current action has no filters.
	 */
	function testNoModelPresentOrNoActionFilters()
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

		$this->expectError();
		$this->Controller->filters = $testSettings;
		$this->Controller->Component->initialize($this->Controller);

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
		$this->Controller->Component->initialize($this->Controller);
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
		$this->Controller->Component->initialize($this->Controller);
		$this->assertTrue($this->Controller->Document->Behaviors->enabled('Filtered'));
	}

	/**
	 * Test basic filter settings.
	 */
	function testBasicFilters()
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

		$this->Controller->Component->initialize($this->Controller);
		$this->assertEqual($expected, $this->Controller->Filter->settings);
	}

	/**
	 * Test running a component with no filter data.
	 */
	function testEmptyStartup()
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


		$this->Controller->Component->initialize($this->Controller);
		$this->Controller->Component->startup($this->Controller);
		$this->assertTrue(in_array('Filter.Filter', $this->Controller->helpers));
	}

	/**
	 * Test loading filter data from session (both full and empty).
	 */
	function testSessionStartupData()
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
		$this->expectError();
		$this->Controller->Component->initialize($this->Controller);

		$this->expectError();
		$this->Controller->Component->startup($this->Controller);
		$this->assertEqual
			(
				$this->Controller->Document->Behaviors->Filtered->_filterValues[$this->Controller->Document->alias],
				$filterValues
			);

		$filterValues = array('Document' => array('title' => 'in'));
		$this->Controller->Session->write($sessionKey, $filterValues);

		$this->Controller->Component->startup($this->Controller);
		$this->assertEqual
			(
				$this->Controller->Document->Behaviors->Filtered->_filterValues[$this->Controller->Document->alias],
				$filterValues
			);
	}

}
