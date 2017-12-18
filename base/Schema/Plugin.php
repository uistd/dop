<?php

namespace UiStd\Dop\Schema;

/**
 * Class Plugin
 * @package UiStd\Dop\Scheme
 */
class Plugin extends Node
{
    /**
     * @var string
     */
    private $name;

    /**
     * Plugin constructor.
     * @param string $name
     * @param \DOMElement $node
     */
    public function __construct($name, \DOMElement $node)
    {
        parent::__construct($node);
        $this->name = $name;
    }
}
