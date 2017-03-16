<?php
namespace ffan\dop;

/**
 * Class MockPlugin
 * @package ffan\dop
 */
class MockPlugin
{
    /**
     * @var ProtocolManager
     */
    private $manager;
    
    /**
     * MockPlugin constructor.
     * @param ProtocolManager $manager
     */
    public function __construct(ProtocolManager $manager)
    {
        $this->manager = $manager;
    }
}
