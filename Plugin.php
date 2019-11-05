<?php namespace Voilaah\RestApi;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

     public function register()
    {
        $this->registerConsoleCommand('create.restapi', 'Voilaah\RestApi\Console\CreateRestController');
    }
}
