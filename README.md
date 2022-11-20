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
* Wrap your code with a `try-catch` block, this will help you to manage errors. Every exception throw by the helper classes containts the relative HTTP code, so (just see the example file) in the `catch` section put this code `$response->response($e->getMessage(), array(), false, $e->getCode() );` this will send the exception relative JSON response.
* in the `try` section put your resource code, make your database code and don't forget to take the token if required (`$token = DataHelper::getToken();`) and verify it (`JWT::verify($token)`);
* in `index.php` write your route: create an object from Route class (already done in the code) and use his methods to define the route. All methods accept 3 parameters:
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
Provides useful methods for retriving data.

| Name | Parameters | Description | Return value |
| ---- | ---------- | ----------- | ---------------- |
| `postGetBody()` | `none` | read the data upcoming in the body from the php input | `array` |
| `getToken()` | `none` | read the token from the upcoming headers | `string` |
| `checkParameters()` | `array<ParamKey> $keys` => this array must contains all the body key to check, every array's item is an instance of `ParamKey Class` <br /> `array $requestBody` => the request body (take this with `postGetBody()` | Check if the incoming parameters key is present and if respect the setted constraints | `bool` |

### Route
Provides routing method, use this for build your paths.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `get()` | `string $route` => path for reach the resource <br />  `function $callback` => function to be executed <br /> `array $header` => headers setted by resource in form of `"header" => "value"` | define a route with GET HTTP method. | `void` |
| `post()` | `string $route` => path for reach the resource <br />  `function $callback` => function to be executed <br /> `array $header` => headers setted by resource in form of `"header" => "value"` | define a route with POST HTTP method. | `void` |
| `put()` | `string $route` => path for reach the resource <br />  `function $callback` => function to be executed <br /> `array $header` => headers setted by resource in form of `"header" => "value"` | define a route with PUT HTTP method. | `void` |
| `delete()` | `string $route` => path for reach the resource <br />  `function $callback` => function to be executed <br /> `array $header` => headers setted by resource in form of `"header" => "value"` | define a route with DELETE HTTP method. | `void` |
| `notFound()` | `string $path` => path of the file to be included | attach a file that manage the "resource not found" case. | `void` |

### Response
Provides an useful set of JSON responses with preconfigured HTTP code or completely configurable JSON response.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `success()` | `string $message` => message that will be displayed in response JSON <br /> `array $data` => data that will be returned in response JSON | display a JSON response and set the HTTP Code 200 | `void` |
| `created()` | `string $message` => message that will be displayed in response JSON <br /> `array $data` => data that will be returned in response JSON | display a JSON response and set the HTTP Code 201 | `void` |
| `updated()` | `string $message` => message that will be displayed in response JSON <br /> `array $data` => data that will be returned in response JSON | display a JSON response and set the HTTP Code 204 | `void` |
| `badRequest()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 400, set "state key" in response to `false` | `void` |
| `unhatorized()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 401, set "state key" in response to `false` | `void` |
| `forbidden()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 403, set "state key" in response to `false` | `void` |
| `notFound()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 404, set "state key" in response to `false` | `void` |
| `internalServerError()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 500, set "state key" in response to `false` | `void` |
| `notImplemented()` | `string $message` => message that will be displayed in response JSON | display a JSON response and set the HTTP Code 501, set "state key" in response to `false` | `void` |
| `response()` | `string $message` => message that will be displayed in response JSON <br /> `array $data` => data that will be returned in response JSON <br /> `bool $state` => boolean rappresentation of call success <br /> `int $http_code` => HTTP code to be setted | display a JSON response configured with given parameters | `void` |

### JWT (JSON Web Token)
Class that manage JWT tokens. All the methods throw Exceptions on errors. The exception code will be the HTTP Code relative to the error occured.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `JWT()` | `array $body` => body of the token, usually filled with user informations <br/> `string $secret_key` => optional, by default the value putted in `config.php` <br/> `int $hours_before_expire = 24` => optional, 24 hours by default, this paramters will set the token expiration | Constructor of JWT class, this will create and configure a JWT Bearer Token. | `JWT Object` |
| `getToken()` | `none` | Return the JWT Token created by the constructor. | `string` |
| `getBody()` | `none` | Return the body of the token. | `array` |
| `decode()` | `string $token` => a JWT Token to verify and decode <br /> `string $secret_key = JWT_SECRET` => optional, set the secret key for decode the token | Verify and decode the given JWT Token and return the body. | `array` |
| `verify()` | `string $token` => a JWT Token to verify and decode <br /> `string $secret_key = JWT_SECRET` => optional, set the secret key for decode the token | Verify the given JWT Token. | `bool` |

### Database
Simple class for manage MySQL Database connection and interaction. Based on PDO.

| Name | Prameters | Description | Return value |
| ---- | --------- | ----------- | ---------------- |
| `getConnection()` | `none` | Enstabilish a Database connection with given configuration in `config.php` and return a PDO Object. Throw exceptions on errors. | `PDO Object` |
| `ExecQuery()` | `string $query_string` => an SQL query. You can put inside params (`:param` notation) and the function automatically prepare the query with value passed in second argument. <br /> `array $params` => params for query preparation. This is an associative array, the key is the param name and the value is the value to be binded in the query. | Execute a query on database. The query can be prepared or not. Throw exception on error. | `PDO Statement Object` |
| `ExecTransaction()` | `array $queries` => array of strings, every string is a query of transaction.<br/> `array $params` => an array of array, every array must contains query preparation params. Can be empty. | Begin a transaction and make the queries from index 0 to last.If there are some queries without parameters, put at corresponding index in $params array an empty array. | `bool` |

## Inspiration 
The `navigate` private function is inspired by a source code read on [Help in coding](https://helpincoding.com), i have modified it and passed from "inlcuding file" to "anonymous functions".  

## About me
I'm an Italian Full-stack Web Developer since 2019 with **TacoSoft s.r.l**. and a student of **Politecnico di Torino**, in this moment i'm having fun with **ReactJS**, but i started with **PHP** and I can't leave him. Follow me on my [Linked-In page](https://www.linkedin.com/in/girolamo-dario-pisano-375aa514b/) to be updated with some other awesome project!
