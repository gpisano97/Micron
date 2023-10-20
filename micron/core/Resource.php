<?php

namespace core;
use Route;
use Request;

include_once 'micron/core/Request.php';
include_once 'micron/core/Route.php';

/**
 * Summary of Resource
 */
interface Resource {

    /**
     * Read resources from database. This function must end with a Response.
     * @param Request $request
     * @return void
     * 
     */
    //public function read(Request $request) : void;

    /**
     * Create a resource on the database. This function must end with a Response.
     * @param Request $request
     * @return void
     */
    //public function create(Request $request) : void;

    /**
     * Update a resource on the database. This function must end with a Response.
     * @param Request $request
     * @return void
     */
    //public function update(Request $request) : void;
    
    /**
     * Delete a resource from the database. This function must end with a Response.
     * @param Request $request
     * @return void
     */
    //public function delete(Request $request) : void;
    
    /**
     * This function will handle the routes of the resource. Put inside all listening endpoint (including endpoint linked to CRUD operations).
     * Use this function in the same way you use the Micron entry point (so by default the index.php file).
     * @param Route $router
     * @return void
     */
    public function listen(Route $router) : void;
}