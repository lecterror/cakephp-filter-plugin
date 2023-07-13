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

App::import('Core', array('AppModel', 'Model'));
App::uses('Document', 'Filter.Test/Case/MockObjects');
App::uses('Document2', 'Filter.Test/Case/MockObjects');
App::uses('Document3', 'Filter.Test/Case/MockObjects');
App::uses('DocumentCategory', 'Filter.Test/Case/MockObjects');
App::uses('DocumentTestsController', 'Filter.Test/Case/MockObjects');
App::uses('Item', 'Filter.Test/Case/MockObjects');
App::uses('Metadata', 'Filter.Test/Case/MockObjects');

class FilteredBehaviorTest extends CakeTestCase
{
	/**
	 * @var string[]
	 */
	public $fixtures = array
		(
			'plugin.filter.document_category',
			'plugin.filter.document',
			'plugin.filter.item',
			'plugin.filter.metadata',
		);

	/**
	 * @var \Document
	 */
	public $Document = null;

	public function startTest($model)
	{
		$Document = ClassRegistry::init('Document');
		$this->assertInstanceOf('Document', $Document);
		$this->Document = $Document;
	}

	public function endTest($model)
	{
		unset($this->Document);
	}

	/**
	 * Detach and re-attach the behavior to reset the options.
	 *
	 * @param mixed[] $options Behavior options.
	 * @return void
	 */
	protected function _reattachBehavior($options = array())
	{
		$this->Document->Behaviors->detach('Filtered');
		$this->Document->Behaviors->attach('Filter.Filtered', $options);
	}

	/**
	 * Test attaching without options.
	 *
	 * @return void
	 */
	public function testBlankAttaching()
	{
		$this->Document->Behaviors->attach('Filter.Filtered');
		$this->assertTrue($this->Document->Behaviors->enabled('Filtered'));
	}

	/**
	 * Test attaching with options.
	 *
	 * @return void
	 */
	public function testInitSettings()
	{
		$testOptions = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategory.id'	=> array('type' => 'select', 'filterField' => 'document_category_id'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);

		$expected = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => false, 'selectOptions' => array()),
				'DocumentCategory.id'	=> array('type' => 'select', 'filterField' => 'document_category_id', 'condition' => 'like', 'required' => false, 'selectOptions' => array()),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?', 'condition' => 'like', 'required' => false, 'selectOptions' => array())
			);
		$Filtered = $this->Document->Behaviors->__get('Filtered');
		$this->assertInstanceOf('FilteredBehavior', $Filtered);
		$this->assertEquals($expected, $Filtered->settings[$this->Document->alias]);
	}

	/**
	 * Test init settings when only a single field is given, with no extra options.
	 *
	 * @return void
	 */
	public function testInitSettingsSingle()
	{
		$testOptions = array('Document.title');
		$this->_reattachBehavior($testOptions);

		$expected = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => false, 'selectOptions' => array()),
			);
		$Filtered = $this->Document->Behaviors->__get('Filtered');
		$this->assertInstanceOf('FilteredBehavior', $Filtered);
		$this->assertEquals($expected, $Filtered->settings[$this->Document->alias]);
	}

	/**
	 * Test setting the filter values for future queries.
	 *
	 * @return void
	 */
	public function testSetFilterValues()
	{
		$testOptions = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategory.id'	=> array('type' => 'select', 'filterField' => 'document_category_id'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);

		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in', 'is_private' => 0),
				'DocumentCategory'	=> array('id' => 1)
			);

		$this->Document->setFilterValues($filterValues);
		$actualFilterValues = $this->Document->getFilterValues();
		$this->assertEquals($filterValues, $actualFilterValues[$this->Document->alias]);
	}

	/**
	 * Test detecting an error in options - when a field is 'required' but no value is given for it.
	 *
	 * @return void
	 */
	public function testLoadingRequiredFieldValueMissing()
	{
		$testOptions = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategory.id'	=> array('type' => 'select', 'filterField' => 'document_category_id'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('is_private' => 0),
				'DocumentCategory'	=> array('id' => 1)
			);
		$this->Document->setFilterValues($filterValues);

		$this->expectException('PHPUnit_Framework_Error_Notice');
		$this->Document->find('first');
	}

	/**
	 * Test filtering with conditions from current model and belongsTo model.
	 *
	 * @return void
	 */
	public function testFilteringBelongsTo()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategory.id'	=> array('type' => 'select')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in'),
				'DocumentCategory'	=> array('id' => 1)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-06-28 10:39:23', 'updated' => '2010-06-29 11:22:48')),
				array('Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'))
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testFilteringBelongsToTextField()
	{
		$testOptions = array
			(
				'DocumentCategory.title'	=> array('type' => 'text')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'DocumentCategory'	=> array('title' => 'spec')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'))
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test filtering with conditions from current model and belongsTo model,
	 * same as testFilteringBelongsTo() except for a change in filterField format.
	 *
	 * @return void
	 */
	public function testFilteringBelongsToFilterFieldTest()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategory.id'	=> array('type' => 'select', 'filterField' => 'Document.document_category_id')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in'),
				'DocumentCategory'	=> array('id' => 1)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-06-28 10:39:23', 'updated' => '2010-06-29 11:22:48')),
				array('Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'))
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test various conditions for the type 'text' in filtering (less than, equal, like, etc..)
	 *
	 * @return void
	 */
	public function testFilteringBelongsToDifferentConditions()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => '='),
				'DocumentCategory.id'	=> array('type' => 'select')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('title' => 'Illegal explosives DIY'),
				'DocumentCategory'	=> array('id' => '')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1, 'created' => '2010-01-08 05:15:03', 'updated' => '2010-05-22 03:15:24')),
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);

		$testOptions = array
			(
				'id'					=> array('type' => 'text', 'condition' => '>='),
				'created'				=> array('type' => 'text', 'condition' => '<=')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('id' => 3, 'created' => '2010-03-01')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1, 'created' => '2010-01-08 05:15:03', 'updated' => '2010-05-22 03:15:24')),
				array('Document' => array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
				array('Document' => array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
				array('Document' => array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test filtering with conditions on current model, the belongsTo model
	 * and hasMany model (behavior adds an INNER JOIN in query).
	 *
	 * @return void
	 */
	public function testFilteringBelongsToAndHasMany()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategory.id'	=> array('type' => 'select'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?'),
				'Item.code'				=> array('type' => 'text'),
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in', 'is_private' => 0),
				'DocumentCategory'	=> array('id' => 1),
				'Item'				=> array('code' => '04')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
					'DocumentCategory' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'Metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
					'Item' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		$result = $this->Document->find('all');
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
					'DocumentCategory' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'Metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
				)
			);

		$result = $this->Document->find('all', array('recursive' => 0));
		$this->assertEquals($expected, $result);

		$this->Document->unbindModel(array('hasMany' => array('Item')), false);
		$this->Document->bindModel(array('hasMany' => array('Item')), false);

		$result = $this->Document->find('all', array('recursive' => 0));
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44')
				)
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test filtering with join which has some custom
	 * condition in the relation (both string and array).
	 *
	 * @return void
	 */
	public function testCustomJoinConditions()
	{
		$testOptions = array
			(
				'Metadata.weight'	=> array('type' => 'text', 'condition' => '>'),
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Metadata'			=> array('weight' => 3),
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'),
					'Metadata' => array('id' => 5, 'document_id' => 5, 'weight' => 4, 'size' => 790, 'permissions' => 'rw-rw-r--'),
				)
			);

		$this->Document->recursive = -1;
		$oldConditions = $this->Document->hasOne['Metadata']['conditions'];
		$this->Document->hasOne['Metadata']['conditions'] = array('Metadata.size > 500');
		$this->Document->Behaviors->attach('Containable');

		$result = $this->Document->find('all', array('contain' => array('Metadata')));
		$this->assertEquals($expected, $result);

		$this->Document->hasOne['Metadata']['conditions'] = 'Metadata.size > 500';
		$result = $this->Document->find('all', array('contain' => array('Metadata')));
		$this->assertEquals($expected, $result);

		$this->Document->hasOne['Metadata']['conditions'] = $oldConditions;
		$this->Document->Behaviors->detach('Containable');
	}

	/**
	 * Test for any possible conflicts with Containable behavior.
	 *
	 * @return void
	 */
	public function testFilteringBelongsToAndHasManyWithContainable()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategory.id'	=> array('type' => 'select'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?'),
				'Item.code'				=> array('type' => 'text'),
			);

		$this->_reattachBehavior($testOptions);
		$this->Document->Behaviors->attach('Containable');

		$filterValues = array
			(
				'Document'			=> array('title' => 'in', 'is_private' => 0),
				'DocumentCategory'	=> array('id' => 1),
				'Item'				=> array('code' => '04')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
					'DocumentCategory' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'Item' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		$result = $this->Document->find('all', array('contain' => array('DocumentCategory', 'Item')));
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
					'DocumentCategory' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
				)
			);

		$result = $this->Document->find('all', array('contain' => array('DocumentCategory')));
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
				)
			);

		$result = $this->Document->find('all', array('contain' => array()));
		$this->assertEquals($expected, $result);

		$this->Document->Behaviors->detach('Containable');
	}

	/**
	 * Test filtering by text input with hasOne relation.
	 *
	 * @return void
	 */
	public function testHasOneAndHasManyWithTextSearch()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'Item.code'				=> array('type' => 'text'),
				'Metadata.size'			=> array('type' => 'text', 'condition' => '='),
			);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in'),
				'Item'				=> array('code' => '04'),
				'Metadata'			=> array('size' => 45),
			);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec'),
				)
			);

		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);

		$this->Document->recursive = -1;
		$result = $this->Document->find('all', array('fields' => array('Document.id', 'Document.title')));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test filtering with Containable and hasOne Model.field.
	 *
	 * @return void
	 */
	public function testHasOneWithContainable()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'Item.code'				=> array('type' => 'text'),
				'Metadata.size'			=> array('type' => 'text', 'condition' => '='),
			);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in'),
				'Item'				=> array('code' => '04'),
				'Metadata'			=> array('size' => 45),
			);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
					'Metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
					'Item' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		// containable first, filtered second
		$this->Document->Behaviors->attach('Containable');
		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);
		$result = $this->Document->find('all', array('contain' => array('Metadata', 'Item')));
		$this->assertEquals($expected, $result);
		$this->Document->Behaviors->detach('Containable');

		// filtered first, containable second
		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);
		$this->Document->Behaviors->attach('Containable');
		$result = $this->Document->find('all', array('contain' => array('Metadata', 'Item')));
		$this->assertEquals($expected, $result);
		$this->Document->Behaviors->detach('Containable');
	}

	/**
	 * Test filtering when a join is already present in the query,
	 * this should prevent duplicate joins and query errors.
	 *
	 * @return void
	 */
	public function testJoinAlreadyPresent()
	{
		$testOptions = array
			(
				'title'					=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'Item.code'				=> array('type' => 'text'),
				'Metadata.size'			=> array('type' => 'text', 'condition' => '='),
			);

		$filterValues = array
			(
				'Document'			=> array('title' => 'in'),
				'Item'				=> array('code' => '04'),
				'Metadata'			=> array('size' => 45),
			);

		$expected = array
			(
				array
				(
					'Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44'),
					'DocumentCategory' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'Metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
					'Item' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		$customJoin = array();
		$customJoin[] = array
			(
				'table' => 'items',
				'alias' => 'FilterItem',
				'type' => 'INNER',
				'conditions' => 'Document.id = FilterItem.document_id',
			);

		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);
		$result = $this->Document->find('all', array('joins' => $customJoin, 'recursive' => 1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test the 'nofilter' query param.
	 *
	 * @return void
	 */
	public function testNofilterFindParam()
	{
		$testOptions = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategory.id'	=> array('type' => 'select'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?', 'default' => 0)
			);
		$this->_reattachBehavior($testOptions);


		$filterValues = array
			(
				'DocumentCategory'	=> array('id' => 2),
				'Document'			=> array('title' => '')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15'))
			);

		$result = $this->Document->find('all', array('recursive' => -1, 'nofilter' => true));
		$this->assertNotEquals($expected, $result);

		$result = $this->Document->find('all', array('recursive' => -1, 'nofilter' => 'true'));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test bailing out if no settings exist for the current model.
	 *
	 * @return void
	 */
	public function testExitWhenNoSettings()
	{
		$this->Document->DocumentCategory->Behaviors->attach('Filter.Filtered');

		$this->assertFalse(isset($this->Document->DocumentCategory->Behaviors->Filtered->settings[$this->Document->DocumentCategory->alias]));

		$filterValues = array
			(
				'DocumentCategory'	=> array('id' => 2)
			);
		$this->Document->DocumentCategory->setFilterValues($filterValues);

		$expected = array
			(
				array('DocumentCategory' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!')),
				array('DocumentCategory' => array('id' => 2, 'title' => 'Imaginary Spec', 'description' => 'This doc does not exist')),
				array('DocumentCategory' => array('id' => 3, 'title' => 'Nonexistant data', 'description' => 'This doc is probably empty')),
				array('DocumentCategory' => array('id' => 4, 'title' => 'Illegal explosives DIY', 'description' => 'Viva la revolucion!')),
				array('DocumentCategory' => array('id' => 5, 'title' => 'Father Ted', 'description' => 'Feck! Drink! Arse! Girls!'))
			);

		$result = $this->Document->DocumentCategory->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);

		$this->Document->DocumentCategory->Behaviors->detach('Filtered');
	}

	/**
	 * Test beforeDataFilter() callback, used to cancel filtering if necessary.
	 *
	 * @return void
	 */
	public function testBeforeDataFilterCallbackCancel()
	{
		$Document = ClassRegistry::init('Document2');
		$this->assertInstanceOf('Document2', $Document);
		$this->Document = $Document;
		$testOptions = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategory.id'	=> array('type' => 'select'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);


		$filterValues = array
			(
				'DocumentCategory'	=> array('id' => 2)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-06-28 10:39:23', 'updated' => '2010-06-29 11:22:48')),
				array('Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44')),
				array('Document' => array('id' => 3, 'title' => 'Nonexistant data', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-04-28 11:12:33', 'updated' => '2010-05-05 15:03:24')),
				array('Document' => array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1, 'created' => '2010-01-08 05:15:03', 'updated' => '2010-05-22 03:15:24')),
				array('Document' => array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
				array('Document' => array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
				array('Document' => array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test afterDataFilter() callback, used to modify the conditions after
	 * filter conditions have been applied.
	 *
	 * @return void
	 */
	public function testAfterDataFilterCallbackQueryChange()
	{
		$Document = ClassRegistry::init('Document3');
		$this->assertInstanceOf('Document3', $Document);
		$this->Document = $Document;
		$this->Document->itemToUnset = 'FilterDocumentCategory.id';

		$testOptions = array
			(
				'Document.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategory.id'	=> array('type' => 'select'),
				'Document.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);


		$filterValues = array
			(
				'DocumentCategory'	=> array('id' => 2)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('Document' => array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-06-28 10:39:23', 'updated' => '2010-06-29 11:22:48')),
				array('Document' => array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-03-28 12:19:13', 'updated' => '2010-04-29 11:23:44')),
				array('Document' => array('id' => 3, 'title' => 'Nonexistant data', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0, 'created' => '2010-04-28 11:12:33', 'updated' => '2010-05-05 15:03:24')),
				array('Document' => array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1, 'created' => '2010-01-08 05:15:03', 'updated' => '2010-05-22 03:15:24')),
				array('Document' => array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
				array('Document' => array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
				array('Document' => array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0, 'created' => '2009-01-13 05:15:03', 'updated' => '2010-12-05 03:24:15')),
			);

		$result = $this->Document->find('all', array('recursive' => -1));
		$this->assertEquals($expected, $result);
	}
}
