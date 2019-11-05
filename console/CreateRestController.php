<?php namespace Voilaah\RestApi\Console;

use October\Rain\Scaffold\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use October\Rain\Support\Str;
use Config;

class CreateRestController extends GeneratorCommand
{
    /**
     * @var string The console command name.
     */
    protected $name = 'create:restapi';

    /**
     * @var string The console command description.
     */
    protected $description = 'Creates a new RESTful controller.';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'RESTful Controller';

    /**
     * A mapping of stub to generated file.
     *
     * @var array
     */
    protected $stubs = [
        'stubs/config_rest.stub'   => 'http/api/{{lower_name}}/config_rest.yaml',
        'stubs/controller.stub'    => 'http/api/{{studly_name}}.php',
        // 'stubs/routes.stub'        => 'routes.php',
        'stubs/transformer.stub'   => 'classes/transformers/{{studly_model}}Transformer.php'
    ];

    /**
     * Prepare variables for stubs.
     *
     * return @array
     */
    protected function prepareVars()
    {

        $pluginCode = $this->argument('plugin');

        $parts = explode('.', $pluginCode);
        $plugin = array_pop($parts);
        $author = array_pop($parts);

        $controller = $this->argument('controller');

        /*
         * Determine the model name to use,
         * either supplied or singular from the controller name.
         */
        $model = $this->option('model');
        if (!$model) {
            $model = Str::singular($controller);
        }

        $transformer = $this->option('transformer');
        if (!$transformer) {
            $transformer = Str::singular($controller);
        }

        $this->addRoutes($plugin, $author, $controller);

        return [
            'name' => $controller,
            'model' => $model,
            'transformer' => $transformer,
            'author' => $author,
            'plugin' => $plugin
        ];
    }

    /**
     *  Add routes to routes file.
     */
    protected function addRoutes($plugin, $author, $controller)
    {
        $routeStubPath = plugins_path() . '/' . Config::get('voilaah.restapi::route_stub' , 'route_stub');

        $stub = $this->constructStub($routeStubPath, $plugin, $author, $controller);

        $routesFile = plugins_path() . '/' . $author . '/' . $plugin . '/routes.php';

        // read file
        $lines = file($routesFile);
        $lastLine = trim($lines[count($lines) - 1]);

        // modify file
        if (strcmp($lastLine, '});') === 0) {
            $lines[count($lines) - 1] = '    '.$stub;
            $lines[] = "\r\n});\r\n";
        } else {
            $lines[] = "$stub\r\n";
        }

        // save file
        $fp = fopen($routesFile, 'w');
        fwrite($fp, implode('', $lines));
        fclose($fp);
        $this->info('Routes added successfully.');
    }

    /**
     * Get stub content and replace all stub placeholders
     * with data from $this->stubData.
     *
     * @param string $path
     *
     * @return string
     */
    protected function constructStub($path, $plugin, $author, $name)
    {
        $stub = $this->files->get($path);

        $stub = str_replace("{{lower_plural_name}}", strtolower($name), $stub);
        $stub = str_replace("{{studly_author}}", $author, $stub);
        $stub = str_replace("{{studly_plugin}}", $plugin, $stub);
        $stub = str_replace("{{studly_name}}", $name, $stub);

        return $stub;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['plugin', InputArgument::REQUIRED, 'The name of the plugin to create. Eg: RainLab.Blog'],
            ['controller', InputArgument::REQUIRED, 'The name of the controller. Eg: Posts'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Overwrite existing files with generated ones.'],
            ['model', null, InputOption::VALUE_OPTIONAL, 'Define which model name to use, otherwise the singular controller name is used.'],
            ['transformer', null, InputOption::VALUE_OPTIONAL, 'Define which transformer name to use, otherwise the singular controller name is used.'],
        ];
    }
}
