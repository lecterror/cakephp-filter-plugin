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

class AllFilterTests extends PHPUnit_Framework_TestSuite
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('All FilterPlugin tests');

		$suite->addTestFile(dirname(__FILE__).DS.'Controller'.DS.'Component'.DS.'FilterTest.php');
		$suite->addTestFile(dirname(__FILE__).DS.'Model'.DS.'Behaviors'.DS.'FilteredTest.php');

		return $suite;
	}
}
