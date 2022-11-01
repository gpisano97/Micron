# Micron - PHP API REST Framework
A small and usefull PHP Api REST framework.

## Table of contents
* [General info](#general-info)
* [Technologies](#technologies)
* [Setup](#setup)
* [Utilization](#utilization)
* [Api](#api)


# General info

This framework allow you to build **API Rest using PHP** in a very easy way. It provides also usefull helper class, like DataHelper or Database that allow you to connect and make prepared query to MySql database in a very fast and simple way. There is also a library for user authentication with **JWT Token**. **All the framework classes throws php exceptions on error, so is strongly recommended using "try-catch" block for wrap your code.** Micron has an internal PHP routing class and this provides an easy way for **build readble URI**. It supports all HTTP method (following **REST guidelines**) and provides all the **response** cases (according to HTTP Code) in **JSON** format thanks to Response Class. Routing uses anonymous function for execute your code, so is required to put your code in a function to be run.

# Technologies
* PHP 7.4.
* PDO for Database interaction.
* OOP.
* Functional Programming.

# Setup
* clone the repo.
* In `.htaccess` file put your "index file" location. I recommend keeping the default setting.
* In `config.php` file put your database information.
* In `JWT/config.php` put your jwt secret key.

# Utilization
Be inspierd by `api` folder, when you clone the repo this folder contains a working database-less example for auth, GET and POST request.
Micron is very easy to use, follow this simple steps:

* in `api` folder, create your resource folder and a PHP file with a function (or more like you prefer) inside. If you want to use URI parameters don't forget to define the function parameter like an empty array (`function example(array $params = []){}`)
* Make sure to require or inlcude the DataHelper and Response classes.
* Create a response object from Response class, this provides all methods for JSON responses.
* Wrap your code with a `try-catch` block, this will help you to manage errors. 
