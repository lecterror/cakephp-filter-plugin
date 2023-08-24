# CakePHP Filter Plugin #

## About ##

Filter is a [CakePHP][] plugin which enables you to create filtering forms for your data
in a very fast and simple way, without getting in the way of paging, sorting and other
"standard" things when displaying data. It also remembers the filter conditions in a
session, but this can be turned off if undesirable.

It also features callback methods for further search refinement where necessary.

**IMPORTANT**: These instructions are for CakePHP 2.0. If you're using CakePHP 1.3.x
the correct path to unload the plugin is `app/plugins/filter/`. More importantly,
**if you're using CakePHP 1.3.x you should use the 1.3.x version of this plugin**,
not the latest version from GitHub.

## Usage ##

First, obtain the plugin. If you're using Git, run this while in your app folder:

	git submodule add git://github.com/lecterror/cakephp-filter-plugin.git app/Plugin/Filter
	git submodule init
	git submodule update

Or visit <http://github.com/lecterror/cakephp-filter-plugin> and download the
plugin manually to your `app/Plugin/Filter/` folder.

To use the plugin, you need to tell it which model to filter and which fields to use. For
a quick tutorial, visit <https://github.com/lecterror/cakephp-filter-plugin/wiki/Basic-usage>

* Ensure the plugin is loaded in `app/Config/bootstrap.php` by calling `CakePlugin::load('Filter');`

If you need more than this plugin provides by default, there are ways to customize it, see
this article: <https://github.com/lecterror/cakephp-filter-plugin/wiki/Advanced-usage>

In order to generate GET forms add `'type' => 'GET'` to the `filterForm()` or `beginForm()` options array.

## Synopsis

### Adapt your controller, in this example, we add ModelName.field to the filter list.

```php
class ExampleController extends AppController  
{  
   var $components = array('Filter.Filter');  
   
       var $filters = array(  
        'index' => array(  
            'ModelName' => array(
                'ModelName.field',  
            ),
        )
    );   
}
```

### Adapt the view :

In the app/View/Modelname/index.ctp
Before the table, insert :
```php
   <?php echo $this->Filter->filterForm('ModelName', array('legend' => 'Search')); ?>
```

## Contributing ##

If you'd like to contribute, clone the source on GitHub, make your changes and send me a pull request.
If you don't know how to fix the issue or you're too lazy to do it, create a ticket and we'll see
what happens next.

**Important**: If you're sending a patch, follow the coding style! If you don't, there is a great
chance I won't accept it. For example:

	// bad
	public function drink() {
		return false;
	}

	// good
	public function drink()
	{
		return true;
	}

## Licence ##

Multi-licensed under:

* MPL <http://www.mozilla.org/MPL/MPL-1.1.html>
* LGPL <http://www.gnu.org/licenses/lgpl.html>
* GPL <http://www.gnu.org/licenses/gpl.html>

[CakePHP]: http://cakephp.org/
