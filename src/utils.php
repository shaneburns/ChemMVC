<?php
namespace ChemMVC;

/**
 * APIUtils : for all your generalized utility needs
 */
class utils {
    public static function GUID()
    {
        if (function_exists('com_create_guid') === true) return trim(com_create_guid(), '{}');
        else return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    public static function generateToken($length = 20){
        return bin2hex(random_bytes($length));
    }
    public static function hashToken($token = null){
        if(!is_null($token))
            $token = hash('sha384', $token, true);
        return $token;
    }
    public static function generateSelector($length = 9){
        return base64_encode(random_bytes($length));
    }
    public static function generateCode($length = 6) : string {
        return substr(md5(uniqid(mt_rand(), true)) , 0, $length);
    }
    public static function addMinutesToDateTime(\DateTimeImmutable $date = null, int $minutesToAdd = 0) : \DateTimeImmutable
    {
        if(!is_null($date))
            $date =  $date->add(new \DateInterval('PT' . $minutesToAdd . 'M'));
        return $date;
    }
    public static function addDays(\DateTimeImmutable $date = null, int $daysToAdd = 0) : \DateTimeImmutable
    {
        if(!is_null($date))
            $date =  $date->add(new \DateInterval('P' . $daysToAdd . 'D'));
        return $date;
    }
    public static function convertObjectClass($object, $final_class) { 
        return unserialize(sprintf( 
            'O:%d:"%s"%s', 
            strlen($final_class), 
            $final_class, 
            strstr(strstr(serialize($object), '"'), ':') 
        )); 
    }
    public static function classCast(object $start, object $final) {
        foreach($start as $property => $value) { 
            if(property_exists($final, $property))
                $final->$property = $value; 
        } 
        return $final;
    }
    public static function compareObjectProperties(object $suspect, object $expected) : ?bool
    {
        if(count((array)$suspect) != count((array)$expected) ) return false;
        foreach($suspect as $prop => $val) if(!property_exists($expected, $prop)) return false;
        return true;
    }
    public static function startsWith ($string, $startString) 
    { 
        $len = strlen($startString); 
        return (substr($string, 0, $len) === $startString); 
    } 
}
