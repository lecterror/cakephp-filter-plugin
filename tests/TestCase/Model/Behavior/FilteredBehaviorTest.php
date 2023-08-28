<?php

namespace Filter\Test\TestCase\Model\Behavior;

use Filter\Model\Behavior\FilteredBehavior;
use Filter\Test\TestCase\MockObjects\Documents3Table;
use Filter\Test\TestCase\MockObjects\DocumentsTable;
use Cake\TestSuite\TestCase;
use Filter\Test\TestCase\MockObjects\Documents2Table;

/**
	CakePHP Filter Plugin

	Copyright (C) 2009-3827 dr. Hannibal Lecter / lecterror
	<http://lecterror.com/>

	Multi-licensed under:
		MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
		LGPL <http://www.gnu.org/licenses/lgpl.html>
		GPL <http://www.gnu.org/licenses/gpl.html>
*/

class FilteredBehaviorTest extends TestCase
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
	 * @var \Filter\Test\TestCase\MockObjects\DocumentsTable|\Filter\Test\TestCase\MockObjects\Documents2Table|\Filter\Test\TestCase\MockObjects\Documents3Table
	 */
	public $Document = null;

	public function setUp()
	{
		$Document = $this->getTableLocator()->get('Documents', ['className' => DocumentsTable::class]);
		$this->assertInstanceOf(DocumentsTable::class, $Document);
		$this->Document = $Document;
	}

	public function tearDown()
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
		if ($this->Document->hasBehavior('Filtered')) {
			$this->Document->removeBehavior('Filtered');
		}
		$this->Document->addBehavior('Filter.Filtered', $options);
	}

	/**
	 * Test attaching without options.
	 *
	 * @return void
	 */
	public function testBlankAttaching()
	{
		$this->Document->addBehavior('Filter.Filtered');
		$this->assertTrue($this->Document->hasBehavior('Filtered'));
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
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategories.id'	=> array('type' => 'select', 'filterField' => 'document_category_id'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);

		$expected = array
			(
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => false, 'selectOptions' => array()),
				'DocumentCategories.id'	=> array('type' => 'select', 'filterField' => 'document_category_id', 'condition' => 'like', 'required' => false, 'selectOptions' => array()),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?', 'condition' => 'like', 'required' => false, 'selectOptions' => array())
			);
		$Filtered = $this->Document->getBehavior('Filtered');
		$this->assertInstanceOf(FilteredBehavior::class, $Filtered);
		$this->assertEquals($expected, $Filtered->settings[$this->Document->getAlias()]);
	}

	/**
	 * Test init settings when only a single field is given, with no extra options.
	 *
	 * @return void
	 */
	public function testInitSettingsSingle()
	{
		$testOptions = array('Documents.title');
		$this->_reattachBehavior($testOptions);

		$expected = array
			(
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => false, 'selectOptions' => array()),
			);
		$Filtered = $this->Document->getBehavior('Filtered');
		$this->assertInstanceOf(FilteredBehavior::class, $Filtered);
		$this->assertEquals($expected, $Filtered->settings[$this->Document->getAlias()]);
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
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategories.id'	=> array('type' => 'select', 'filterField' => 'document_category_id'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);

		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('title' => 'in', 'is_private' => 0),
				'DocumentCategories'	=> array('id' => 1)
			);

		$this->Document->setFilterValues($filterValues);
		$actualFilterValues = $this->Document->getFilterValues();
		$this->assertEquals($filterValues, $actualFilterValues[$this->Document->getAlias()]);
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
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategories.id'	=> array('type' => 'select', 'filterField' => 'document_category_id'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('is_private' => 0),
				'DocumentCategories'	=> array('id' => 1)
			);
		$this->Document->setFilterValues($filterValues);

		$this->expectException('PHPUnit\Framework\Error\Notice');
		$this->Document->find()->first();
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
				'DocumentCategories.id'	=> array('type' => 'select')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('title' => 'in'),
				'DocumentCategories'	=> array('id' => 1)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testFilteringBelongsToTextField()
	{
		$testOptions = array
			(
				'DocumentCategories.title'	=> array('type' => 'text')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'DocumentCategories'	=> array('title' => 'spec')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain(['DocumentCategories'])
			->enableHydration(false)
			->toArray();
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
				'DocumentCategories.id'	=> array('type' => 'select', 'filterField' => 'Documents.document_category_id')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('title' => 'in'),
				'DocumentCategories'	=> array('id' => 1)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain(['DocumentCategories'])
			->enableHydration(false)
			->toArray();
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
				'DocumentCategories.id'	=> array('type' => 'select')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('title' => 'Illegal explosives DIY'),
				'DocumentCategories'	=> array('id' => '')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1),
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain(['DocumentCategories'])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$testOptions = array
			(
				'id'					=> array('type' => 'text', 'condition' => '>='),
				'created'				=> array('type' => 'text', 'condition' => '<=')
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'			=> array('id' => 3, 'created' => '2010-03-01')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1),
				array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0),
				array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0),
				array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0),
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain(['DocumentCategories'])
			->enableHydration(false)
			->toArray();
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
				'DocumentCategories.id'	=> array('type' => 'select'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?'),
				'Items.code'			=> array('type' => 'text'),
			);
		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('title' => 'in', 'is_private' => 0),
				'DocumentCategories'	=> array('id' => 1),
				'Items'					=> array('code' => '04')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
					'document_category' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
					'items' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'DocumentCategories' => [
					'fields' => ['id', 'title', 'description'],
				],
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
				'Items' => [
					'fields' => ['id', 'document_id', 'code'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
					'document_category' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
				)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'DocumentCategories' => [
					'fields' => ['id', 'title', 'description'],
				],
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$this->Document->associations()->remove('Item');
		$this->Document->hasMany('Item');

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'DocumentCategories' => [
					'fields' => ['id', 'title', 'description'],
				],
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0,
				)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
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
					'id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0,
					'metadata' => array('id' => 5, 'document_id' => 5, 'weight' => 4, 'size' => 790, 'permissions' => 'rw-rw-r--'),
				)
			);

		$oldConditions = $this->Document->associations()->get('Metadata')->getConditions();
		$this->Document->associations()->get('Metadata')->setConditions(['Metadata.size > 500']);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$this->Document->associations()->get('Metadata')->setConditions(['Metadata.size > 500']);
		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$this->Document->associations()->get('Metadata')->setConditions($oldConditions);
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
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'DocumentCategories.id'	=> array('type' => 'select'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?'),
				'Items.code'			=> array('type' => 'text'),
			);

		$this->_reattachBehavior($testOptions);

		$filterValues = array
			(
				'Documents'				=> array('title' => 'in', 'is_private' => 0),
				'DocumentCategories'	=> array('id' => 1),
				'Items'					=> array('code' => '04')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
					'document_category' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'items' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'DocumentCategories' => [
					'fields' => ['id', 'title', 'description'],
				],
				'Items' => [
					'fields' => ['id', 'document_id', 'code'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
					'document_category' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
				)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'DocumentCategories' => [
					'fields' => ['id', 'title', 'description'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
				)
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);
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
				'title'				=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'Items.code'		=> array('type' => 'text'),
				'Metadata.size'		=> array('type' => 'text', 'condition' => '='),
			);

		$filterValues = array
			(
				'Documents'			=> array('title' => 'in'),
				'Items'				=> array('code' => '04'),
				'Metadata'			=> array('size' => 45),
			);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
				)
			);

		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);

		$result = $this->Document->find()
			->select(['id', 'title'])
			->enableHydration(false)
			->toArray();
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
				'title'				=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'Items.code'		=> array('type' => 'text'),
				'Metadata.size'		=> array('type' => 'text', 'condition' => '='),
			);

		$filterValues = array
			(
				'Documents'			=> array('title' => 'in'),
				'Items'				=> array('code' => '04'),
				'Metadata'			=> array('size' => 45),
			);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
					'metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
					'items' => array
						(
							array('id' => 4, 'document_id' => 2, 'code' => 'The item #01'),
							array('id' => 5, 'document_id' => 2, 'code' => 'The item #02'),
							array('id' => 6, 'document_id' => 2, 'code' => 'The item #03'),
							array('id' => 7, 'document_id' => 2, 'code' => 'The item #04')
						)
				)
			);

		// containable first, filtered second
		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);
		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
				'Items' => [
					'fields' => ['id', 'document_id', 'code'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		// filtered first, containable second
		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);
		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
				'Items' => [
					'fields' => ['id', 'document_id', 'code'],
				],
			])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);
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
				'title'				=> array('type' => 'text', 'condition' => 'like', 'required' => true),
				'Items.code'		=> array('type' => 'text'),
				'Metadata.size'		=> array('type' => 'text', 'condition' => '='),
			);

		$filterValues = array
			(
				'Documents'			=> array('title' => 'in'),
				'Items'				=> array('code' => '04'),
				'Metadata'			=> array('size' => 45),
			);

		$expected = array
			(
				array
				(
					'id' => 2,
					'title' => 'Imaginary Spec',
					'document_category_id' => 1,
					'owner_id' => 1,
					'is_private' => 0,
					'document_category' => array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
					'metadata' => array('id' => 2, 'document_id' => 2, 'weight' => 0, 'size' => 45, 'permissions' => 'rw-------'),
					'items' => array
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
				'alias' => 'FilterItems',
				'type' => 'INNER',
				'conditions' => 'Documents.id = FilterItems.document_id',
			);

		$this->_reattachBehavior($testOptions);
		$this->Document->setFilterValues($filterValues);
		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->contain([
				'DocumentCategories' => [
					'fields' => ['id', 'title', 'description'],
				],
				'Metadata' => [
					'fields' => ['id', 'document_id', 'weight', 'size', 'permissions'],
				],
				'Items' => [
					'fields' => ['id', 'document_id', 'code'],
				],
			])
			->join($customJoin)
			->enableHydration(false)
			->toArray();
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
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategories.id'	=> array('type' => 'select'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?', 'default' => 0)
			);
		$this->_reattachBehavior($testOptions);


		$filterValues = array
			(
				'DocumentCategories'	=> array('id' => 2),
				'Documents'				=> array('title' => '')
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0)
			);

		$result = $this->Document->find('all', ['nofilter' => true])
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
		$this->assertNotEquals($expected, $result);

		$result = $this->Document->find('all', ['nofilter' => 'true'])
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);
	}

	/**
	 * Test bailing out if no settings exist for the current model.
	 *
	 * @return void
	 */
	public function testExitWhenNoSettings()
	{
		$this->Document->DocumentCategories->addBehavior('Filter.Filtered');

		$Filtered = $this->Document->DocumentCategories->behaviors()->get('Filtered');
		$this->assertFalse(isset($Filtered->settings[$this->Document->DocumentCategories->getAlias()]));

		$filterValues = array
			(
				'DocumentCategories'	=> array('id' => 2)
			);
		$this->Document->DocumentCategories->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 1, 'title' => 'Testing Doc', 'description' => 'It\'s a bleeding test doc!'),
				array('id' => 2, 'title' => 'Imaginary Spec', 'description' => 'This doc does not exist'),
				array('id' => 3, 'title' => 'Nonexistant data', 'description' => 'This doc is probably empty'),
				array('id' => 4, 'title' => 'Illegal explosives DIY', 'description' => 'Viva la revolucion!'),
				array('id' => 5, 'title' => 'Father Ted', 'description' => 'Feck! Drink! Arse! Girls!'),
			);

		$result = $this->Document->DocumentCategories->find('all', ['nofilter' => 'true'])
			->select(['id', 'title', 'description'])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);

		$this->Document->DocumentCategories->removeBehavior('Filtered');
	}

	/**
	 * Test beforeDataFilter() callback, used to cancel filtering if necessary.
	 *
	 * @return void
	 */
	public function testBeforeDataFilterCallbackCancel()
	{
		$Document = $this->getTableLocator()->get('Document2', ['className' => Documents2Table::class]);
		$this->assertInstanceOf(Documents2Table::class, $Document);
		$this->Document = $Document;
		$testOptions = array
			(
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategories.id'	=> array('type' => 'select'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);


		$filterValues = array
			(
				'DocumentCategories'	=> array('id' => 2)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0,),
				array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 3, 'title' => 'Nonexistant data', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1),
				array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0),
				array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0),
				array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0),
			);

		$result = $this->Document->find()
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
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
		$Document = $this->getTableLocator()->get('Document3', ['className' => Documents3Table::class]);
		$this->assertInstanceOf(Documents3Table::class, $Document);
		$this->Document = $Document;
		$this->Document->itemToUnset = 'FilterDocumentCategories.id';

		$testOptions = array
			(
				'Documents.title'		=> array('type' => 'text', 'condition' => 'like'),
				'DocumentCategories.id'	=> array('type' => 'select'),
				'Documents.is_private'	=> array('type' => 'checkbox', 'label' => 'Private?')
			);
		$this->_reattachBehavior($testOptions);


		$filterValues = array
			(
				'DocumentCategories'	=> array('id' => 2)
			);
		$this->Document->setFilterValues($filterValues);

		$expected = array
			(
				array('id' => 1, 'title' => 'Testing Doc', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 2, 'title' => 'Imaginary Spec', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 3, 'title' => 'Nonexistant data', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 0),
				array('id' => 4, 'title' => 'Illegal explosives DIY', 'document_category_id' => 1, 'owner_id' => 1, 'is_private' => 1),
				array('id' => 5, 'title' => 'Father Ted', 'document_category_id' => 2, 'owner_id' => 2, 'is_private' => 0),
				array('id' => 6, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0),
				array('id' => 7, 'title' => 'Duplicate title', 'document_category_id' => 5, 'owner_id' => 3, 'is_private' => 0),
			);

		$result = $this->Document->find('all')
			->select(['id', 'title', 'document_category_id', 'owner_id', 'is_private'])
			->enableHydration(false)
			->toArray();
		$this->assertEquals($expected, $result);
	}
}
