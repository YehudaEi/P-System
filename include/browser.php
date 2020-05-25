<?php

class browser {
    private $URL = null;
    private $blacklistedWebsites = array("localhost", "127.0.0.1", SERVER_BASE_URL);
    private $notAllowedHeaders = array("content-security-policy", "x-frame-options", "access-control-allow-origin", "access-control-allow-methods", "access-control-allow-credentials", "server", "content-length", "expiry", "content-encoding", "transfer-encoding");

    function __construct($URL = "") {
        if (!empty($URL)) {
            $this->URL = modifyURL($URL);
        }
    }    

    public function openPage() {
        if (!empty($this->URL)) {

            $request = new request($this->URL);
            $success = $request->exec();
            if($success){
                if (!$request->responseCode) {
                    throw new Exception("Could not resolve host: " . (($h = parse_url($this->URL, PHP_URL_HOST)) != "" ? $h : $this->URL));
                }

                if ($this->blacklistedWebsites) {
                    foreach($this->blacklistedWebsites as $domain){
                        if(strpos($this->URL, $domain) !== false)
                            throw new Exception("Access to " . $this->URL . " is not permitted on this server.");
                    }
                }

                $line = "[".$_SESSION[SESSION_NAME]['user']."][" . date("d-m-Y H:i:s") . "][" . $_SERVER["REMOTE_ADDR"] . "][$request->responseCode] " . $this->URL . PHP_EOL;
                logAction($line, date("d-m-Y").".log");

                if ($request->responseUrl != $this->URL) {
                    @header("Location: " . proxyUrl($request->responseUrl));
                    exit;
                }

                if (empty($request->response)) {
                    return null;
                }
                else {
                    $page = $request->response;
                }

                $contentType = explode(";", $request->responseHeaders["content-type"])[0];
                
                foreach($request->responseHeaders as $headerName => $headerValue){
                    if (!empty($headerName) && !in_array(strtolower($headerName), $this->notAllowedHeaders)){
                        preg_match_all(URL_REGEX, $headerValue, $matchs);
    
                        $matchs = array_unique($matchs[0]);
                        
                        foreach($matchs as $match){
                            if($match != "//"){
                                $proxyMatch = proxyUrl(modifyURL($match));
                                $headerValue = str_replace($match, $proxyMatch, $headerValue);
                            }
                        }
    
                        header($headerName . ": " . $headerValue);
                    }
                }

                $origin = $_SERVER['HTTP_ORIGIN'] ?? "*";
                header('Access-Control-Allow-Origin: '.$origin);
                header('Access-Control-Allow-Credentials: true');                
                

                if (strlen($page) > 0) {
                    if(in_array($this->getResponseType($contentType), array("html", "php", "js", "css", "json", "manifest", "plain", "xml"))){
                        $replaceUrl = function($url){
                            $proxyUrl = proxyUrl(modifyURL($url[0]));
                            return $proxyUrl;
                        };

                        $page = preg_replace_callback(URL_REGEX, $replaceUrl, $page);

                        $page = str_replace("returnfalse", "return false", $page); //youtube error
                    }
                }
                else {
                    throw new Exception("Unable to load page content.");
                }
                
                header("content-length: ".strlen($page));
                return $page;
            }
            else{
                throw new Exception("There was an error while requesting the page.\n\nresponse code: ".$request->responseCode."\n\nerror: ".var_export($request->errors, true));
            }
        }
        return null;
    }

    public static function getResponseType($convert) {
        $rules = array("text/javascript" => "js", "application/javascript" => "js", "application/x-javascript" => "js", "application/x-shockwave-flash" => "swf", "audio/x-wav" => "wav", "video/quicktime" => "mov", "video/x-msvideo" => "avi", "text/html" => array("html", "htm"), "text/*" => array("php", "css", "xml", "plain"), "application/*" => array("pdf", "zip", "xml", "rss", "xhtml", "json", "manifest"), "font/*" => array("ttf", "otf", "woff", "eot"), "image/*" => array("jpeg", "jpg", "gif", "png", "svg"), "video/*" => array("3gp", "mreg", "mpg", "mpe", "mp3"), "URL" => array("a[c-gilmoq-uwxz]", "arpa", "asia", "b[abd-jm-or-twyz]", "biz", "c[acdf-ik-oru-z]", "cat", "com", "coop", "d[ejkmoz]", "e[cegr-u]", "edu", "f[i-kmor]", "g[ad-il-np-uwy]", "gov", "h[kmnrtu]", "i[del-oq-t]", "info", "int", "j[emop]", "jobs", "k[eg-imnprwyz]", "l[a-cikr-vy]", "m[ac-eghk-z]", "mil", "mobi", "museum", "n[ace-gilopruz]", "net", "om", "onion", "org", "p[ae-hkmnr-twy]", "post", "pro", "qa", "r[eosuw]", "s[a-eg-ik-ort-vx-z]", "t[cdf-hj-otvwz]", "travel", "u[agksyz]", "v[aceginu]", "w[fs]", "y[te]", "xxx", "z[amw]"));
        if (!empty($convert)) {
            $isContentType = strpos($convert, "/") !== false;
            $cExt = "";
            if ($isContentType) {
                $cExt = explode("/", $convert)[1];
            }
            foreach ($rules as $key => $ext) {
                if (str_replace("*", $cExt, $key) == $convert || !$isContentType) {
                    foreach ((array)$ext as $e) {
                        if ($isContentType && (preg_match("~^" . $e . "$~i", explode("+", $cExt) [0]) || count($ext) == 1)) {
                            return $e;
                        } elseif (!$isContentType && preg_match("~^" . $e . "$~i", $convert)) {
                            return str_replace("*", $e, $key);
                        }
                    }
                }
            }
        }
        return false;
    }
}
