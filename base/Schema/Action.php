<?php

namespace UiStd\Dop\Schema;

/**
 * Class Action
 * @package UiStd\Dop\Scheme
 */
class Action extends Node
{
    /**
     * @var string
     */
    private $request_model;

    /**
     * @var string
     */
    private $response_mode;

    /**
     * @param string $model
     */
    public function setRequestModel($model)
    {
        $this->request_model = $model;
    }

    /**
     * @param string $model
     */
    public function setResponseMode($model)
    {
        $this->response_mode = $model;
    }

    /**
     * @return string
     */
    public function getRequestModel()
    {
        return $this->request_model;
    }

    /**
     * @return string
     */
    public function getResponseModel()
    {
        return $this->response_mode;
    }
}
