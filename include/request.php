<?php

class request {
    public $URL = "";
    public $cookieFile = "";
    public $errors = "";
    public $responseUrl = "";
    public $responseCode = false;
    public $responseHeaders = "";
    public $response = "";

    function __construct($URL){
        $this->URL = $URL;
        $this->cookieFile = createCookieFile();
    }

    public function exec() {
        if(!empty($this->URL)){
            $curl = curl_init($this->URL);
            
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_ENCODING, $_SERVER['HTTP_ACCEPT_ENCODING']);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "Accept-Language:" . $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                "Accept:" . $_SERVER['HTTP_ACCEPT'],
            ));
            curl_setopt($curl, CURLOPT_NOPROGRESS, true);
            curl_setopt_array($curl, array(
                CURLOPT_TIMEOUT => false, 
                CURLOPT_CONNECTTIMEOUT => 2, 
                CURLOPT_DNS_CACHE_TIMEOUT => 200, 
                CURLOPT_SSL_VERIFYHOST => (HTTPS ? 2 : 0), 
                CURLOPT_SSL_VERIFYPEER => false, 
                CURLOPT_LOW_SPEED_LIMIT => 5, 
                CURLOPT_LOW_SPEED_TIME => 20,
            ));

            if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
                curl_setopt($curl, CURLOPT_USERPWD, $_SERVER['PHP_AUTH_USER'] . ":" . $_SERVER['PHP_AUTH_PW']);
            

            curl_setopt($curl, CURLOPT_WRITEFUNCTION, (function ($curl, $p) use (&$body, &$headers, &$totalBuffer) {
                $shouldStream = (preg_match("~(video/|image/)~i", $headers["content-type"][0]) && strpos($headers["content-type"][0], "+") === false);
                if (!@$headers["content-length"][1] && $shouldStream) {
                    $start = 0;
                    $end = 0;
                    if (preg_match("~bytes\=([0-9]+|)\-([0-9]+|)~i", $_SERVER['HTTP_RANGE'], $r)) {
                        header('HTTP/1.1 206 Partial Content');
                        $start = $r[1][0];
                        $end = (!empty($r[2][0]) ? $r[2][0] : $headers["content-length"][0]) - 1;
                    }
                    $headers["content-length"][1] = true;
                    $headers["accept-ranges"] = array("0-" . $end, true);
                    $headers["content-range"] = array("bytes " . $start . "-" . $end . "/" . $headers["content-length"][0], true);
                    header("Content-Length: " . $headers["content-length"][0]);
                    header("Accept-Ranges: " . $headers["accept-ranges"][0]);
                    header("Content-Range: " . $headers["content-range"][0]);
                }

                if (!$shouldStream && $curl) {
                    $body .= $p;
                } 
                else {
                    $body = null;
                    echo $p;
                    ob_end_flush();
                    flush();
                }
                return strlen($p);
            }));
            
            curl_setopt($curl, CURLOPT_HEADERFUNCTION, (function($curl, $header) use (&$headers){
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2)
                    return $len;

                $headers[strtolower(trim($header[0]))] = trim($header[1]);

                return $len;
            }));

            if (!empty(CUSTOM_USER_AGENT))
                curl_setopt($curl, CURLOPT_USERAGENT, CUSTOM_USER_AGENT);
            else
                curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

            if (!is_null(CUSTOM_REFERRER))
                curl_setopt($curl, CURLOPT_REFERER, CUSTOM_REFERRER);
            else
                curl_setopt($curl, CURLOPT_REFERER, (!empty($_SERVER["HTTP_REFERER"]) ? unProxyUrl($_SERVER["HTTP_REFERER"]) : ""));


            curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieFile);
            curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieFile);

            foreach ($_FILES as $upload => $files) {
                for ($i = 0;$i < count($files["name"]);$i++) {
                    if ($files["error"][$i] == false) {
                        $name = $upload . (count($files["name"]) > 1 ? "[$i]" : "");
                    }
                }
            }
            
            if (count($_POST) > 0) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, (count($_FILES) > 0 ? $_POST : http_build_query($_POST)));
            }
            
            curl_exec($curl);
            
            if($errors = curl_error($curl)){
                $this->errors = $errors;
                $this->responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                curl_close($curl);
                return false;
            }
            else{
                $this->responseUrl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
                $this->responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                $this->responseHeaders = $headers;
                $this->response = $body;

                curl_close($curl);
                return true;
            }
        }
    }
}