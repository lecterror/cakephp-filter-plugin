<?php

namespace Filter\Test\TestCase\MockObjects;

use Cake\Controller\Controller;

/**
 * @property \Filter\Test\TestCase\MockObjects\DocumentsTable $Document
 * @property \Filter\Controller\Component\FilterComponent $Filter
 */
class DocumentTestsController extends Controller
{
	/**
	 * @var mixed[]
	 */
	public $filters;

	/**
	 * {@inheritDoc}
	 *
	 * @see \Cake\Controller\Controller::initialize()
	 */
	public function initialize()
	{
		parent::initialize();
		/** @var \Filter\Test\TestCase\MockObjects\DocumentsTable $Table */
		$Table = $this->getTableLocator()->get('Documents', [
			'className' => DocumentsTable::class,
		]);
		$this->Document = $Table;
		$this->loadComponent('Filter.Filter');
	}

	/**
	 * @return void
	 */
	public function index()
	{
	}

	/**
	 * must override this or the tests never complete.
	 *
	 * @TODO: mock partial?
	 *
	 * @param string|mixed[] $url
	 * @param int|mixed[]|null|string $status
	 * @param bool $exit
	 * @return \Cake\Http\Response|null
	 */
	public function redirect($url, $status = null, $exit = true)
	{
		return null;
	}
}
