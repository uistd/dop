<?php

namespace UiStd\Dop\Schema;

/**
 * Class Shader
 * @package UiStd\Dop\Scheme
 */
class Shader extends Node
{
    /**
     * @var string[]
     */
    private $codes;

    /**
     * Shader constructor.
     * @param \DOMElement $node
     */
    public function __construct(\DOMElement $node)
    {
        parent::__construct($node);
        $this->parse($node);
    }

    /**
     * @param \DOMElement $node
     */
    private function parse($node)
    {
        $code_node_list = $node->childNodes;
        $num = 0;
        for ($i = 0; $i < $code_node_list->length; ++$i) {
            $code_node = $code_node_list->item($i);
            if (XML_ELEMENT_NODE !== $code_node->nodeType) {
                continue;
            }
            $num++;
            $node = new Node($code_node);
            $attributes = $node->getAttributes();
            $attributes['code_value'] = $code_node->nodeValue;
            $this->codes[] = $attributes;
        }
        if (0 === $num) {
            $this->codes[] = array('code_value' => $node->nodeValue);
        }
    }

    /**
     * @return string[]
     */
    public function getCodes()
    {
        return $this->codes;
    }
}
