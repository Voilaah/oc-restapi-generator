<?php

return [

    /*
     * Relative path from the app directory to api controllers directory.
     */
    'controllers_dir'  => 'Api/Controllers',

    /*
     * Relative path from the app directory to transformers directory.
     */
    'transformers_dir' => 'Api/Transformers',

    /*
     * Relative path from the app directory to the api routes file.
     */
    'routes_file'      => 'Api/routes.php',

    /*
     * Relative path from the app directory to the models directory. Typically it's either 'Models' or ''.
     */
    'models_base_dir'  => '',

    /*
     * Relative path from the base directory to the api controller stub.
     */
    'controller_stub'  => 'voilaah/restapi/console/stubs/controller.stub',

    /*
     * Relative path from the base directory to the route stub.
     */
    'route_stub'       => 'voilaah/restapi/console/stubs/route.stub',

    /*
     * Relative path from the base directory to the transformer stub.
     */
    'transformer_stub' => 'voilaah/restapi/console/stubs/transformer.stub',
];