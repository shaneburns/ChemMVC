# Chemistry MVC

A lightweight, highly use-case configurable, 
extensible, easy to integrate MVC framework with 
an implemented strict custom class mapping and casting process for 
controller action parameter data types(objects specifically)

## Installing

Install through composer with:

```
composer require mcshane/chem-mvc
```

## Start faster with a template

Just replace 'my-project' with the name of the repo you want to start

```
composer create-project eadrom/chemmvc-template my-project
```

Delete the newly installed composer files in 'my-project' root and cd to 'my-project'/app/ and run

```
composer install
```

The project structure is setup and composer dependencies installed. 
You're ready to start building your web project.

To the controllers.

## Usage

Chem MVC works off of a routing catalyst that assumes a url stucture of

```
/ControllerName/ActionName
```

Take the following url for example

```
https://my-site.cool/
```

With Chemistry MVC's default routing, this url would invoke the '/home/index' action. 'home' being the default controller name and 'index' being the default action name. In turn the above url would be equivilant to.

```
https://my-site.cool/home/index
```

While these routing behaviours can be altered, this will assume you keep them the same.

Traverse your project to my-project/app/controllers/homeController.php

It should look as follows from a fresh install of the template

```
<?php 
namespace app\controllers;
use ChemMVC\controller as Controller;

class homeController extends Controller
{
    function index(){
        return parent::view();
    }
}
```

Simply enough, this **homeController** Class has one **index** Action.  The index action calls the extended **ChemMVC\controller** class' **view** method. This method automagically locates the file ***my-project/app/views/home/index.php***.  This behaviour is possible for any action in any controller. Where controller name is the directory and the action name is the file name + '.php'

e.g. with homeController as
```
class homeController extends Controller
{
    function couldBeCooler(){
        return parent::view();
    }
}
```
***https://my-site.cool/home/couldBeCooler*** would process and return the result of ***my-project/app/views/home/couldBeCooler.php*** With no extra specification.


***More to Come***
Got any ideas for improvements? Fork this repo and make your art.
I haven't even talked about TDBM(linked below). 

## Depenedency List(and reasons why they are here):
* [BundleFu](https://github.com/dotsunited/BundleFu) - Packing JS and CSS files into bundles for individual pages, reducing load time from code base bloat(currently being deprecated in favor of webpack for JS and SASS)
* [TDBM](https://github.com/thecodingmachine/tdbm) - Modeling your database into usable php classes with CRUD operations, data structuring and data requirements built in.
