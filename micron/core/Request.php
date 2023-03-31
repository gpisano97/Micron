<?php


class Request
{
    public string $uri;
    public string $method;
    public string $headers;
    public array $URIparams;
    public array $requestBody;
    public array $authTokenBody;
    public array $queryParams;

    public function __construct(string $uri, string $method, array $URIparams = [], array $requestBody = [], array $authTokenBody = [], array $queryParams = []){
        $this->uri = $uri;
        $this->method = $method;
        $this->URIparams = $URIparams;
        $this->requestBody = $requestBody;
        $this->authTokenBody = $authTokenBody;
        $this->queryParams = $queryParams;
    }
}
