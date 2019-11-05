# [RESTful-Plugin](https://github.com/Voilaah/oc-restful-api) #
Restful Api Generator plugin for October CMS

## Introduction ##

This plugin allows you to create RESTful controllers.

In a nutshell, it adds the following scaffold command.
```
php artisan create:restapi Acme.Plugin ControllerName
```

However, it does more than just that, you have the ability to override the api actions (verbs) with your own logic. The first version of the plugin is minimal and only provides default behavior for `index` verb. I will add default behavior logic for the more common verbs (create, store, show, edit, update, destroy) in the next update. Most RESTful APIs use custom logic and hide several fields so usually the behavior should be overridden than using the default behavior.

Feel free to make PRs to the github repository should you want to contribute. If you have feature requests use the issue tracker.

## Adding RESTful behavior to  controller (Without scaffolding) ##

If you prefer not to use the http folder and wanted all the REST API logic mixed in the same controller generated by the create:controller command you may do so. In order to use the REST behavior you should add it to the $implement property of the controller class. Also, the $restConfig class property should be defined and its value should refer to the YAML file used for configuring the behavior options.
```
namespace Acme\Blog\Controllers;

class Categories extends \Backend\Classes\Controller
{
    public $implement = ['Voilaah.RestApi.Behaviors.RestController'];

    public $restConfig = 'rest_config.yaml';
}
```

Next you must configure the RESTful behavior. The configuration file referred in the $restConfig property is defined in YAML format. The file should be placed into the controller's views directory. Below is an example of a typical RESTful behavior configuration file:
```
# ===================================
#  Rest Behavior Config
# ===================================

# Allowed Rest Actions
allowedActions:
  - store

# Model Class name
modelClass: Acme\Blog\Models\Post

# Verb Method Prefix
prefix: api
```

The prefix ensures that the controller actions do not collide with the existing actions of the controller. Example actions are index, update, preview which several behaviors such as the ListController and FormController behaviors use. The RESTful functions should not interfere with these methods so you can ensure this by using the prefix. So, in order to override the default logic you would write the method signature as the prefix followed by camel case verb name. For example, **public function apiStore()**.

Finally create a routes.php file. Here’s the general template:
```
<?php

Route::group(['prefix' => 'api/v1', 'namespace' => 'Acme\Blog\Http\Controllers'], function () {
    //
});
```
And it should work fine. I however recommend you to skip all these steps and instead use the scaffolding command, it’s much simpler.

## Thanks ##

#### October CMS ####
[Alexey Bobkov and Samuel Georges](http://octobercms.com) for OctoberCMS.
