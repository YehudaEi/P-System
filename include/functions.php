<?php

function proxyUrl($url){
    if (!empty($url)) {
        //parse url
        $urlData = parse_url($url);
        $proxyUrl = (HTTPS ? "https" : "http") . "://";

        //https
        if(isset($urlData['scheme']) && !empty($urlData['scheme']))
            if($urlData['scheme'] == "https")
                $proxyUrl .= "s--";
            else
                $proxyUrl .= "h--";
        else
            return false;
        
        //host
        foreach(explode(".", $urlData['host']) as $domain){
            $domain = str_replace("-", "_-", $domain);
            $proxyUrl .= $domain."--";
        }
        $proxyUrl = substr($proxyUrl, 0, -2);
        $proxyUrl .= ".".SERVER_BASE_DOMAIN;

        //path
        if(isset($urlData['path']) && !empty($urlData['path']))
            $proxyUrl .= $urlData['path'];

        //query
        if(isset($urlData['query']) && !empty($urlData['query']))
            $proxyUrl .= "?".$urlData['query'];

        //fragment
        if(isset($urlData['fragment']) && !empty($urlData['fragment']))
            $proxyUrl .= "#".$urlData['fragment'];
        
        return $proxyUrl;
    }
    
    return $url;
}

function unProxyUrl($url = null){
    $tmp = explode("--" , str_replace(".".SERVER_BASE_DOMAIN, "", ($url ?? $_SERVER['HTTP_HOST'])));
    $baseUrl = "";
    foreach($tmp as $k => $domain){
        if($k === 0){
            if($domain == "s")
                $baseUrl .= "https://";
            elseif($domain == "h")
                $baseUrl .= "http://";
            else{
                $baseUrl .= "http://";
                $baseUrl .= $domain.".";
            }
        }
        else{
            $baseUrl .= $domain.".";
        }
    }
    $baseUrl = str_replace("_-", "-", $baseUrl);
    $baseUrl = substr($baseUrl, 0, -1);
    $fullUrl = $baseUrl.$_SERVER['REQUEST_URI'];

    return $fullUrl;
}

function logAction($line, $fileName) {
    if (LOGGING) {
        if (!file_exists(APP_TMP)) {mkdir(APP_TMP, 0777);}
        $dir = APP_TMP . DS . "logs" . DS;
        if (!file_exists($dir)) {mkdir($dir, 0777);}

        file_put_contents($dir . $fileName, $line, FILE_APPEND | LOCK_EX);

        return true;
    }
    return false;
}

function errorHandler($exception) {
    if (is_object($exception) && trim(strtolower(@get_class($exception))) == "exception") {
        $message = trim($exception->getMessage());

        if (!empty($message)) {
            echo $message;
            return true;
        }
    }
    return false;
}

function createCookieFile() {
    if(!isset($_SESSION[SESSION_NAME]['cookieName'])){
        $cookieFileName = uniqid();
        $_SESSION[SESSION_NAME]['cookieName'] = $cookieFileName;
    }
    else{
        $cookieFileName = $_SESSION[SESSION_NAME]['cookieName'];
    }

    $dir = APP_TMP . DS . "cookies" . DS;
    if (!file_exists($dir)) {mkdir($dir, 0777);}

    $fileName = $dir . $cookieFileName . ".txt";
    return $fileName;
}

function modifyURL($URL) {
    if (!preg_match("~^[a-z]+://~is", $URL = htmlspecialchars_decode(trim($URL)))) {
        $validDomainName = (preg_match("~^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$~i", $h = parse_url("http://" . $URL, PHP_URL_HOST)) && preg_match("~^.{1,253}$~", $h) && preg_match("~^[^\.]{1,63}(\.[^\.]{1,63})*$~", $h)) && browser::getResponseType(pathinfo($h, PATHINFO_EXTENSION)) == "URL";

        $scheme = (($s = parse_url($URL, PHP_URL_SCHEME)) == "" ? "http" : strtolower($s));
        $host = ((isset($validDomainName) && !$validDomainName) || @$URL[0] == "/" ? parse_url($URL, PHP_URL_HOST) : "");
        $URL = ($URL == "#" ? $URL : $scheme . "://" . $host . $URL);

        while (preg_match("~/[A-Za-z0-9_]+/\.\./~", $URL)) {
            $URL = preg_replace("~/[A-Za-z0-9_]+/\.\./~", "/", $URL);
        }
    }
    return str_replace(array(" ", "\\"), array("+", ""), $URL);
}

