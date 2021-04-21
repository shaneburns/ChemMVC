<?php
namespace ChemMVC;

class result  
{
    use Overloadable;

    protected $body;
    protected array $headers;
    protected int $status;

    public function __construct(...$args)
    {
        $this->format(...$args);
    }

    public function set(...$args) : void
    {
        $this->format($args);
    }

    public function getBody() : mixed
    {
        return $this->body; 
    }

    public function getStatus() : int
    {
        return $this->status; 
    }

    public function getHeaders() : array
    {
        return is_array($this->headers) ? $this->headers : array(); 
    }

    protected function format(...$args) : void
    {
        $this->overload($args, [
            function ($body, int $status, array $headers)
            {
                $this->body = !empty($body) && !is_null($body) ? $body : ['request'=> 'failed', 'message'=> 'No reaction found.'] ;
                $this->status =  0 <= $status < 99 ? 200 : $status;
                $this->headers = $headers;
            },
            function ($body){
                $this->format($body, 200, []);
            },
            function ($body, int $status){
                $this->format($body, $status, []);
            },
            function (\Exception $e){
                $this->format($e->getMessage(), 500, []);
            },
            function (\Exception $e, int $status){
                $this->format($e->getMessage(), $status, []);
            },
            function (\Exception $e, int $status, array $headers){
                $this->format($e->getMessage(), $status, $headers);
            },
            function (result $result){
                $this->format($result->getBody(), $result->getStatus(), $result->getHeaders());
                unset($result);
            },
            function (){
                $this->format($this->body ?? null, $this->status ?? 0, $this->headers ?? []);
            }
        ]);
    }

    protected function HTTPStatus() : object
    {
        $httpCodes = array(
            100 => 'HTTP/1.1 100 Continue',
            101 => 'HTTP/1.1 101 Switching Protocols',
            200 => 'HTTP/1.1 200 OK',
            201 => 'HTTP/1.1 201 Created',
            202 => 'HTTP/1.1 202 Accepted',
            203 => 'HTTP/1.1 203 Non-Authoritative Information',
            204 => 'HTTP/1.1 204 No Content',
            205 => 'HTTP/1.1 205 Reset Content',
            206 => 'HTTP/1.1 206 Partial Content',
            300 => 'HTTP/1.1 300 Multiple Choices',
            301 => 'HTTP/1.1 301 Moved Permanently',
            302 => 'HTTP/1.1 302 Found',
            303 => 'HTTP/1.1 303 See Other',
            304 => 'HTTP/1.1 304 Not Modified',
            305 => 'HTTP/1.1 305 Use Proxy',
            307 => 'HTTP/1.1 307 Temporary Redirect',
            400 => 'HTTP/1.1 400 Bad Request',
            401 => 'HTTP/1.1 401 Unauthorized',
            402 => 'HTTP/1.1 402 Payment Required',
            403 => 'HTTP/1.1 403 Forbidden',
            404 => 'HTTP/1.1 404 Not Found',
            405 => 'HTTP/1.1 405 Method Not Allowed',
            406 => 'HTTP/1.1 406 Not Acceptable',
            407 => 'HTTP/1.1 407 Proxy Authentication Required',
            408 => 'HTTP/1.1 408 Request Time-out',
            409 => 'HTTP/1.1 409 Conflict',
            410 => 'HTTP/1.1 410 Gone',
            411 => 'HTTP/1.1 411 Length Required',
            412 => 'HTTP/1.1 412 Precondition Failed',
            413 => 'HTTP/1.1 413 Request Entity Too Large',
            414 => 'HTTP/1.1 414 Request-URI Too Large',
            415 => 'HTTP/1.1 415 Unsupported Media Type',
            416 => 'HTTP/1.1 416 Requested Range Not Satisfiable',
            417 => 'HTTP/1.1 417 Expectation Failed',
            422 => 'HTTP/1.1 422 Unprocessable Entity',
            500 => 'HTTP/1.1 500 Internal Server Error',
            501 => 'HTTP/1.1 501 Not Implemented',
            502 => 'HTTP/1.1 502 Bad Gateway',
            503 => 'HTTP/1.1 503 Service Unavailable',
            504 => 'HTTP/1.1 504 Gateway Time-out',
            505 => 'HTTP/1.1 505 HTTP Version Not Supported',
        );
     
        array_push($this->headers, $httpCodes[$this->status]);
        \http_response_code($this->status);

        return (object) array(
            'code' => $this->status,
            'message' => $httpCodes[$this->status],
        );
    }

    public function display() : void
    {
        $status = $this->HTTPStatus();
        if(!empty($this->headers)) foreach($this->headers as $h ) header($h);

        if(\is_a($this->body, 'sequence')) $this->body->execute; die();

        if($this->status > 399 && (empty($this->body) || is_null($this->body)))  $this->body = ['request'=> 'failed', 'message'=> $status->message];
        if(gettype($this->body) === "object") echo \json_encode($this->body);
        else echo $this->body;
    }
}
