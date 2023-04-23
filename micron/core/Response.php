<?php
namespace core;
use Exception;

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
     * 
     * @param string $message
     * @param array|object $data
     * @param bool $state
     * @return never
     * 
     * Return a JSON response
     */
    private function responseHelper(string $message, array|object|null $data = null, bool $state = true){
        $this->result["result"]["state"] = $state;
        $this->result["result"]["description"] = $message;
        if($data === null){
            $this->result["data"] = [];
        }
        else{
            $this->result["data"] = $data;
        }
        echo json_encode($this->result);
        exit;
    }
    
    /**
     * 
     * @param string $message
     * @param array|object|null $data
     * @return void
     * 
     * This function set HTTP code 200 and send a JSON response with the $message parameter and the $data parameter.</br>
     * Is suggested use this response for GET, DELETE or PUT request.
     */
    public function success(string $message, array|object|null $data = null){
        http_response_code(200);
        $this->responseHelper($message, $data);
    }
    
    /**
     * 
     * @param string $message
     * @param array|object|null $data
     * @return never
     * This function set HTTP code 201 and send a JSON response with the $message parameter and the $data parameter.</br>
     * Is suggested use this response for POST request (creating new resource on DB).
     */
    public function created(string $message, array|object|null $data = null){
        http_response_code(201);
        $this->responseHelper($message, $data);
    }

    public function updated(string $message, array|object|null $data = null){
        http_response_code(204);
        $this->responseHelper($message, $data);
    }
    
    public function badRequest(string $message){
        http_response_code(400);
        $this->responseHelper($message, array(), false);
    }
    
    public function unhatorized(string $message){
        http_response_code(401);
        $this->responseHelper($message, array(), false);
    }
    
    public function forbidden(string $message){
        http_response_code(403);
        $this->responseHelper($message, array(), false);
    }
    
    public function notFound(string $message="Resource not found"){
       http_response_code(404);
       $this->responseHelper($message);
    }
    
    public function internalServerError(string $message = "Internal server error."){
        http_response_code(500);
        echo $message;
    }
    
    public function notImplemented(string $message = "Method not implemented."){
        http_response_code(501);
        echo $message;
    }
    
    /**
     * @param string $message
     * @param array|object|null $data
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
    public function response(string $message, array|object|null $data = null, bool $state = true, int $http_code = 200){
        $this->result["result"]["state"] = $state;
        $this->result["result"]["description"] = $message;
        if(count($data) > 0){
            $this->result["data"] = $data;
        }
        http_response_code($http_code);
        echo json_encode($this->result);
    }

    /**
     * Send a response and close the connection with client WHITOUT ending the script.
     *
     * @param string $text_for_response
     * @param bool $response_state
     * @param int $response_http_code
     * 
     * @return void
     * 
     */
    public function responseAndContinueScript(string $text_for_response, bool $response_state = true, int $response_http_code = 200){
        ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true); // optional
        ob_start();

        $this->response($text_for_response, array(), $response_state, $response_http_code);

        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush();
        flush();
        ob_end_clean();
    }

    /**
     * Summary of provideFile
     * 
     * Responds sending a file. The file can be sent with 'inline' or 'attachment' header.
     * @param string $filePath the file path on server file system.
     * @param bool $isAttachment if true the file will be handled like 'attachment' (the browser will download it), if false will be handled like 'inline' (suggested for html files)
     * @throws Exception this will occur when file not found.
     * @return void
     * 
     */
    public function provideFile(string $filePath, bool $isAttachment = true){

        //checking if file exist
        if(!is_file($filePath)){
            throw new Exception("File not found.", 404);
        }

        //get file extension reading the FS
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        //get file name reading the FS
        $filename = pathinfo($filePath, PATHINFO_FILENAME);
        //get the content type reading the FS
        $contentType = mime_content_type($filePath);

        //determine wath disposition use: attachment with filename is for download.
        $disposition = "inline";
        if($isAttachment){
            $disposition = "attachment;filename=\"{$filename}.{$extension}\"";
        }

        //setting the headers
        header("Content-type:{$contentType}");
        header('Content-Description: File Transfer');
        header("Cache-Control: no-cache, must-revalidate");
        header("Content-Disposition: {$disposition}");
        header("Content-Length: ".filesize($filePath));
        header('Pragma: public');
        flush();
        http_response_code(200);        
        readfile($filePath);
    }
}

