<?php
namespace ChemMVC;
// Class to house Controller/Action/Data Structure
class routingCatalyst{
    public $equation;
    private string $controller = DEFAULT_CONTROLLER;
    private string $action = DEFAULT_ACTION;
    private ?string $queryString = null;
    private ?array $parameters = null;

    public function __construct()
    {
        // Grab and parse current request's url
        $this->equation = "$_SERVER[REQUEST_URI]";
        $this->equation = parse_url($this->equation);

        // Pour some sugar on it
        $this->transmute();
    }

    private function transmute() : void
    {
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

    public function setController(string $controller) : void
    {
        $this->controller = $controller;
    }

    public function setAction(string $action) : void
    {
        $this->action = $action;
    }
    
    public function hasParameters() : bool
    {
        return (!is_null($this->parameters) && is_array($this->parameters) && !empty($this->parameters));
    }
    public function setParameters() : void
    {
        // Unify Get and Post params
        $params = \array_merge($_POST, $_GET);
        // Convert any JSON objects to 
        if(is_array($params) && !empty($params)){
            foreach ($params as $key => $value) {
                if(gettype($value) == 'string' && (utils::startsWith($value, '{"') || utils::startsWith($value, '['))) {
                    $temp = json_decode($value);
                    if(json_last_error() == JSON_ERROR_NONE) $params[$key] = $temp;
                }
            }
            $this->parameters = $params;
        }
        else $this->parameters = null;
    }

    public function parametersNeedCasting() : bool
    {
        $result = false;
        foreach($this->parameters as $p) if(gettype($p) == 'object'){ $result = true; break; }
        return $result;
    }

    public function getParameters() : array
    {
        return (is_array($this->parameters) ? $this->parameters : []);
    }

    public function setQueryString($query = null) : void
    {
        if(!is_null($query)){
            if(!is_null($this->queryString)) $this->queryString .= '&' . $query;
            else $this->queryString = $query;
        }else $this->queryString = $query;
    }

    public function getControllerPath() : string
    {
        return PROJECT_NAMESPACE . "\\" . CONTROLLER_NAMESPACE . "\\" . $this->controller."Controller";
    }

    public function getController() : string
    {
        return $this->controller;
    }

    public function getAction() : string
    {
        return $this->action;
    }
    
    public function getQueryString() : string
    {
        return $this->queryString;
    }

    public function getLocationString() : string
    {
        return '/' . $this->getController() . '/' . $this->getAction() . (($this->getQueryString() !== null) ? '?' . $this->getQueryString() : '');
    }
}
