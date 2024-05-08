<?php

namespace core;

/**
 * Summary of MiddlewareConfiguration
 * 
 * Set the middleware behavior for the request.
 * By default :
 * $tokenControl = true -> the middleware will check and validate a bearer token
 * $tokenBodyAuthorizedValues = [] -> the middleware will not check any token information
 * $acceptedContentType = ['application/json', 'text/json', 'none'] -> allowed request content type. The 'none' value is used for managing GET requests
 */
class MiddlewareConfiguration
{
    private bool $tokenControl;
    private bool $isRefreshToken;
    private array $tokenBodyAuthorizedValues = [];
    private array $acceptedContentType = ['application/json', 'text/json', 'none'];

    private array $CustomMiddlewaresObjects = [];


    /**
     * Summary of __construct
     * @param bool $tokenControl True value allow the middleware for checking and validate bearer token. False ignore the token.
     * @param bool $isRefreshToken True value allow the middleware for checking and validate the refresh token. False will throw an exception if a refresh token provided.
     * @param array $tokenBodyAuthorizedValues Allow to set some token body key and the reference value : e.g. ['level' => 'admin'] -> Will block every request where the token body key 'level' is not admin
     * @param array $acceptedContentType Allow to set the accepted content type for the request. IMPORTANT, for GET requests put inside 'none'. By Default 'none' is already in the array.
     * @param array $CustomMiddlewares Allow to add some middlewares function that will be executed after the default Middleware functionalities.
     */
    function __construct(bool $tokenControl = true, bool $isRefreshToken = false, array $tokenBodyAuthorizedValues = [], array $acceptedContentType = [], array $CustomMiddlewares = []){
        $this->tokenControl = $tokenControl;
        $this->tokenBodyAuthorizedValues = $tokenBodyAuthorizedValues;
        $this->isRefreshToken = $isRefreshToken;
        foreach ($CustomMiddlewares as $CustomMiddleware) {
            if(gettype($CustomMiddleware) !== "string"){
                throw new \Exception("CustomMiddleware must be an array of string containing class names!", 500);
            }
            $classImplementation = class_implements($CustomMiddleware); 
            if($classImplementation === false){
                throw new \Exception("CustomMiddleware class $CustomMiddleware not exist or is not required. Try require it with require_once.", 500);
            }
            else if( !in_array("core\MiddlewareConfiguration\ICustomMiddleware", $classImplementation) ){
                throw new \Exception("All Custom Middlewares must implement the core\MiddlewareConfiguration\ICustomMiddleware interface.");
            }

            $customMiddlewareObj = new $CustomMiddleware();
            array_push($this->CustomMiddlewaresObjects, $customMiddlewareObj); 
        }

        if(count($acceptedContentType) > 0){
            $this->acceptedContentType = $acceptedContentType;
        }
    }

	/**
     * Get the token control value.
     * 
	 * @return bool
	 */
	public function getTokenControl(): bool {
		return $this->tokenControl;
	}

    /**
     * Get the token control value.
     * 
	 * @return bool
	 */
	public function getIsRefreshToken(): bool {
		return $this->isRefreshToken;
	}

	/**
     * Get the token body authorized value.
     * 
	 * @return array
	 */
	public function getTokenBodyAuthorizedValues(): array {
		return $this->tokenBodyAuthorizedValues;
	}

	/**
     * Get Accepted content type
     * 
	 * @return array
	 */
	public function getAcceptedContentType(): array {
		return $this->acceptedContentType;
	}

    /**
     * Get Custom Middleware Objects
     * 
	 * @return array
	 */
	public function getCustomMiddlewaresObject(): array {
		return $this->CustomMiddlewaresObjects;
	}

    /**
     * Summary of getConfiguration
     * This function allow returning a MiddlewareConfiguration object without use the 'new' keyword
     * 
     * @param bool $tokenControl True value allow the middleware for checking and validate bearer token. False ignore the token.
     * @param bool $isRefreshToken True value allow the middleware for checking and validate the refresh token. False will throw an exception of InvalidToken if a refresh token provided.
     * @param array $tokenBodyAuthorizedValues Allow to set some token body key and the reference value : e.g. ['level' => 'admin'] -> Will block every request where the token body key 'level' is not admin
     * @param array $acceptedContentType Allow to set the accepted content type for the request. IMPORTANT, for GET requests put inside 'none'. By Default 'none' is already in the array.
     * @param array $CustomMiddlewares Allow to add some middlewares function that will be executed after the default Middleware functionalities.
     * @return MiddlewareConfiguration
     */
    static function getConfiguration(bool $tokenControl = true, bool $isRefreshToken = false, array $tokenBodyAuthorizedValues = [], array $acceptedContentType = [], array $CustomMiddlewares = []) : MiddlewareConfiguration{
        return new MiddlewareConfiguration($tokenControl, $isRefreshToken, $tokenBodyAuthorizedValues, $acceptedContentType, $CustomMiddlewares);
    }
}
