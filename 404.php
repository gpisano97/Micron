<?php 

use core\Response;

require_once 'micron/Micron.php';

$response = new Response();

$response->internalServerError("Invalid url.")
?>