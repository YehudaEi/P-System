<?php

function login(){
    header(base64_decode('WC1Qb3dlcmVkLUJ5OiBQLVN5c3RlbSBieSBZZWh1ZGFFaXNlbmJlcmc='));
    $user = null;

    if(isset($_SERVER['HTTP_COOKIE'])){
        if(strtolower($_SERVER['SCRIPT_NAME']) == "/logout" && $_SERVER['HTTP_HOST'] == SERVER_BASE_DOMAIN){ 
            if(isset($_SESSION[SESSION_NAME]['user'])){
                unset($_SESSION[SESSION_NAME]['user']);
                updateLastIps(true);
                $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:blue;'>Goodbye 👋</h2>";
            }
            header("Location: https://".SERVER_BASE_DOMAIN."/Login");
        }
        elseif(isset($_SESSION[SESSION_NAME]['user'], USERS[$_SESSION[SESSION_NAME]['user']])) {
            if($_SERVER["REMOTE_ADDR"] == $_SESSION[SESSION_NAME]['clientIP']){
                $user = $_SESSION[SESSION_NAME]['user'];
                updateLastIps();
            }
            else{
                unset($_SESSION[SESSION_NAME]['user']);
                unset($_SESSION[SESSION_NAME]['clientIP']);
                $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:blue;'>⚠️ Your IP address has changed</h2>";
                header("Location: https://".SERVER_BASE_DOMAIN);
            }
        }
        elseif(isset($_POST['user'], $_POST['pass'])) {            
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
            
            logAction($line, $logFileName);
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
                        <form method="POST">
                            <input type="text" name="user" required placeholder="Username"><br><br>
                            <input type="password" name="pass" required placeholder="Password"><br><br>
                            <button type="submit">Login</button>
                        </form>
                    </div>
                </body>
            </html>
            <!-- block injections 😁 </head></body></html> -->';
        }
    }
    elseif(isset($_SERVER['HTTP_ORIGIN'])){
        if(file_exists(APP_LAST_IPS)){
            $data = json_decode(file_get_contents(APP_LAST_IPS), true);
            foreach($data as $ip => $time){
                if($_SERVER['REMOTE_ADDR'] == $ip && (time() - $time) < 60*5){
                    $user = "Origin {".unProxyUrl($_SERVER['HTTP_ORIGIN'])."}";
                    $_SESSION[SESSION_NAME]['user'] = $user;
                    break;
                }
            }
        }
    }

    return $user;
}
?>