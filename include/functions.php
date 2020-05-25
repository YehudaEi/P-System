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

function getProxyUrl($url = null){
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

