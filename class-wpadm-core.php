<?php
require_once dirname(__FILE__) . '/class-wpadm-result.php';
require_once dirname(__FILE__) . '/class-wpadm-command.php';
require_once dirname(__FILE__) . '/modules/class-wpadm-command-context.php';
require_once dirname(__FILE__) . '/modules/class-wpadm-queue.php';
require_once dirname(__FILE__) . '/modules/class-wpadm-command-factory.php';


if (!class_exists('WPAdm_Core')) {
    class WPAdm_Core {

        /*
        * "прилетевший" POST-запрос от админки($_POST)
        * @var array
        */
        private $request = array();

        /*
        * публичный ключ для проверки подписи
        * @var string
        */
        private $pub_key;

        /*
        * Результат выполнения запроса
        * @var WPAdm_Result
        */
        private $result;

        private $plugin;


        public function __construct(array $request, $plugin = '') {
            $this->result = new WPAdm_Result();
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_ERROR);
            $this->request = $request;
            $this->plugin = $plugin;

            // авторизация запроса
            if (!$this->auth()) {
                return;
            };                          
            if ('connect' == $request['method']) {
                $this->connect();
            } elseif($obj = $this->getObject($request['method'], $request['params'])) {
                $this->result = $obj->getResult();
            } else {
                $this->result->setError('Неизветный метод "' . $request['method'] . '"');
            }
        }


        /**
        * Возвращает путь до папки временных файлов
        * @return string
        */
        static public function getTmpDir() {
            $tmp_dir = dirname(__FILE__) . '/tmp';
            self::mkdir($tmp_dir);
            file_put_contents($tmp_dir . '/index.php', '');
            return $tmp_dir;
        }

        /**
        * Возвращает путь до папки временных файлов
        * @return string
        */
        static public function getPluginDir() {
            return dirname(__FILE__);
        }




        /**
        * @param string $method
        * @param mixed $params
        * @return null|WPAdm_Method_Class
        */
        private function getObject($method, $params) {
            if (!preg_match("|[a-zA-Z0-9_]|", $method)) {
                //если в навзвании метода есть недопустимые символы
                return null;
            }
            $method = mb_strtolower($method);
            //            $class_file = dirname(__FILE__) . "/methods/class-wpadm-method-" . str_replace('_', '-', $method) . ".php";

            $class_file = dirname(dirname(__FILE__)) . '/wpadm_' . $this->plugin . "/methods/class-wpadm-method-" . str_replace('_', '-', $method) . ".php";
            if (file_exists($class_file)) {
                require_once $class_file;
                $tmp = explode('_', str_replace('-', '_', $method));
                foreach($tmp as $k=>$m) {
                    $tmp[$k] = ucfirst(strtolower($m));
                }
                $method = implode('_', $tmp);

                $class_name = "WPAdm_Method_{$method}";
                if (!class_exists($class_name)) {
                    $this->getResult()->setError("Class '$class_name' not found");
                    $this->getResult()->setResult(WPAdm_result::WPADM_RESULT_ERROR);
                    return null;
                }
                return new $class_name($params);
            }
            // если метод не потдерживается, то возвращаем Null
            return null;
            //        switch($method) {
            //            case 'stat':
            //                return new WPAdm_Method_Stat($params);
            //            case 'exec':
            //                require_once dirname(__FILE__) . '/class-wpadm-method-exec.php';
            //                return new WPAdm_Method_Exec($params);
            //            case 'posting':
            //                require_once dirname(__FILE__) . '/class-wpadm-method-posting.php';
            //                return new WPAdm_Method_Posting($params);
            //            default:
            //                // если метод не потдерживается, то возвращаем Null
            //                return null;
            //        }
        }




        private function connect() {
            add_option('wpadm_pub_key', $this->pub_key);
            $this->result->setResult(WPAdm_Result::WPADM_RESULT_SUCCESS);
        }



        /*
        * Авторизация запроса
        */
        private function auth() {
            $this->pub_key = get_option('wpadm_pub_key');
            if (empty($this->pub_key)) {
                if ('connect' == $this->request['method']) {
                    $this->pub_key = $this->request['params']['pub_key'];
                } else {
                    $this->getResult()->setError('Активируйте сайт на wpadm.com для работы плагинов.');
                    return false;
                }
            } elseif ('connect' == $this->request['method']) {
                if( $this->pub_key != $this->request['params']['pub_key'] ){
                    $this->getResult()->setError('Ошибка. Воспользуйтесь переподключением плагина.');
                    return false;
                }
            } elseif('queue_controller' == $this->request['method']) {
                //todo: проверить, что запустили сами себя
                return true;
            }

            $sign = md5(serialize($this->request['params']));
            //openssl_public_decrypt($this->request['sign'], $request_sign, $this->pub_key);
            $ret = $this->verifySignature($this->request['sign'], $this->pub_key, $sign);


            //$ret = ($sign == $request_sign);
            if (!$ret) {
                $this->getResult()->setError("Неверная подпись");
            }
            return $ret;
        }


        /**
        * Создаем папку
        * @param $dir
        */
        static public function mkdir($dir) {
            if(!file_exists($dir)) {
                mkdir($dir);
                //todo: права доступа
                file_put_contents($dir . '/index.php', '');
            }
        }

        /**
        * @return WPAdm_result result
        */
        public function getResult() {
            return $this->result;
        }


        public function verifySignature($sign, $pub_key, $text) {
            if (function_exists('openssl_public_decrypt')) {
                openssl_public_decrypt($sign, $request_sign, $pub_key);
                $ret = ($text == $request_sign);
                return $ret;
            } else {
                set_include_path(get_include_path() . PATH_SEPARATOR . self::getPluginDir() . '/modules/phpseclib');
                require_once 'Crypt/RSA.php';
                $rsa = new Crypt_RSA();
                $rsa->setEncryptionMode(CRYPT_RSA_ENCRYPTION_PKCS1);
                return ($rsa->decrypt($sign) == $text);
            }
        }

        /**
        * @param $sign
        * @param $request_sign
        * @param $pub_key
        */
        public function openssl_public_decrypt($sign, &$request_sign, $pub_key) {
            //openssl_public_decrypt($sign, $request_sign, $pub_key);

        }


        static public function log($txt, $class='') {
            $log_file = WPAdm_Core::getTmpDir() . '/log.log';
            file_put_contents($log_file, date("Y-m-d H:i:s") ."\t{$class}\t{$txt}\n", FILE_APPEND);
        }

        /**
        * Удаляет директорию со всем содержимым
        * @param $dir
        */
        static function rmdir($dir) {
            $files = glob($dir. '/*');
            foreach($files as $f) {
                if ($f == '..' or $f == '.') {
                    continue;
                }
                if (is_dir($f)) {
                    self::rmdir($f);
                }
                unlink($f);
            }
            rmdir($dir);
        }
    }
}
