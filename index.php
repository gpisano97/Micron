<?php
use core\DataHelper\DataHelper;
use core\Media\FilesManager;
use core\Response;
use core\MiddlewareConfiguration;

require_once "micron/Micron.php";


$route = new Route();



$route->enableCORS();

try {

    $route->get("/", function (Request $request) {
        Response::instance()->success("Hello from Micron!!");
    }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false));

    /* $route->post("testfile", function (Request $request) {
        $fm = new FilesManager();
        $fm->Upload(fileId: $request->requestBody["id"], uploadedFile: $request->requestBody["file"], replaceIfPresent: true);
        $info = $fm->FileInfos(fileId: $request->requestBody["id"]);
        $fm->Download(fileId: $request->requestBody["id"], downloadName: "prova");
        //$fm->Delete(fileId: $request->requestBody["id"], fileName: $request->requestBody["file"]["name"]);
    }, middlewareSettings: MiddlewareConfiguration::getConfiguration(tokenControl: false, acceptedContentType: ["multipart/form-data"])); */

    $route->notFound("404.php");
} catch (\Throwable $th) {
    $response = new Response();
    $response->response($th->getMessage(), [], false, $th->getCode());
    exit;
}