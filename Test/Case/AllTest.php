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
App::uses('CakePlugin', 'Core');

class AllFilterTests extends CakeTestSuite
{
	/**
	 * @return \CakeTestSuite
	 */
	public static function suite()
	{
		$suite = new CakeTestSuite('All FilterPlugin tests');

		$suite->addTestDirectoryRecursive(CakePlugin::path('Filter').'Test'.DS.'Case');

		return $suite;
	}
}
