<?php

//Database settings
define("DB_HOST", "");
define("DB_DATABASE_NAME", "");
define("DB_USERNAME", "");
define("DB_PASSWORD", "");
//MySQL -> mysql, PostgreSQL -> pgsql (you need to enable the driver in php.ini), MicrosoftSQL -> sqlsrv (you need to enable the driver in php.ini)
define("DB_TYPE", "mysql");
define("DB_PORT", "3306");

//JWT settings
define("JWT_SECRET", "secret key here");

define("MEDIA_BASE_PATH", $_SERVER["DOCUMENT_ROOT"]."/media");
