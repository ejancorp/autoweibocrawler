<?php
/**
 * AutoWeibo Library For Automatic Weibo Login Via CURL & NodeJs
 *
 * Weibo client version 1.4.18
 *
 * I got no resources only an old not working scripts.
 *
 *
 * =============================================================================
 * *
 * @author     Ej Corpuz @ mobext.ph Aug 11 2014
 * @version    1.1.0
 *
 *  Note: Needs NodeJs server to run the hashing script. @ node/server.js
 *
 *	$request = new Auto_Weibo();
 *	$testRequest =
 *  $request->initWeibo('username','password','ssoclient','http://localhost:1337');
 *	print $testRequest;
 *
 *  @since Aug. 11, 2014(ReWrite Date)
 *
 *
 */

class Auto_Weibo {

    private $cookiepath = '';
    private $logdebug = FALSE;

    //ssologin version default
    protected $client = '1.4.18';
    //the node js server that handles the Weibo SSO hash script
    protected $hashserver = 'http://dev3.mobext.ph:1337/?';
    protected $targetPage = "";
    protected $username;
    protected $password;

    public function __construct($username = NULL, $password = NULL, $client = NULL, $hashserver = NULL, $targetPage = NULL) {

        /*
         * Instantiate Variables / Properties
         */

        $this -> username = $username;
        $this -> password = $password;

        $this -> client = $client;
        $this -> hashserver = $hashserver;

        $this -> cookiepath = sys_get_temp_dir() . '/' . $username . '.cookie.txt';
        $this -> hashserver = ($hashserver == '' ? $this -> hashserver : $hashserver);

        //ssologin version
        $client = ($client == NULL || $client == '' ? $this -> client : $client);

        $this -> targetPage = ($targetPage != NULL || $targetPage != '' ? $targetPage : trigger_error('No Page URL Declared. - AutoWeibo() Class', E_USER_ERROR));

    }

    public function initWeibo() {

        $username = $this -> username;
        $password = $this -> password;
        $client = $this -> client;
        $hashserver = $this -> hashserver;
        $pageUrl = $this -> targetPage;

        //checkHashServer
        if (!$this -> checkServer($this -> hashserver)) {
            die();
        }

        //get prelogin url
        $getPrelogin = $this -> preloginUrl($username, $client);
        $requestPrelogin = $this -> requestCurl($getPrelogin, 'get');

        //get important variables
        $vars = $this -> importVars($requestPrelogin, $password);

        //get hashed password
        $hpass = $this -> hashPass($vars);
        $requestHash = $this -> requestCurl($hpass, 'get');

        //start login
        $sendLogin = $this -> loginSend($vars, $username, $requestHash, $client);
        //return $sendLogin;

        $replace_url_str = strpos($sendLogin, "location.replace('") + strlen("location.replace('");
        $replace_url = substr($sendLogin, $replace_url_str);
        $replace_url_1 = strpos($replace_url, "'");
        $replace_url = substr($replace_url, 0, $replace_url_1);

        $auto = $replace_url;

        //return scraped html type page
        $saveRun = $this -> requestCurl($auto, 'get', NULL, 1, 0, TRUE);
        return $saveRun;

    }

    /* get prelogin url function */
    private function preloginUrl($username = '', $client = '') {
        if ($username == '' && $client == '') {
            return FALSE;
        }

        $url = 'http://login.sina.com.cn/sso/prelogin.php?entry=weibo&callback=sinaSSOController.preloginCallBack&su=' . base64_encode($username) . '&rsakt=mod&client=ssologin.js(v' . $client . ')';
        return $url;
    }

    /* extract important variables servertime,nonce,rsakv,pubkey */
    private function importVars($result, $password = '') {

        if (!$result) {
            return FALSE;
        }

        preg_match('/"servertime":([0-9]+)/', $result, $servertime);
        preg_match('/"nonce":"([a-zA-Z_0-9]+)"/', $result, $nonce);
        preg_match('/"rsakv":"([a-zA-Z_0-9]+)"/', $result, $rsakv);
        preg_match('/"pubkey":"([a-zA-Z_0-9]+)"/', $result, $pubkey);

        return array(
            'servicetime' => $servertime[1],
            'nonce' => $nonce[1],
            'rsakv' => $rsakv[1],
            'rsapubkey' => $pubkey[1],
            'pwd' => $password
        );
    }

    /* hash user password */
    private function hashPass($params) {
        if ($params) {

            $url = $this -> hashserver . http_build_query($params);
            return $url;

        }
        else {
            return false;
        }
    }

    /* inititate weibo login */
    private function loginSend($params, $username, $hpwd, $client = '') {

        $loginparam = array(
            'encoding' => 'UTF-8',
            'entry' => 'weibo',
            'from' => '',
            'gateway' => '1',
            'nonce' => $params['nonce'],
            'prelt' => '111',
            'pwencode' => 'rsa2',
            'returntype' => 'META',
            'rsakv' => $params['rsakv'],
            'savestate' => '7',
            'servertime' => $params['servicetime'],
            'service' => 'miniblog',
            'url' => $this -> targetPage,
            'useticket' => '1',
            'vsnf' => '1',
            'su' => base64_encode($username),
            'sp' => $hpwd,
        );

        $loginurl = "http://login.sina.com.cn/sso/login.php?client=ssologin.js(" . $client . ")";
        return $this -> requestCurl($loginurl, 'post', $loginparam);
    }

    /* run curl */
    private function requestCurl($url, $type = 'get', $data = array(), $withcookie = 0, $optheader = 0, $follow = TRUE) {

        //global $this->cookiepath;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HEADER, $optheader);
        curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla / 5.0 (X11; Ubuntu; Linux i686; rv: 28.0) Gecko / Firefox 20100101 / 28.0');
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $follow);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, 2000);
        if ($withcookie) {
            curl_setopt($curl, CURLOPT_COOKIESESSION, true);
            if ($withcookie == 2)
                curl_setopt($curl, CURLOPT_COOKIEJAR, $this -> cookiepath);
            else
                curl_setopt($curl, CURLOPT_COOKIEFILE, $this -> cookiepath);
        }
        if (strtolower($type) == 'post') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_URL, $url);

        $curlexec = curl_exec($curl);

        if ($this -> logdebug) {
            $this -> debug(date("ymdhis"), $curlexec);
        }

        return $curlexec;
    }

    /* for logging purposes */
    private function debug($file_name, $data) {
        file_put_contents('debug/' . $file_name . '.txt', $data);
    }

    private function checkServer($url) {

        $res = get_headers($url);
        if (strpos($res[0], "200")) {
            return TRUE;
        }
        else {
            return FALSE;
        }

    }

}

/* end class ej@mobextph2014 */
