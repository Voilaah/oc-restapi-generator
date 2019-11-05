<?php namespace Voilaah\RestApi\Behaviors;

use Backend\Classes\ControllerBehavior;
use Db;
use Dingo\Api\Transformer\Adapter\Fractal;
use Exception;
use Lang;
use October\Rain\Database\ModelException;
use Request;
use Str;
use ValidationException;


/**
 * Rest Controller Behavior
 *
 * Adds REST features for working with backend models.
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['Mohsin.Rest.Behaviors.RestController'];
 *
 * @author Saifur Rahman Mohsin
 */
class RestController extends ControllerBehavior
{
    use \Backend\Traits\FormModelSaver;
    use \Voilaah\RestApi\Traits\RestTrait;


    const API_VERSION = 'v1';


    /**
     * @var Model The child controller that implements the behavior.
     */
    protected $controller;


    /**
     * @var Model The initialized model used by the rest controller.
     */
    protected $model;

    /**
     * @var String The prefix for verb methods.
     */
    protected $prefix = '';

    /**
     * {@inheritDoc}
     */
    protected $requiredProperties = ['restConfig'];

    /**
     * @var array Configuration values that must exist when applying the primary config file.
     * - modelClass: Class name for the model
     * - list: List column definitions
     */
    protected $requiredConfig = ['modelClass', 'allowedActions'];

    /**
     * Behavior constructor
     * @param Backend\Classes\Controller $controller
     */
    public function __construct($controller)
    {
        parent::__construct($controller);
        $this -> controller = $controller;

        /*
         * Build configuration
         */
        $this->config = $this->makeConfig($controller->restConfig, $this->requiredConfig);
        $this->config->modelClass = Str::normalizeClassName($this->config->modelClass);

        if(isset($this->config->prefix))
          $this->prefix = $this->config->prefix;

        $this->bootRestTrait();
    }

    /**
     * Creates a new instance of the model. This logic can be changed
     * by overriding it in the rest controller.
     * @return Model
     */
    public function createModelObject()
    {
        return $this->createModel();
    }
    /**
     * Creates a new instance of the model. This logic can be changed
     * by overriding it in the rest controller.
     * @return Model
     */
    public function createTransformerObject()
    {
        return $this->createTransformer();
    }

    /**
     * Display the records.
     *
     * @return Response
     */
    public function index()
    {
        $options = $this->config->allowedActions['index'];
        $relations =  isset($this->config->allowedActions['index']['relations']) ? $this->config->allowedActions['index']['relations'] : [];
        $page = Request::input('page', 1);
        $includes = Request::input('with', null);

        /*
         * Default options
         */
        extract(array_merge([
            'page'       => $page,
            'pageSize'    => 5
        ], $options));

        try {
            $model = $this->controller->createModelObject();
            $this->transformer = $this->controller->createTransformerObject();
            $model = $this->controller->extendModel($model) ?: $model;

            $this->parseIncludes(array_merge($relations, array_filter(explode(',', $includes))));

            $data = $model->with($relations)->paginate($pageSize, $page);

            return $this->respondWithCollection($data, $this->transformer, 200);

            // return $this->helper->apiArrayResponseBuilder(200, 'success', $collection);
            // return response()->json($model->with($relations)->paginate($pageSize, $page), 200);
        }
        catch (Exception $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
    }

    /**
     * Store a newly created record using post data.
     *
     * @return Response
     */
    public function store()
    {
        $data = Request::all();

        try {
            $model = $this->controller->createModelObject();
            $model = $this->controller->extendModel($model) ?: $model;

            $modelsToSave = $this->prepareModelsToSave($model, $data);
            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save();
            }

            return response()->json($model, 200);
        }
        catch(ModelException $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
        catch (Exception $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
    }

    /**
     * Display the specified record.
     *
     * @param  int  $recordId
     * @return Response
     */
    public function show($recordId)
    {
        $relations =  isset($this->config->allowedActions['show']['relations']) ? $this->config->allowedActions['show']['relations'] : [];
        $includes = Request::input('with', null);
        // merging
        $relations = array_merge($relations, array_filter(explode(',', $includes)));

        try {
            $model = $this->controller->findModelObject($recordId);
            $this->transformer = $this->controller->createTransformerObject();
            $this->parseIncludes($relations);

            // Get relations too
            foreach($relations as $relation)
              $model -> {$relation};

            return $this->respondWithItem($model, $this->transformer, 200);

            // return response()->json($model, 200);
        }
        catch(ModelException $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
        catch (Exception $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
    }

    /**
     * Update the specified record in using post data.
     *
     * @param  int  $recordId
     * @return Response
     */
    public function update($recordId)
    {
        $data = Request::all();

        try {
            $model = $this->controller->findModelObject($recordId);

            $modelsToSave = $this->prepareModelsToSave($model, $data);
            foreach ($modelsToSave as $modelToSave) {
                $modelToSave->save();
            }

            return response()->json($model, 200);
        }
        catch(ModelException $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
        catch (Exception $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
    }

    /**
     * Remove the specified record.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($recordId)
    {
        try {
            $model = $this->controller->findModelObject($recordId);
            $model -> delete();
            return response()->json($model, 200);
        }
        catch(ModelException $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
        catch (Exception $ex) {
            return response()->json($ex -> getMessage(), 400);
        }
    }

    /**
     * Finds a Model record by its primary identifier, used by show, update actions.
     * This logic can be changed by overriding it in the rest controller.
     * @param string $recordId
     * @return Model
     */
    public function findModelObject($recordId)
    {
        if (!strlen($recordId)) {
            throw new Exception('Record ID/slug has not been specified.');
        }

        $model = $this->controller->createModelObject();

        /*
         * Prepare query and find model record
         */
        $query = $model->newQuery();
        // deprecated
        // $result = $query->find($recordId);
        $result = $query->where('id', $recordId)
                        ->orWhere('slug', $recordId)
                        ->first();

        if (!$result) {
            throw new Exception(sprintf('Record with an ID/slug of %u could not be found.', $recordId));
        }

        $result = $this->controller->extendModel($result) ?: $result;

        return $result;
    }

    /**
     * Internal method, prepare the model object
     * @return Model
     */
    protected function createModel()
    {
        $class = $this->config->modelClass;
        return new $class();
    }

    /**
     * Internal method, prepare the transformer object
     * @return Model
     */
    protected function createTransformer()
    {
        $class = $this->config->transformerClass;
        return new $class();
    }

    public function getController()
    {
        return $this->controller;
    }

    /* Functions to allow RESTful actions */
    public static function getAfterFilters() {return [];}
    public static function getBeforeFilters() {return [];}
    public static function getMiddleware() {return [];}
    public function callAction($method, $parameters = false) {
      $action = Str::camel($this -> prefix . ' ' . $method);
      if (method_exists($this->controller, $action) && is_callable(array($this->controller, $action)) && array_key_exists($method, $this->config->allowedActions))
      {
        return call_user_func_array(array($this->controller, $action), $parameters);
      }
      else if (method_exists($this, $action) && is_callable(array($this, $action)) && array_key_exists($method, $this->config->allowedActions))
      {
        return call_user_func_array(array($this, $action), $parameters);
      }
      else
      {
        return response()->json([
            'response' => 'Not Found',
        ], 404);
      }
    }

    /**
     * Extend supplied model, the model can
     * be altered by overriding it in the controller.
     * @param Model $model
     * @return Model
     */
    public function extendModel($model)
    {
    }
}
