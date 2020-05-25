<?php

class browser {
    public $cookieDIR, $isSSL = "";
    private $URL, $responseHeaders, $HTTP = "";
    private $blacklistedWebsites = array("localhost", "127.0.0.1", SERVER_BASE_URL);
    private $notAllowedHeaders = array("content-security-policy", "x-frame-options", "access-control-allow-origin", "access-control-allow-methods", "access-control-allow-credentials", "server", "content-encoding", "content-length", "cache-control", "transfer-encoding");

    public $customUserAgent = null;
    public $customReferrer = null;
    public $logToFile = true;

    function __construct($URL = "") {
        set_exception_handler(array($this, 'errorHandler')); //Set our custom error handler
        $this->isSSL = HTTPS; //Check if the proxy is running on a SSL certificate
        $this->createCookieDIR(); //Populate cookieDIR with directory string, but don't create the file yet
        if (!empty($URL)) {
            header('Content-Type:');
            header('Cache-Control:');
            header('Last-Modified:');
            
            for ($e = $URL;strlen($e) > 0;$e = substr($e, 0, strlen($e) - 1)) {
                if (strlen($e) % 4 != 0 || !($decode = base64_decode($e, true))) {
                    continue;
                }
                if (filter_var("http://" . trim($decode) . "/", FILTER_VALIDATE_URL)) {
                    $URL = str_replace($e, $decode, $URL);
                    break;
                }
            }
            
            $this->URL = $this->modifyURL($URL); //Fix any formatting issues with the URL so it is resolvable
        }
    }

    public function errorHandler($exception) {
        if (is_object($exception) && trim(strtolower(@get_class($exception))) == "exception") {
            $message = trim($exception->getMessage()); //Get message from exception
            //If message isn't empty output it to screen, the script will be terminated automatically
            if (!empty($message)) {
                echo $message;
                return true;
            }
        }
        return false;
    }

    public function modifyURL($URL) {
        if (!preg_match("~^[a-z]+://~is", $URL = htmlspecialchars_decode(trim($URL)))) {
            $currentURL = $this->URL; //Store the URL for the current page
            if ($URL != "/" && $URL != "#" && (@$URL[0] != "/" || strpos(substr($URL, 0, 3), "./") !== false || substr($URL, 0, 2) == "//")) {
                while (substr($URL, 0, 1) == "/") {
                    $URL = substr($URL, 1);
                } //Remove any rogue slashes
                $validDomainName = (preg_match("~^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$~i", $h = parse_url("http://" . $URL, PHP_URL_HOST)) && preg_match("~^.{1,253}$~", $h) && preg_match("~^[^\.]{1,63}(\.[^\.]{1,63})*$~", $h)) && $this->convertExtension(pathinfo($h, PATHINFO_EXTENSION)) == "URL";
                if (!$validDomainName && parse_url($currentURL, PHP_URL_HOST) && (!empty($URL) || trim(pathinfo($URL, PATHINFO_EXTENSION)))) {
                    $path = parse_url(explode("?", $currentURL) [0], PHP_URL_PATH); //Find path from original URL
                    if (pathinfo(pathinfo(explode("?", $currentURL) [0], PATHINFO_BASENAME), PATHINFO_EXTENSION) != "") {
                        $path = str_replace(pathinfo(explode("?", $currentURL) [0], PATHINFO_BASENAME), "", $path); //Remove path if needed
                        
                    }
                    while (substr($path, strlen($path) - 1, strlen($path)) == "/") {
                        $path = substr($path, 0, strlen($path) - 1);
                    } //Remove any slashes from end of URL which are not needed
                    $URL = (preg_match("~(^\./|\\\\./)~i", $URL) ? preg_replace("~(^\./|/./)~i", "/", $URL) : $path . "/" . $URL); //Recompile the URL so that it is valid
                    
                }
            }
            $scheme = (($s = parse_url($currentURL, PHP_URL_SCHEME)) == "" ? "http" : strtolower($s)); //Find a scheme for the URL as none was set originally
            $host = ((isset($validDomainName) && !$validDomainName) || @$URL[0] == "/" ? parse_url($currentURL, PHP_URL_HOST) : "");
            $URL = ($URL == "#" ? $currentURL : $scheme . "://" . $host . $URL); //Compile all needed URL components
            while (preg_match("~/[A-Za-z0-9_]+/\.\./~", $URL)) {
                $URL = preg_replace("~/[A-Za-z0-9_]+/\.\./~", "/", $URL);
            } //Convert the "../" to the absolute location
            
        }
        return str_replace(array(" ", "\\"), array("+", ""), $URL);
    }

    public function createCookieDIR() {
        if(!isset($_SESSION[SESSION_NAME]['cookieName'])){
            $cookieFileName = uniqid();
            $_SESSION[SESSION_NAME]['cookieName'] = $cookieFileName;
        }
        else{
            $cookieFileName = $_SESSION[SESSION_NAME]['cookieName'];
        }
        $this->cookieDIR = APP_TMP . DS . 'cookies' . DS . $cookieFileName . ".txt"; //Generate cookie file directory
        return (bool)is_writable(dirname($this->cookieDIR)); //Return whether the cookie directory is writable
    }

    public function openPage() {
        if (!empty($this->URL)) {
            $page = "";
            
            $this->createCookieDIR(); //If cookies are enabled create the directory
            
            $return = $this->curlRequest($this->URL); //Run the cURL function to get the page for parsing
            
            $this->HTTP = $return["HTTP"];
            $this->responseHeaders = $return["headers"]; //Populate the response information values for plugins
            $contentType = explode(";", $this->responseHeaders["content-type"])[0];
            $charset = @explode("charset=", $this->responseHeaders["content-type"])[1]; //Store content type and charset for parsing
            
            foreach($this->responseHeaders as $headerName => $headerValue){
                if (!empty($headerName) && !in_array(strtolower($headerName), $this->notAllowedHeaders)){
                    preg_match_all(URL_REGEX, $headerValue, $matchs);

                    $matchs = array_unique($matchs[0]);
                    
                    foreach($matchs as $match){
                        if($match != "//"){
                            $proxyMatch = proxyUrl($this->modifyURL($match));
                            $headerValue = str_replace($match, $proxyMatch, $headerValue);
                        }
                    }

                    header($headerName . ": " . $headerValue);
                }
            }

            $requestHeaders = getallheaders();
            $origin = "*, *.".SERVER_BASE_DOMAIN;
            foreach($requestHeaders as $headerName => $headerValue){
                if(strtolower($headerName) == "origin")
                    $origin = $headerValue;
            }
            header('Access-Control-Allow-Origin: '.$origin);
            header('access-control-allow-credentials: true');

            if (!$this->HTTP) {
                throw new Exception("Could not resolve host: " . (($h = parse_url($this->URL, PHP_URL_HOST)) != "" ? $h : $this->URL));
            } //Check that page was resolved right
            
            if ($this->blacklistedWebsites && preg_match('~(' . implode("|", $this->blacklistedWebsites) . ')~', $this->URL, $d)) {
                throw new Exception("Access to " . $d[0] . " is not permitted on this server.");
            }
            
            if ($return["URL"] != $this->URL) {
                @header("Location: " . proxyUrl($return["URL"]));
                exit;
            } //Go to new proxy URL if cURL was redirected there
            
            $this->logAction($this->HTTP, $this->URL); //Log URL and HTTP code to file
            
            if (is_null($return["page"])) {
                return null;
            } else {
                $page .= $return["page"];
                $return = null;
            } //Check that content hasn't already been outputted, so needs parsing
                        
            if (!empty($page) || strlen($page) > 0) {
                if(in_array($this->convertExtension($contentType), array("html", "php", "js", "css"))){
                    preg_match_all(URL_REGEX, $page, $matchs);

                    $matchs = array_unique($matchs[0]);
                    
                    foreach($matchs as $match){
                        if($match != "//"){
                            $proxyMatch = proxyUrl($this->modifyURL($match));
                            $page = str_replace($match, $proxyMatch, $page);
                        }
                    }

                    $page = str_replace("returnfalse", "return false", $page); //youtube error
                }
            } 
            else {
                throw new Exception("Unable to load page content."); //Page was resolved but no content was returned
                
            }
            
            header("content-length: ".strlen($page));
            return $page; //Return fully parsed page
            
        }
        return null; //Return null as no URL was set
    }

    private function convertExtension($convert) {
        $rules = array("text/javascript" => "js", "application/javascript" => "js", "application/x-javascript" => "js", "application/x-shockwave-flash" => "swf", "audio/x-wav" => "wav", "video/quicktime" => "mov", "video/x-msvideo" => "avi", "text/html" => array("html", "htm"), "text/*" => array("php", "css", "xml", "plain"), "application/*" => array("pdf", "zip", "xml", "rss", "xhtml"), "font/*" => array("ttf", "otf", "woff", "eot"), "image/*" => array("jpeg", "jpg", "gif", "png", "svg"), "video/*" => array("3gp", "mreg", "mpg", "mpe", "mp3"), "URL" => array("a[c-gilmoq-uwxz]", "arpa", "asia", "b[abd-jm-or-twyz]", "biz", "c[acdf-ik-oru-z]", "cat", "com", "coop", "d[ejkmoz]", "e[cegr-u]", "edu", "f[i-kmor]", "g[ad-il-np-uwy]", "gov", "h[kmnrtu]", "i[del-oq-t]", "info", "int", "j[emop]", "jobs", "k[eg-imnprwyz]", "l[a-cikr-vy]", "m[ac-eghk-z]", "mil", "mobi", "museum", "n[ace-gilopruz]", "net", "om", "onion", "org", "p[ae-hkmnr-twy]", "post", "pro", "qa", "r[eosuw]", "s[a-eg-ik-ort-vx-z]", "t[cdf-hj-otvwz]", "travel", "u[agksyz]", "v[aceginu]", "w[fs]", "y[te]", "xxx", "z[amw]"));
        if (!empty($convert)) {
            $isContentType = strpos($convert, "/") !== false; //Check if value is a content type
            $cExt = "";
            if ($isContentType) {
                $cExt = explode("/", $convert) [1];
            }
            foreach ($rules as $key => $ext) {
                if (str_replace("*", $cExt, $key) == $convert || !$isContentType) {
                    foreach ((array)$ext as $e) {
                        if ($isContentType && (preg_match("~^" . $e . "$~i", explode("+", $cExt) [0]) || count($ext) == 1)) {
                            return $e; //Return validated content type
                            
                        } elseif (!$isContentType && preg_match("~^" . $e . "$~i", $convert)) {
                            return str_replace("*", $e, $key); //Return validated extension
                            
                        }
                    }
                }
            }
        }
        return false; //No matching conversion has been found
        
    }

    public function logAction($HTTP, $URL) {
        if ($this->logToFile && !empty($URL)) {
            $dir = APP_TMP . DS . "logs" . DS;
            if (!file_exists($dir)) {
                mkdir($dir, 0777);
            } //Create logs DIR if not found already
            $line = "[".$_SESSION[SESSION_NAME]['user']."][" . date("H:i:s d-m-Y") . "][" . $_SERVER["REMOTE_ADDR"] . "][$HTTP] " . $URL . PHP_EOL;
            $attempt = file_put_contents($dir . date("d-m-Y") . ".log", $line, FILE_APPEND | LOCK_EX);
            return ($attempt !== false); //Return whether the write was successful
        }
        return false; //Logging is disabled or no URL was passed
    }

    public function curlRequest($URL) {
        $curl = curl_init($URL);
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //Allow cURL to download the source code
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); //Follow any page redirects provided in headers
        curl_setopt($curl, CURLOPT_ENCODING, $_SERVER['HTTP_ACCEPT_ENCODING']); //Force encoding to be UTF-8, gzip or deflated
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Accept-Language:" . $_SERVER['HTTP_ACCEPT_LANGUAGE'],
            "Accept:" . $_SERVER['HTTP_ACCEPT'],
        )); //Add a basic Accept header to emulate browser headers
        curl_setopt($curl, CURLOPT_NOPROGRESS, true); //Save memory and processing power by disabling calls to unused progress callbacks
        curl_setopt_array($curl, array( //Add some settings to make the cURL request more efficient
            CURLOPT_TIMEOUT => false, 
            CURLOPT_CONNECTTIMEOUT => 2, 
            CURLOPT_DNS_CACHE_TIMEOUT => 200, 
            CURLOPT_SSL_VERIFYHOST => ($this->isSSL ? 2 : 0), 
            CURLOPT_SSL_VERIFYPEER => false, 
            CURLOPT_LOW_SPEED_LIMIT => 5, 
            CURLOPT_LOW_SPEED_TIME => 20,
        ));

        if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
            curl_setopt($curl, CURLOPT_USERPWD, $_SERVER['PHP_AUTH_USER'] . ":" . $_SERVER['PHP_AUTH_PW']);
        
        curl_setopt($curl, CURLOPT_WRITEFUNCTION, (function ($curl, $p) use (&$body, &$headers, &$totalBuffer) {
            $shouldStream = (preg_match("~(video/|image/)~i", $headers["content-type"][0]) && strpos($headers["content-type"][0], "+") === false); //Check if the file needs to be parsed
            if (!@$headers["content-length"][1] && $shouldStream) { //Send various stream headers to browser (only once)
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
            } else {
                $body = null;
                echo $p;
                ob_end_flush();
                flush();
            } //Output the file depending on if the file needs to be parsed
            return strlen($p);
        }));
        
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, (function($curl, $header) use (&$headers){
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) //ignore invalid headers
                return $len;

            $headers[trim($header[0])] = trim($header[1]);

            return $len;
        }));

        //Set user agent, referrer, cookies and post parameters based on 'virtual' browser values
        if (!is_null($this->customUserAgent)) {
            curl_setopt($curl, CURLOPT_USERAGENT, $this->customUserAgent);
        } else {
            curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        }
        if (!is_null($this->customReferrer)) {
            curl_setopt($curl, CURLOPT_REFERER, $this->customReferrer);
        } else {
            curl_setopt($curl, CURLOPT_REFERER, (!preg_match("~" . preg_replace(array("~[a-z]+://~i", "~" . basename($_SERVER['PHP_SELF']) . "~i"), array("(http(s|)://|)", "(" . basename($_SERVER['PHP_SELF']) . "|)"), SERVER_BASE_URL) . "~is", $r = getProxyUrl(@$_SERVER["HTTP_REFERER"]))) ? $r : "");
        }
        
        //Set cookie file in cURL
        curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookieDIR);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieDIR);

        foreach ($_FILES as $upload => $files) {
            for ($i = 0;$i < count($files["name"]);$i++) {
                if ($files["error"][$i] == false) {
                    $name = $upload . (count($files["name"]) > 1 ? "[$i]" : "");
                }
            }
        } //Parse any uploaded files into the POST values for submission
        if (count($_POST) > 0) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, (count($_FILES) > 0 ? $_POST : http_build_query($_POST)));
        } //Send POST values using cURL
        curl_exec($curl); //Run request with settings added previously
        $vars = array(
            "URL" => curl_getinfo($curl, CURLINFO_EFFECTIVE_URL), 
            "HTTP" => curl_getinfo($curl, CURLINFO_HTTP_CODE), 
            "headers" => $headers, 
            "error" => curl_error($curl), 
            "page" => $body
        );
        curl_close($curl); //Close cURL connection safely once complete
        return $vars;
    }
}
