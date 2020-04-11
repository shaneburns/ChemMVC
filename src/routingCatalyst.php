<?php
namespace ChemMVC;
// Class to house Controller/Action/Data Structure
class routingCatalyst{
    public $equation;
    private $controller = DEFAULT_CONTROLLER;
    private $action = DEFAULT_ACTION;
    private $queryString = null;
    private $parameters = null;

    function __construct(){
        // Grab and parse current request's url
        $this->equation = "$_SERVER[REQUEST_URI]";
        $this->equation = parse_url($this->equation);
        // Pour some sugar on it
        $this->transmute();
    }

    function transmute(){

        if(isset($this->equation['path']) && !empty($this->equation['path'])){
            // Split the URL at every '/' and get the first two splits
            $this->components = preg_split("#/#", $this->equation['path'], 2, PREG_SPLIT_NO_EMPTY);
            if(count($this->components) > 0){
                if(isset($this->components[0])){// If a controller is set
                    // Set the controller
                    $this->setController(str_replace('/', '', $this->components[0]));
                }
                if(isset($this->components[1])){// If a action is set
                    // Set the action
                    $this->setAction(str_replace('/', '', $this->components[1]));
                }
                // ?? Arguments details ??
                $this->setParameters();
            }
        }
        if(isset($this->equation['query'])) $this->setQueryString($this->equation['query']);
    }
    function setController(string $controller){
        $this->controller = $controller;
    }
    function setAction(string $action){
        $this->action = $action;
    }
    
    function hasParameters(){
        return (!is_null($this->parameters) && is_array($this->parameters) && !empty($this->parameters));
    }
    function setParameters(){
        // Unify Get and Post params
        $params = \array_merge($_POST, $_GET);
        // Convert any JSON objects to 
        if(is_array($params) && !empty($params)){
            foreach ($params as $key => $value) {
                if(gettype($value) == 'string' && utils::startsWith($value, '{"')) {
                    $temp = json_decode($value);
                    if(json_last_error() == JSON_ERROR_NONE) $params[$key] = $temp;
                }
            }
            $this->parameters = $params;
        }
        else $this->parameters = null;
    }
    function parametersNeedCasting(){
        $result = false;
        foreach($this->parameters as $p) if(gettype($p) == 'object')  $result = true;
        return $result;
    }
    function getParameters(){
        return (is_array($this->parameters) ? $this->parameters : []);
    }
    public function setQueryString($query = null)
    {
        if(!is_null($query)){
            if(!is_null($this->queryString)) $this->queryString .= '&' . $query;
            else $this->queryString = $query;
        }else $this->queryString = $query;
    }

    function getControllerPath(){
        return PROJECT_NAMESPACE . "\\" . CONTROLLER_NAMESPACE . "\\" . $this->controller."Controller";
    }
    function getController(){
        return $this->controller;
    }
    function getAction(){
        return $this->action;
    }
    public function getQueryString()
    {
        return $this->queryString;
    }
    public function getLocationString()
    {
        return '/' . $this->getController() . '/' . $this->getAction() . (($this->getQueryString() !== null) ? '?' . $this->getQueryString() : '');
    }
}
