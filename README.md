# minPHP #

[![Build Status](https://travis-ci.org/phillipsdata/minphp.svg?branch=master)](https://travis-ci.org/phillipsdata/minphp)

minPHP is an extremely lightweight MVC framework for PHP application development.

## Requirements ##

* PHP 5.1.3 or greater

## Getting Started ##

1. Extract all the contents of the /src/ directory to a publically accessible web directory.
2. Load the web directory in your browser.
3. If your webserver does not support .htaccess files, delete the .htaccess file in the /src/ directory. You will need to access all URIs with a preceding "index.php/".

### Controllers, Views, and URIs ###

**Controllers** are PHP class files that handle URI requests. Each controller and controller method (known as an **action**) represent a URI segment. For example, the "Foo" controller can be accessed at ```/foo/```. This would automatically invoke the ```Foo::index()``` method. This method could explicitly be invoked using the ```/foo/index/``` URI. Similarly, the "bar" method of Foo can be accessed at ```/foo/bar/```. 

Each **View** is linked to a specific action. That is, each controller method has its own view. A view is simply a PHP Data Template (.pdt file), which typically contain HTML and PHP. The view for ```/foo/index/``` would be ```foo.pdt```. The view for ```/foo/bar/``` would be ```foo_bar.pdt```. Views are located in the ```/src/app/views/default/``` directory.

Controllers are initialized with two view objects. One for the action's view and another called "structure". The structure view (```/src/app/views/default/structure.pdt```) contains the content used in all views.

### Passing Variables to Views ###

Passing variables to views from a controller is simple:

```php
<?php
class Foo extends AppController
{

	public function index()
    {
	
		$my_var = array(1,2,3);
	
		$this->set("my_var", $my_var);
	}

}

```

You can also set multiple variables all at once:

```php
<?php
class Foo extends AppController
{

	public function index()
    {
	
		$my_var = array(1,2,3);
		$my_other_var = array("a","b","c");
	
		$this->set(compact("my_var", "my_other_var"));
	}

}

```

To set variables in the structure view use:

```php
$this->structure->set("my_var", $my_var);
```

### Directory Structure ###

<pre>
/app
	/controllers 	- where all controllers are to be placed
	/models 		- where all models are to be placed
	/views			- where all views are to be placed
		/default	- a collection of related display components
			/css
			/images
			/javascript
/cache				- where cached views are stored (must be writable to use)
/components			- where components are placed
/config				- where configuration files are to be stored
/helpers			- where all helpers are located
/language			- each language has its own directory in here
	/en_us			- the default language directory
/lib				- where all core minPHP files are located
/plugins			- where all minPHP plugins are stored
/vendors			- where vendor code is placed (i.e. third party libraries)
</pre>