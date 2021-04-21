<?php
namespace ChemMVC;
//use COREmodels\StatisticsModels;

class controller{
    public $chem;
    public $bond;
    public $result;

    public function __construct(chemistry $chem, bool $invokeAction = true){
        $this->chem = $chem;
        if($invokeAction) $this->result = $this->invokeAction();
    }
    public function hasAction()
    {
        return method_exists($this, $this->chem->catalyst->getAction());
    }

    public function getResult(){
        return $this->result;
    }

    protected function invokeAction(){
        // Check if the action exists
        if($this->hasAction()){
            try {
                // invoke the action
                if($this->chem->catalyst->hasParameters()) return $this->{$this->chem->catalyst->getAction()}(...$this->getParameters());
                else return $this->{$this->chem->catalyst->getAction()}();
            } catch (\Exception $e) {
                return $e;
            }
        }else{
            // 404 
            return new result(null, 404);
        }
    }
    
    protected function getParameters()
    {
        if(!$this->chem->catalyst->hasParameters()) return;
        $requestParams = $this->chem->catalyst->getParameters();
        $params = array();
        $action = $this->chem->catalyst->getAction();
        $valid = false;
        $method = new \ReflectionMethod($this, $action);
        $methodParams = $method->getParameters();

        foreach( $methodParams as $key => $methodParam){
            if(isset($requestParams[$methodParam->name])){
                $rpType = gettype($requestParams[$methodParam->name]);
                $rpType = ($rpType == 'bool' ? 'boolean' : ($rpType == 'float' ? 'double' : ($rpType == 'int' ? 'integer' : $rpType)));
                if($rpType === 'object' 
                    && $methodParam->getClass() !== null 
                    && gettype($methodParam->getClass()) === 'object'
                    && !$methodParam->getClass()->isInternal()){
                        $instance = $methodParam->getClass()->newInstance(); // create a new instance
                        if(!utils::compareObjectProperties($requestParams[$methodParam->name], $instance)){ // do a full compare
                            $this->result = new \Exception("Chemistry Controller Error: faulty mapping on request parameter '$methodParam->name.'");
                            break; // somin ain't right here
                        }
                        try{
                            $params[$methodParam->name] = utils::classCast($requestParams[$methodParam->name], $instance); // cast that ish
                        }catch(\Exception $e){
                            // TODO: Bad Mapping -> log this error stat dude...
                            $this->result = $e;
                            break;
                        }
                }else{ // check basic type matching
                    $params[$methodParam->name] = $requestParams[$methodParam->name];
                    continue;
                }
                
            }
            else if(!$methodParam->allowsNull()) continue; // handle this
            
        }
        
        return array_values($params);
    }

    protected function view(?string $alt = null){
        try {
            return new sequence($this->chem->catalyst->getController(), !is_null($alt) && is_string($alt) ? $alt : $this->chem->catalyst->getAction(), $this->chem->config->bundleConfig);
        } catch (\Exception $e) {
            return $e;
        }
    }

    protected function redirectToAction($actionName = '') : void
    {
        if(!empty($actionName)){
            $this->chem->catalyst->setAction($actionName);
            $this->redirect();
        }
    }
    protected function redirectToControllerAction($controllerName = '', $actionName = '') : void
    {
        if(!empty($controllerName) && !empty($actionName)){
            $this->chem->catalyst->setController($controllerName);
            $this->chem->catalyst->setAction($actionName);
            //$this->chem->loadController(true);
            $this->redirect();
        }
    }

    protected function redirect($newLocation = null, $statusCode = 303) : void
    {
        // Build location string
        if(is_null($newLocation))
            $newLocation = $this->chem->catalyst->getLocationString();
        header("Location: " . $newLocation, true, $statusCode);
        die();
    }
}
