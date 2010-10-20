# CakePHP Filter Plugin #

## About ##

Filter is a [CakePHP][] plugin which enables you to create filtering forms for your data
in a very fast and simple way, without getting in the way of paging, sorting and other
"standard" things when displaying data. It also remembers the filter conditions in a
session, but this can be turned off if undesirable.

It also features callback methods for further search refinement where necessary.

## Usage ##

First, obtain the plugin. If you're using Git, run this while in your app folder:

	git submodule add git://github.com/lecterror/cakephp-filter-plugin.git plugins/filter
	git submodule init
	git submodule update

Or visit <http://github.com/lecterror/cakephp-filter-plugin> and download the
plugin manually to your `app/plugins/filter/` folder.

To use the plugin, you need to tell it which model to filter and which fields to use. For
a quick tutorial, visit <http://lecterror.com/articles/view/cakephp-generic-filter-plugin>

I know, I should probably write an advanced usage article. For now, just deal with it.

## Contributing ##

If you'd like to contribute, clone the source on GitHub, make your changes and send me a pull request.
If you don't know how to fix the issue or you're too lazy to do it, create a ticket and we'll see
what happens next.

**Important**: If you're sending a patch, follow the coding style! If you don't, there is a great
chance I won't accept it. For example:

	// bad
	function drink() {
		return false;
	}

	// good
	function drink()
	{
		return true;
	}

## Licence ##

Multi-licensed under:

* MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
* LGPL <http://www.gnu.org/licenses/lgpl.html>
* GPL <http://www.gnu.org/licenses/gpl.html>

[CakePHP]: http://cakephp.org/
