<?php

namespace core;
use Route;
use Request;

include_once 'core/Micron.php';

/**
 * Summary of Resource
 */
interface Resource {

    /**
     * Summary of read
     * @param Request $request
     * @return void
     * 
     * Read resources from database. This function must end with a Response.
     */
    public function read(Request $request) : void;
    /**
     * Summary of create
     * @param Request $request
     * @return void
     */
    public function create(Request $request) : void;
    /**
     * Summary of update
     * @param Request $request
     * @return void
     */
    public function update(Request $request) : void;
    /**
     * Summary of delete
     * @param Request $request
     * @return void
     */
    public function delete(Request $request) : void;
    
    /**
     * Summary of listen
     * @param Route $router
     * @return void
     */
    public function listen(Route $router) : void;
}