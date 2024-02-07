<?php

//Database settings
define("DB_HOST", "localhost");
define("DB_DATABASE_NAME", "micron");
define("DB_USERNAME", "root");
define("DB_PASSWORD", "");
//MySQL -> mysql, PostgreSQL -> pgsql (you need to enable the driver in php.ini), MicrosoftSQL -> sqlsrv (you need to enable the driver in php.ini)
define("DB_TYPE", "mysql");

//JWT settings
define("JWT_SECRET", "secret key here");

define("MEDIA_BASE_PATH", $_SERVER["DOCUMENT_ROOT"]."/media");

//defines for Users class. Fill only if you want to use this feature. (Not implemented yet)
define("USER_LEVELS", "'ADMIN','STANDARD'"); //define the users levels

