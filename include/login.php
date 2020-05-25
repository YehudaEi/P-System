<?php

function login(){
    header(base64_decode('WC1Qb3dlcmVkLUJ5OiBQLVN5c3RlbSBieSBZZWh1ZGFFaXNlbmJlcmc='));
    $user = null;

    if(strtolower($_SERVER['SCRIPT_NAME']) == "/logout" && $_SERVER['HTTP_HOST'] == SERVER_BASE_DOMAIN){ 
        if(isset($_SESSION[SESSION_NAME]['user'])){
            unset($_SESSION[SESSION_NAME]['user']);
            $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:blue;'>Goodbye 👋</h2>";
        }
        header("Location: https://".SERVER_BASE_DOMAIN."/Login");
    }
    elseif(isset($_SESSION[SESSION_NAME]['user'], USERS[$_SESSION[SESSION_NAME]['user']])) {
        if($_SERVER["REMOTE_ADDR"] == $_SESSION[SESSION_NAME]['clientIP']){
            $user = $_SESSION[SESSION_NAME]['user'];
        }
        else{
            unset($_SESSION[SESSION_NAME]['user']);
            $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:blue;'>⚠️ Your IP address has changed</h2>";
            header("Location: https://".SERVER_BASE_DOMAIN);
        }
    }
    elseif(isset($_POST['user'], $_POST['pass'])) {
        if (!file_exists(APP_TMP)) {mkdir(APP_TMP, 0777);}
        $dir = APP_TMP . DS . "logs" . DS;
        if (!file_exists($dir)) {mkdir($dir, 0777);}
            
        if (isset(USERS[strtolower($_POST['user'])]) && isset($_POST['pass']) && password_verify($_POST['pass'], USERS[strtolower($_POST['user'])])) {
            $_SESSION[SESSION_NAME]['user'] = strtolower($_POST['user']);
            $_SESSION[SESSION_NAME]['clientIP'] = $_SERVER["REMOTE_ADDR"];
            $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:lime;'>Success Connected 😀</h2>";
            $line = "[" . date("H:i:s d-m-Y") . "][" . $_SERVER["REMOTE_ADDR"] . "][" . $_SERVER["HTTP_USER_AGENT"] . "][" . $_POST['user'] . "][USER_PASSWORD]" . PHP_EOL;
            $logFileName = "Success Connections.txt";
        } else {
            unset($_SESSION[SESSION_NAME]['user']);
            $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:red;'>username or password is incorrect 😕</h2>";
            $line = "[" . date("H:i:s d-m-Y") . "][" . $_SERVER["REMOTE_ADDR"] . "][" . $_SERVER["HTTP_USER_AGENT"] . "][" . $_POST['user'] . "][" . $_POST['pass'] . "]" . PHP_EOL;
            $logFileName = "Failed Connections.txt";
        }
        
        file_put_contents($dir . $logFileName, $line, FILE_APPEND | LOCK_EX);
        header("Refresh: 0");
    }
    else{
        unset($_SESSION[SESSION_NAME]['user']);
        $message = $_SESSION[SESSION_NAME]['message'] ?? "";
        $_SESSION[SESSION_NAME]['message'] = "";
        echo '
        <!-- block injections 😁 <html><head><body> -->
        <html dir="ltr">
            <head>
                <title>Yehuda\'s System | Login</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <meta name="author" content="Yehuda Eisenberg">
                <link rel="icon" type="image/x-icon" href="'.FAVICON.'">
            </head>
            <body>
                <div align="center">
                    <h1>Yehuda\'s System - Login</h1>
                    ' . $message . '
                    <form method="POST" action="/Home">
                        <input type="text" name="user" required placeholder="Username"><br><br>
                        <input type="password" name="pass" required placeholder="Password"><br><br>
                        <button type="submit">Login</button>
                    </form>
                </div>
            </body>
        </html>
        <!-- block injections 😁 </head></body></html> -->';
    }

    return $user;
}
?>