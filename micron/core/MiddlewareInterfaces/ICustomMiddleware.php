<?php
namespace core\MiddlewareConfiguration;


require_once "micron/Micron.php";

interface ICustomMiddleware {
    public function run(\Request &$request) : void;
}