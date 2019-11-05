<?php

namespace Voilaah\RestApi\Traits;

use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Illuminate\Routing\Controller;
use League\Fractal\Serializer\ArraySerializer;
use Response;
use October\Rain\Database\Model;
use Input;

/**
 *
 */
trait RestTrait {

    protected $fractal;
    protected $transformer;


    public function bootRestTrait()
    {
        $this->fractal = new Manager();
    }

    /**
     * Function to include relation data for showing purpose
     * @param $arRelation
     */
    protected function parseIncludes($arRelation) {
        $this->fractal->parseIncludes($arRelation);
    }

    protected function respondWithCollection($data, $callback = null, $statusCode = 200)
    {
        if ($data) {
            $resource = new Collection($data->items(), $callback);
            $resource->setPaginator(new IlluminatePaginatorAdapter($data));
            $rootScope = $this->fractal->createData($resource); // Transform data
            return $this->respondWithArray($rootScope->toArray(), $statusCode);
        }
        return $this->respondWithArray(["data" => []], $statusCode);
    }


    protected function respondWithItem($item, $callback, $statusCode)
    {
        if ($item) {

            $resource = new Item($item, $callback);
            $rootScope = $this->fractal->createData($resource);
            return $this->respondWithArray($rootScope->toArray(), ($statusCode)?:$this->$statusCode);

        }
        return $this::respondWithArray(["data" => []], 200);
    }


    protected  function respondWithArray(array $array, $statusCode = null, array $headers = [])
    {
        $array = array_merge(array_merge($this->getInformationApiRequest(), ["status" => "success"]), $array);
        return Response::json(
                $array,
                (isset($statusCode)) ? $statusCode : 500,
                $headers);
    }


    /**
     * Function to return an array of the version and the resource used in the api call
     * @return array
     * @throws \ReflectionException
     */
    private  function getInformationApiRequest() {
        try
        {
            $resource = $this->getController()->getResourceName();
            return [
                "version" => strtolower(self::API_VERSION),
                "resource" => strtolower($resource)
            ];
        } catch (\Throwable $e) {
            return [
                "version" => strtolower(self::API_VERSION),
                "resource" => "undefined"
            ];
        }
    }

}