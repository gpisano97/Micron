<?php
namespace helper;

/**
 *
 * @author girol
 *        
 */
class Response
{
    private $result;

    /**
     *
     */
    public function __construct(){
        $this->result = array();
        $this->result["result"]["state"] = null;
        $this->result["result"]["description"] = "";
        $this->result["data"] = array();
    }
    
    
    /*
     * Http Code
     200 -> OK -> operazione eseguita con successo,
     201 -> CREATED => nuova risorsa, da lanciare dopo POST
     400 -> "BAD REQUEST" -> richiesta formulata male (es: parametri sbagliati ecc ecc)
     401 -> "UNAUTHORIZED" -> token errato o cose cos�
     403 -> "FORBIDDEN" -> richiesta corretta ma permessi insufficienti
     404 -> "NOT FOUND" -> risorsa non trovata -> es prodott/1 ma 1 non c'� nel database
     500 -> "INTERNAL SERVER ERROR" -> errore generico
     501 -> "NOT IMPLEMENTED" -> non implementata 
     */
    
    /**
     * @param string $messaggio
     * @param array $dati
     * @return array
     */
    public function EsitoPositivo($messaggio = "", array $dati = null){
        $this->result["risultato"]["esito"] = true;
        $this->result["risultato"]["descrizione"] = $messaggio;
        if($dati != null){
            $this->result["data"] = $dati;
        }
        return $this->result;
    }
    
    public function EsitoNegativo($messaggio = "", $codice_errore = "", $linea_errore = ""){
        $this->result["risultato"]["esito"] = false;
        $this->result["risultato"]["descrizione"] = $messaggio;
        if($codice_errore != ""){
            $this->result["risultato"]["codice"] = $codice_errore;
        }
        if($linea_errore != ""){
            $this->result["risultato"]["linea"] = $linea_errore;
        }
        unset($this->result["data"]);
        return $this->result;
    }
    
    private function responseHelper(string $message, array $data = [], bool $state = true){
        $this->result["result"]["state"] = $state;
        $this->result["result"]["description"] = $message;
        if(count($data) > 0){
            $this->result["data"] = $data;
        }
        echo json_encode($this->result);
    }
    
    public function success(string $message, array $data = []){
        http_response_code(200);
       
        $this->responseHelper($message, $data);
    }
    
    public function created(string $message, array $data = []){
        http_response_code(201);
        $this->responseHelper($message, $data);
    }

    public function updated(string $message, array $data = []){
        http_response_code(204);
        $this->responseHelper($message, $data);
    }
    
    public function badRequest($message){
        http_response_code(400);
        $this->responseHelper($message, array(), false);
    }
    
    public function unhatorized($message){
        http_response_code(401);
        $this->responseHelper($message, array(), false);
    }
    
    public function forbidden($message){
        http_response_code(403);
        $this->responseHelper($message, array(), false);
    }
    
    public function notFound($message="Resource not found"){
       http_response_code(404);
       $this->responseHelper($message);
    }
    
    public function internalServerError($message = "Internal server error."){
        http_response_code(500);
        echo $message;
    }
    
    public function notImplemented($message = "Method not implemented."){
        http_response_code(501);
        echo $message;
    }
    
    /**
     * @param string $message
     * @param array $data
     * @param bool $state
     * @param int $http_code
     * 
     *<br/><b> HTTP CODES </b>:<br/>
     *200 -> OK -> GET Request success.<br/>
     *201 -> CREATED => New resource addedd, usually POST response.<br/>
     *400 -> "BAD REQUEST" -> Wrong parameter or invalid input type.<br/>
     *401 -> "UNAUTHORIZED" -> Wrong token.<br/>
     *403 -> "FORBIDDEN" -> Good request and good token, but insufficent user permissions.<br/>
     *404 -> "NOT FOUND" -> resource not found, es: product/51 => id : 51 not in DB must have this response code.<br/>
     *500 -> "INTERNAL SERVER ERROR" -> Server error.<br/>
     *501 -> "NOT IMPLEMENTED" -> Correct route but under construction.<br/>
     */
    public function response(string $message, array $data = [], bool $state = true, int $http_code = 200){
        $this->result["result"]["state"] = $state;
        $this->result["result"]["description"] = $message;
        if(count($data) > 0){
            $this->result["data"] = $data;
        }
        http_response_code($http_code);
        echo json_encode($this->result);
    }
}

