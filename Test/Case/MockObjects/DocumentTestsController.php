<?php

App::uses('Controller', 'Controller');

class DocumentTestsController extends Controller
{
	public $name = 'DocumentTests';

	/**
	 * @var mixed[]
	 */
	public $filters;

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
	 * @return \CakeResponse|null
	 */
	public function redirect($url, $status = null, $exit = true)
	{
		return null;
	}
}
