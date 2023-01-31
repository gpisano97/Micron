<?php


class Node
{
    public $node;
    public int $depth;
    public bool $odd;
    public array $chidrens;

    /**
     * Summary of __construct
     * @param array|object $node
     * @param int $depth
     * @param bool $odd
     * @param array $chidrens
     */
    function __construct(array|object $node, int $depth, bool $odd, array $chidrens = []){
        $this->node = $node;
        $this->depth = $depth;
        $this->odd = $odd;
        $this->chidrens = $chidrens;
    }

    /**
     * Summary of addChildren
     * @param Node $node
     * @return void
     * 
     * Add a Node Children to the Node
     */
    public function addChildren(Node $node){
        array_push($this->chidrens, $node);
    }
}
