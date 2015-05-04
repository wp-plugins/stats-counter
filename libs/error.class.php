<?php
/**
* error 
*           
* 100 - mothod not exist
* 101 - error method api
* 102 - error in the received data 
* 103 - error 
* 
* notice 
* 401 - activate user 
* 
* success
* 201 - registaration and acivate ok
* 202 - acivate plugin ok
* 
*/
if ( ! class_exists("errorWPADM")) {
    class errorWPADM {
        private static $messages = array(
        100 => 'Method not Exist.',
        101 => 'Error in Method.',
        102 => 'Error in the received data.',
        103 => 'Error in activate plugin.',
        201 => 'Registaration and acivate were successful.',
        202 => 'Successful in activate plugin.',
        401 => 'Please activate user in <url>.',

        );
        public static function getMessage($code)
        {
            if (isset(self::$messages[$code])) {
                return self::$messages[$code]; 
            } else {
                return "Error in Server, received data is invalid.";
            }

        }
    }
}