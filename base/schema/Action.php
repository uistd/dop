<?php

namespace FFan\Dop\Schema;

use FFan\Dop\Build\BuildOption;
use FFan\Dop\Exception;
use FFan\Dop\Manager;
use FFan\Dop\Protocol\ItemType;
use FFan\Std\Common\Utils as FFanUtils;
use FFan\Std\Common\Str as FFanStr;

/**
 * Class Action
 * @package FFan\Dop\Scheme
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
