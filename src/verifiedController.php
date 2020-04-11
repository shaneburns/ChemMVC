<?php
namespace ChemMVC;
use MVCutils\mvcutils;
use core\Daos\RequestaccesscodeDao as RequestaccesscodeDao;
use core\Daos\VerificationtokenDao as VerificationtokenDao;
/**
 *
 */
class verifiedController extends controller
{
    function __construct($chem = null)
    {
        parent::__construct($chem, false);
        // Check for presence of verification token immediately
        if(isset($_COOKIE['verify'])){// verify it
            if($this->validateVerification()){
                if(isset($_GET['origRequest']) && isset($_GET['controller']) && isset($_GET['action'])) {
                    $this->chem->catalyst->setQueryString(null);
                    $this->redirectToControllerAction($_GET['controller'], $_GET['action']);
                }
                //else $this->redirectToSplashScreen();// check if original request parameters are present else go to security index splash screen
                else $this->invokeAction();
                die();
            }
        }else if(isset($_COOKIE['request'])){// check if the request is valid
            if($this->validateRequest()){ // request is valid
                $this->verifyAccess(); // create verification token and store in database and cookie
                echo true;
                // if(isset($_GET['origRequest']) && isset($_GET['controller']) && isset($_GET['action'])) $this->redirectToControllerAction($_GET['controller'], $_GET['action']);
                // else $this->redirectToSplashScreen();// check if original request parameters are present else go to security index splash screen
            }else if(!isset($_POST['accesscode'])){
                $this->verificationRedirect();
            }
        }else{
            // No verification or access requests found
            $this->requestAccess();
            // redirect proper verification page accordingly
            $this->verificationRedirect();
        }

    }
    public function verificationRedirect()
    {
        if($this->chem->catalyst->getController() === DEFAULT_VERIFICATION_CONTROLLER
            && $this->hasAction()){ // Already on the verification controller at a valid action
                $this->invokeAction();
        }else{ // Let's redirect to the login
            $this->storeOrigRequest();
            // Send to the form
            $this->redirectToControllerAction(DEFAULT_VERIFICATION_CONTROLLER,DEFAULT_VERIFICATION_ACTION);
        }
    }

    public function storeOrigRequest()
    {
        if(isset($_GET['origRequest'])) return;
        // build array
        $data = array(
            'origRequest' => true,
            'controller' => $this->chem->catalyst->getController(),
            'action' => $this->chem->catalyst->getAction()
        );
        $queryString = \http_build_query($data);
        $this->chem->catalyst->setQueryString($queryString);
    }
    private function verifyAccess()
    {
        // create verification token
        $vtDao = new VerificationtokenDao($this->chem->config->tdbmService);
        $vt = $vtDao->createVerificationtoken();
        // create a cookie to validate request
        $this->createVerifyCookie($vt['bean']->getSelector(), $vt['token']);
    }
    private function requestAccess()
    {
        // create requess access code
        $racDao = new RequestaccesscodeDao($this->chem->config->tdbmService);
        $rac = $racDao->createRequestAccessCode();
        // create a cookie to validate request
        $this->createRequestCookie($rac->getSelector());
        // email us the code
        mvcutils::emailUsAccessCode($rac->getCode());
    }
    private function createRequestCookie($token)
    {
        if(!isset($_COOKIE['request']))
            \setcookie('request',$token, time() + (60 * 5), '/');
    }
    private function createVerifyCookie($selector, $token)
    {
        if(!isset($_COOKIE['verify']))
            \setcookie('verify', $selector.':'.$token , time() + (60 * 60 * 24), '/');
    }
    private function validateVerification()
    {
        // Seperate selector and $token
        $goods = $this->seperateVerificationCookie();
        // check the verification code is in the db and not expired and all that
        $vtDao = new VerificationtokenDao($this->chem->config->tdbmService);
        $vt = $vtDao->findBySelector($goods[0]);
        if(!is_null($vt)){
            if(\hash_equals($vt->getHashedValidator(), mvcutils::hashToken($goods[1]))){
                return true;
            }
        }
        return false;
                // User is good to go fam
        //
    }
    private function seperateVerificationCookie(){
        $goods = $_COOKIE['verify'];
        $goods = preg_split("#:#", $goods, 2, PREG_SPLIT_NO_EMPTY);
        return $goods;
    }
    private function validateRequest($selector = '')
    {
        if(isset($_POST['accesscode'])) $code = $_POST['accesscode'];
        else return false;
        // check the request code is in the db and not expired and all that
        $selector = $_COOKIE['request'];
        $racDao = new RequestaccesscodeDao($this->chem->config->tdbmService);
        $rac = $racDao->findBySelector($selector);
        if(!is_null($rac)){
            if($rac->getCode() == $code){
                return true;
            }
        }
    }

}
