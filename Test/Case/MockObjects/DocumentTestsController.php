<?php

App::uses('Controller', 'Controller');

class DocumentTestsController extends Controller
{
	public $name = 'DocumentTests';

	public function index()
	{
	}

	// must override this or the tests never complete..
	// @TODO: mock partial?
	public function redirect($url, $status = null, $exit = true)
	{
		return null;
	}
}
