<?php
namespace ChemMVC;
//use COREmodels\StatisticsModels;

class controller{
    public $chem;
    public $bond;
    public $result;

    function __construct($chem = null, $invokeAction = true){
        if($chem == null) die();
        \set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // error was suppressed with the @-operator
            if (0 === error_reporting()) {
                return false;
            }
            
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
        $this->chem = $chem;
        if($invokeAction) $this->result = $this->invokeAction();
        \restore_error_handler();
    }
    public function hasAction()
    {
        return method_exists($this, $this->chem->catalyst->getAction());
    }

    public function getResult(){
        return $this->result;
    }

    public function invokeAction(){
        // Check if the action exists
        if($this->hasAction()){
            try {
                // invoke the action
                if($this->chem->catalyst->hasParameters()) return $this->{$this->chem->catalyst->getAction()}(...$this->getParameters());
                else return $this->{$this->chem->catalyst->getAction()}();
            } catch (\Exception $e) {
                // TODO: Log this error stat dude...
                echo $e;
            }
        }else{
            // 404 response
            // TODO: CREATE A FREAKING 404 RESPONSE BROOO
            $result = new result(null, 404);
            return $result;
        }
    }
    // public function getParameters(){
    //     $this->chem->catalyst->parametersNeedCasting() ? $this->castAndMapParamter() : $this->mapParameters()
    // }
    public function getParameters()
    {
        $requestParams = $this->chem->catalyst->getParameters();
        $params = array();
        $action = $this->chem->catalyst->getAction();
        $valid = false;
        $method = new \ReflectionMethod($this, $action);
        $methodParams = $method->getParameters();


        foreach( $methodParams as $key => $methodParam){
            if(isset($requestParams[$methodParam->name])){
                $pType = $methodParam->getType()->__toString();
                $rpType = gettype($requestParams[$methodParam->name]);
                $rpType = ($rpType == 'bool' ? 'boolean' : ($rpType == 'float' ? 'double' : ($rpType == 'int' ? 'integer' : $rpType)));
                if($rpType === 'object' 
                    && $methodParam->getClass() !== null 
                    && gettype($methodParam->getClass()) === 'object'
                    && !$methodParam->getClass()->isInternal()){
                        $instance = $methodParam->getClass()->newInstance(); // create a new instance
                        if(!utils::compareObjectProperties($requestParams[$methodParam->name], $instance)){ // do a full compare
                            
                            break; // somin ain't right here
                        }
                        try{
                            $params[$methodParam->name] = utils::classCast($requestParams[$methodParam->name], $instance); // cast that ish
                        }catch(\Exception $e){
                            // TODO: Bad Mapping -> log this error stat dude...
                            
                            break;
                        }
                }else if($pType == $rpType && $rpType != null){ // check basic type matching
                    $params[$methodParam->name] = $requestParams[$methodParam->name];
                    continue; // check basic class types
                }
                
                    
            }
            else if(!$methodParam->allowsNull()) continue; // handle this
            
        }
        // $this->mapParameters($params, $methodParams, $valid);

        // if(!$valid && $skippedCount == 0) {
        //     $result = new Result([], 404);
        //     $result->display();
        //     die();
        // }
        return array_values($params);
    }
    // public function mapParameters(&$params, &$fParams, &$valid){
    //     foreach($params as $key => ){// loop through those params
    //         $pType = gettype($params[$i]);
    //         $fpType = $fParams[$i]->getType()->__toString();
    //         $fpType = ($fpType == 'bool' ? 'boolean' : ($fpType == 'float' ? 'double' : ($fpType == 'int' ? 'integer' : $fpType)));
    //         if($pType == 'object' && $fParams[$i]->getClass() !== null && gettype($fParams[$i]->getClass()) == $pType){// check types for objects
    //             if(!$fParams[$i]->getClass()->isInternal()){// see if it's not an internal class
    //                 $instance = $fParams[$i]->getClass()->newInstance(); // create a new instance
    //                 if(!utils::compareObjectProperties($params[$i], $instance)){ // do a full compare
    //                     $valid = false;
    //                     break; // somin ain't right here
    //                 }
    //                 try{
    //                     $params[$i] = utils::classCast($params[$i], $instance); // cast that ish
    //                 }catch(\Exception $e){
    //                     // TODO: Bad Mapping -> log this error stat dude...
    //                     $valid = false;
    //                     break;
    //                 }
    //             }
    //         }else if($pType != $fpType && $fpType != null){ // check basic type matching
    //             $valid = false;
    //             break; // check basic class types
    //         }
    //         $valid = true;
    //     }
    // }

    function fileContents($controller, $action){
        $path = ROOT.ds."views".ds.$controller.ds.$action.".php";
        $fileContent = null;
        try{
            $fileContent = html_entity_decode(file_get_contents($path));
        }catch(\Exception $e){
            $caseType = preg_match('~^\p{Lu}~u', $controller) ? 'upper' : 'lower';
            if($caseType == 'upper') list($fileContent, $path) = $this->fileContents(\lcfirst($controller), $action);
            else list($fileContent, $path) = $this->fileContents(\ucfirst($controller), $action);
        }
        return array($fileContent, $path);
    }

    function view(){
        //$stat = new StatisticsModels($this->chem->config->tdbmService);
        try {
            list($fileContent, $path) = $this->fileContents($this->chem->catalyst->getController(), $this->chem->catalyst->getAction());
            if($fileContent == null) return new result(["request" => "failed"], 404);
            $this->bond = new sequence($fileContent, $this->chem->config->bundleConfig);
            if($this->bond->hasLogic()) $this->bond->evalLogic();
            else if($this->bond->hasLogicalView()) include($path);
            else if($this->bond->hasView()) $this->bond->displayView();

        } catch (\Exception $e) {
            echo $e;
        }
    }

    public function redirectToAction($actionName = '') : void
    {
        if(!empty($actionName)){
            $this->chem->catalyst->setAction($actionName);
            $this->redirect();
        }
    }
    public function redirectToControllerAction($controllerName = '', $actionName = '') : void
    {
        if(!empty($controllerName) && !empty($actionName)){
            $this->chem->catalyst->setController($controllerName);
            $this->chem->catalyst->setAction($actionName);
            //$this->chem->loadController(true);
            $this->redirect();
        }
    }

    public function redirect($newLocation = null, $statusCode = 303) : void
    {
        // Build location string
        if(is_null($newLocation))
            $newLocation = $this->chem->catalyst->getLocationString();
        header("Location: " . $newLocation, true, $statusCode);
        die();
    }
}
