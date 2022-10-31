<?php 

use helper\Response;

include_once 'helper/Response.php';

$response = new Response();

$response->internalServerError("Invalid url.")
?>