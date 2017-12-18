<?php

namespace UiStd\Dop\Schema;

use UiStd\Dop\Build\BuildOption;
use UiStd\Dop\Exception;
use UiStd\Dop\Manager;
use UiStd\Dop\Protocol\ItemType;
use UiStd\Common\Utils as FFanUtils;
use UiStd\Common\Str as FFanStr;

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
