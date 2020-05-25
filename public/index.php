<?php

require_once('../include/autoload.php');

$isLogged = login();

if(!empty($isLogged)){
    if($_SERVER['HTTP_HOST'] != SERVER_BASE_DOMAIN){
        $proxyUrl = unProxyUrl();
        $proxy = new browser($proxyUrl);

        $page = $proxy->openPage();
        if(empty($page))
            echo "";
        else
            echo $page;
    }
    else{
        $showMain = true;
        if(isset($_POST['url'])){
            if(filter_var($_POST['url'], FILTER_VALIDATE_URL) !== false){
                $showMain = false;
                $proxyUrl = proxyUrl($_POST['url']);

                header("Location: ".$proxyUrl);
                exit();
            }
            elseif(filter_var("http://".$_POST['url'], FILTER_VALIDATE_URL) !== false){
                $showMain = false;
                $proxyUrl = proxyUrl("http://".$_POST['url']);

                header("Location: ".$proxyUrl);
                exit();
            }
        }
        elseif($_SERVER['SCRIPT_NAME'] == "/ViewLogs" && isset(ADMINS[$isLogged]) && ADMINS[$isLogged]){
            if(!isset($_GET['date']) && !isset($_GET['special'])){
                $showMain = false;
                $message = $_SESSION[SESSION_NAME]['message'] ?? "";
                $_SESSION[SESSION_NAME]['message'] = "";
                echo '
                <!-- block injections 😁 <html><head><body> -->
                <html dir="ltr">
                    <head>
                        <title>P System | View Logs</title>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <meta name="author" content="Yehuda Eisenberg">
                        <link rel="icon" type="image/x-icon" href="'.FAVICON.'">
                    </head>
                    <body align="center">
                        <button style="top:5;left:5;position:fixed;background-color:lightgreen;" onclick="window.location.href=\'/logout\'">Logout</button>
                        <button style="top:5;left:70;position:fixed;background-color:cornflowerblue;" onclick="window.location.href=\'/Home\'">Home</button>
                        <div align="center">
                            <h1 style="font-size:3vw;">View <span style="color:blue;margin-right:5px;text-shadow:5px 5px 10px #00FF00;">P </span> system logs</h1>
                            ' . $message . '
                            <br>
                            <table border="1">
                                <tr>
                                    <th>Num</th><th>Date</th><th>Last edit</th><th>link</th>
                                <tr>';
                            foreach(glob(APP_TMP . DS . "logs" . DS . "*.log") as $num => $filename){
                                $date = substr(basename($filename), 0, -4);
                                $lastEdit = date("d/m/Y H:i", filemtime($filename));
                                $num ++;
                                echo "
                                <tr>
                                    <td>{$num}</td><td>{$date}</td><td>{$lastEdit}</td><td><a href=\"/ViewLogs?date={$date}\" target=\"_blank\">Click Here</td>
                                </tr>";
                            }
                            echo '
                            </table>
                            <br>
                            <h3>Special Logs</h3>
                            <a href="/ViewLogs?special=failed" target="_blank">Failed Connections</a>
                            <br><br>
                            <a href="/ViewLogs?special=success" target="_blank">Success Connections</a>
                        </div>
                    </body>
                </html>
                <!-- block injections 😁 </head></body></html> -->';
            }
            elseif(isset($_GET['date'])){
                $showMain = false;
                $date = basename($_GET['date']);
                $fileName = APP_TMP . DS . "logs" . DS . $date . ".log";
                if(file_exists($fileName)){
                    header('Content-Type: text/plain');
                    echo file_get_contents($fileName);

                    $line = "[" . date("H:i:s d-m-Y") . "][" . $_SERVER["REMOTE_ADDR"] . "][" . $_SESSION[SESSION_NAME]['user'] . "][" . $date . ".log" . "]" . PHP_EOL;
                    $logFileName = "View Logs.txt";
                    logAction($line, $logFileName);
                }
                else{
                    $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:red;'>The " . htmlspecialchars($date) . " log was not found 😔</h2>";
                    header("Location: " . SERVER_BASE_URL . "/ViewLogs");
                }
            }
            elseif(isset($_GET['special']) && in_array($_GET['special'], array('failed', 'success'))){
                $showMain = false;
                $logName = ucfirst($_GET['special']);
                $fileName = APP_TMP . DS . "logs" . DS . $logName . " Connections.txt";
                if(file_exists($fileName)){
                    header('Content-Type: text/plain');
                    echo file_get_contents($fileName);

                    $line = "[" . date("H:i:s d-m-Y") . "][" . $_SERVER["REMOTE_ADDR"] . "][" . $_SESSION[SESSION_NAME]['user'] . "][" . $logName . " Connections.txt" . "]" . PHP_EOL;
                    $logFileName = "View Logs.txt";
                    logAction($line, $logFileName);
                }
                else{
                    $_SESSION[SESSION_NAME]['message'] = "<h2 style='color:red;'>The " . htmlspecialchars($logName) . " log was not found 😔</h2>";
                    header("Location: " . SERVER_BASE_URL . "/ViewLogs");
                }
            }
        }
        if($showMain){
            $admin = false;
            if(isset(ADMINS[$isLogged]) && ADMINS[$isLogged])
                $admin = true;
            
            $message = $_SESSION[SESSION_NAME]['message'] ?? "";
            $_SESSION[SESSION_NAME]['message'] = "";
            echo '
            <!-- block injections 😁 <html><head><body> -->
            <html dir="ltr">
                <head>
                    <title>P System | Home</title>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <meta name="author" content="Yehuda Eisenberg">
                    <link rel="icon" type="image/x-icon" href="'.FAVICON.'">
                </head>
                <body align="center">
                    <button style="top:5;left:5;position:fixed;background-color:lightgreen;" onclick="window.location.href=\'/logout\'">Logout</button>
                    '.($admin ? '<button style="top:5;left:70;position:fixed;background-color:cornflowerblue;" onclick="window.location.href=\'/ViewLogs\'">View Logs</button>' : '').'
                    <div align="center">
                        <h1 style="font-size:3vw;">Welcome to the new <span style="color:blue;margin-right:5px;text-shadow:5px 5px 10px #00FF00;">P </span> system :)</h1>
                        ' . $message . '
                        <br>
                        <form method="POST" target="_blank" style="margin:1px;">
                            <input dir="ltr" type="text" required style="font-size:1.5vw;width:35%;border-color:limegreen;" name="url" placeholder="URL - (e.g. http://example.com)">
                            <button style="font-size:1.5vw;" type="submit">Go</button>
                        </form>
                        <span style="margin:1px;color:gray;font-size:1.4vw;">(opens in a new window)</span>
                    </div>
                </body>
            </html>
            <!-- block injections 😁 </head></body></html> -->';
        }
    }
}
?>