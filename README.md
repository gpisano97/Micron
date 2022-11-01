# Micron - PHP API REST Framework
A small and usefull PHP Api REST framework.

## Table of contents
* [General info](#general-info)
* [Technologies](#technologies)
* [Setup](#setup)
* [Utilization](#utilization)
* [API](#api)


## General info

This framework allow you to build **API Rest using PHP** in a very easy way. It also provides usefull helper class, like DataHelper or Database that allow you to connect and make prepared query to MySql database in a very fast and simple way. There is also a library for user authentication with **JWT Token**. **All the framework classes throws php exceptions on error, so is strongly recommended using "try-catch" block for wrap your code.** Micron has an internal PHP routing class and this provides an easy way for **build readble URI**. It supports all HTTP method (following **REST guidelines**) and provides all the **responses** cases (according to HTTP Code) in **JSON** format thanks to Response Class. Routing uses anonymous function for execute your code, so is required to put your code in a function to be run.

## Technologies
* PHP 7.4.
* PDO for Database interaction.
* OOP.
* Functional Programming.

## Setup
* clone the repo.
* In `.htaccess` file put your "index file" location. I recommend keeping the default setting.
* In `config.php` file put your database information.
* In `JWT/config.php` put your jwt secret key.

## Utilization
Be inspierd by `api` folder, when you clone the repo this folder contains a working database-less example for auth, GET and POST request.
Micron is very easy to use, follow this simple steps:

* in `api` folder, create your resource folder and a PHP file with a function (or more like you prefer) inside. If you want to use URI parameters don't forget to define the function parameter like an empty array (`function example(array $params = []){}`)
* Make sure to require or inlcude the DataHelper and Response classes.
* Create a response object from Response class, this provides all methods for JSON responses.
* Wrap your code with a `try-catch` block, this will help you to manage errors. Every exception throw by the helper classes containts the relative PHP code, so (just see the example file) in the `catch` section put this code `$response->response($e->getMessage(), array(), false, $e->getCode() );` this will send the exception relative JSON response.
* in the `try` section put your resource code, make your database code and don't forget to take the token if required (`$token = DataHelper::getToken();`) and verify it (`JWT::verify($token)`);
* in `index.php` write your route: create an object from Route class (already done in the code) and use his method to define the route. All methods accept 3 parameters:
  1. `string $route` -> URI of the resources, can accept multiple parameters in bracket (example `product/{id}` in the `$params` array you will find a key `id` with         the correct value: `product/1` -> $params["id"] will contain `1`.
  2. `$callback` -> this parameter has to be an anonymous function, and will be a function defined in php files in `api` folder. If you want to use parameters don't         forget the `$params` array.
  3. `array $header` put here your headers. There are preconfigured array but don't worry, you can use what header you prefer. The form has to be "header" => "value"
      for example : `Access-Control-Allow-Origin : *` in the array will be `"Access-Control-Allow-Origin" => "*"`.
* Now define the route according to the desired parameters, (check the example in source code, is very clear). `Route class` has a method for every HTTP method, and you can repeat the same URI with a different method: `$route->get("example", function(){ myGETFunction();});` and `$route->post("example", function(){myPOSTFunction();});` will be two different paths!
* Is strongly reccomended to follow the REST guidelines in the routes definition:
  1. `$route->get()` -> retrive resource.
  2. `$route->post()` -> insert resource.
  3. `$route->put()` -> modify resource.
  4. `$route->delete()` -> delete resource.
  
* **Enjoy Micron and check the API section for other useful method!**

## API

### DataHelper

## Inspiration 
The `navigate` private function is inspired by a source code read on [Help in coding](https://helpincoding.com), i have modified it and passed from "inlcuding file" to "anonymous functions".  
