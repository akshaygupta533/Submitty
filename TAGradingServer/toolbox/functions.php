<?php


// FOR DEBUGGING
//ini_set('display_errors', 1);
//error_reporting(E_ALL);


use \lib\AutoLoader;
use \lib\Database;
use \lib\ExceptionHandler;
use \lib\Logger;

// get our sweet autoloader!
include __DIR__ . "/../lib/AutoLoader.php";
AutoLoader::registerDirectory(AutoLoader::getPathToRoot(getcwd())."lib", true, "lib");
AutoLoader::registerDirectory(AutoLoader::getPathToRoot(getcwd())."app", true, "app");

$start_time = microtime_float();

////////////////////////////////////////////////////////////////////////////////////////////////////////
// INCLUDES
////////////////////////////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['course'])) {
    $config = __DIR__."/configs/".$_GET['course'].".php";
    if (!file_exists($config)) {
        die("Fatal Error: The config for course=#### does not exist");
    }
}
else {
    die("Fatal Error: You must use course=#### in the URL bar");
}

require_once("configs/master.php");
require_once($config);

$DEBUG = (defined('__DEBUG__')) ? (__DEBUG__): false;
ExceptionHandler::$debug = $DEBUG;
ExceptionHandler::$logExceptions = true;
Logger::$log_path = __LOG_PATH__;

if($DEBUG) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
else {
    ini_set('display_errors', 1);
    error_reporting(E_ERROR);
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// CORE FUNCTIONALITY CODE - Site-wide includes, database, email, and user account information.
////////////////////////////////////////////////////////////////////////////////////////////////////////

$COURSE_NAME = __COURSE_NAME__;
$BASE_URL = __BASE_URL__;

header("Content-Type: text/html; charset=UTF-8");

$db = Database::getInstance();
$db->connect(__DATABASE_HOST__, __DATABASE_USER__, __DATABASE_PASSWORD__, __DATABASE_NAME__);

$user_id = 0;
if ($DEBUG) {
    $suggested_username = "pevelm";
}
else {
    $suggested_username = $_SERVER["PHP_AUTH_USER"];
}
$params = array($suggested_username);
Database::query("SELECT * FROM users WHERE user_rcs=?", $params);
$user_info = $db->row();
if (!isset($user_info['user_id'])) {
    die("Unrecognized user: {$suggested_username}. Please contact an administrator to get an account.");
}
$user_logged_in = isset($user_info['user_id']);
$user_is_administrator = isset($user_info['user_is_administrator']) && $user_info['user_is_administrator'] == 1;
$user_id = $user_info['user_id'];

if (isset($user_info['use_debug']) && $user_info['use_debug'] == 1) {
    $DEBUG = true;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////
// GENERAL
////////////////////////////////////////////////////////////////////////////////////////////////////////


function echo_error($error)
{
    echo $error, "<br/>";
    echo "<br/>";
}

function generateNumbers($max = 64) 
{
    return generateRandomString("0123456789", $max);
}

function generateSalt($max = 64) 
{
    return generateRandomString("abcdef0123456789", $max);
}

function generatePassword($max = __PASSWORD_AUTO_LENGTH__) 
{
    return generateRandomString("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()", $max);
}

function generateRandomString($alphabet, $max = 64)
{
    $retVal = "";
    
    for($i = 0; $i < $max; $i++)
    {
        $retVal .= $alphabet{mt_rand(0, (strlen($alphabet) - 1))};
    }
    
    return $retVal;
}

function is_valid_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function strip_url_get_variables($url)
{
    $retVal = explode("?", $url);
    return $retVal[0];
}

function url_location()
{
    $location = $_SERVER["PHP_SELF"];
    if (!strstr($location,'.php')) {
        $location .= 'index.php';
    }
    $paths = explode("/", $location);
    $return = array();
    foreach($paths as $path) {
        if ($path != "" && !strstr(__BASE_URL__, $path)) {
            $return[] = $path;
        }
    }

    return substr(implode("/", $return), 0, -4);

}

function url_sans_get()
{
    $retVal = explode("?", $_SERVER["REQUEST_URI"]);
    return $retVal[0];
}

function url_add_get($new_get_value)
{
    $retVal = $_SERVER["REQUEST_URI"];
    
    if(strstr($retVal, "?"))
    {
        $retVal .= "&" . $new_get_value;    
    }
    else
    {
        $retVal .= "?" . $new_get_value;    
    }
    
    return $retVal;
}
    
function format_money($number, $fractional=true) 
{ 
    if($fractional) 
    { 
        $number = sprintf('%.2f', $number); 
    } 
    while(true) 
    { 
        $replaced = preg_replace('/(-?\d+)(\d\d\d)/', '$1,$2', $number); 
        if($replaced != $number) 
        { 
            $number = $replaced; 
        } 
        else 
        { 
            break; 
        } 
    } 
    
    return $number; 
} 

function digit_to_ordinal($number)
{
    $number = intval($number);
    $ends = array('th','st','nd','rd','th','th','th','th','th','th');
    
    if(($number %100) >= 11 && ($number%100) <= 13)
    {
       $abbreviation = $number. 'th';    
    }
    else
    {
       $abbreviation = $number. $ends[$number % 10];    
    }
    
    return $abbreviation;
}


////////////////////////////////////////////////////////////////////////////////////////////////////////
// UTILITIES
////////////////////////////////////////////////////////////////////////////////////////////////////////


/**
 * @param $filename
 * @param $number
 *
 * @return string
 */
function sourceSettingsJS($filename, $number)
{
    switch(pathinfo($filename, PATHINFO_EXTENSION)) {
        case 'java':
        case 'c':
        case 'cpp':
        case 'h':
            $type = 'clike';
            break;
        case 'py':
            $type = 'python';
            break;
        default:
            $type = 'shell';
            break;
    }
    
    $number = intval($number);
    return '<script>
    var editor'.$number.' = CodeMirror.fromTextArea(document.getElementById("code'.$number.'"), {
        lineNumbers: true,
        readOnly: true,
        cursorHeight: 0.0,
        lineWrapping: true
    });

    editor'.$number.'.setSize("100%", "100%");
    editor'.$number.'.setOption("theme", "eclipse");
    editor'.$number.'.setOption("mode", "'.$type.'");

    $("#myTab a").click(function (e) {
        e.preventDefault();
        $(this).tab("show");
        setTimeout(function() { editor'.$number.'.refresh(); }, 1);
    });

    </script>';
}

function clean_string($str)
{
    // update grades_questions set grade_question_comment = regexp_replace(grade_question_comment, E'\r\n', '\n');
    // select * from grades_questions where 0 < position( E'\r\n' in grade_question_comment ) ORDER BY grade_question_comment ASC;

    $str = trim($str);
    $str = str_replace('\r\n', '\n', $str);
    $str = str_replace('\n', '\n', $str);
    $str = str_replace('\r', '\n', $str);
    $str = str_replace(PHP_EOL, '\n', $str);
    $str = str_replace("\x20\x0b", '\n', $str); # replace unicode character to prevent javascript errors.
    $str = str_replace("\x0d\x0a", '\n', $str); # replace unicode character to prevent javascript errors.
    
    return $str;    
}

function clean_string_javascript($str)
{
    $str = str_replace('"', '\"', $str);
    $str = str_replace('\\\"', '\"', $str);
    $str = str_replace("\r","",$str);
    $str = str_replace("\n","\"+\n\"",$str);

    return $str;            
}

/**
 * Given a path to a directory, this function checks to see if the directory exists, and if it doesn't tries to create it.
 *
 * @param $dir
 *
 * @return bool
 */
function create_dir($dir) {
    if (!is_dir($dir)) {
        return mkdir($dir);
    }
    return true;
}

/**
 * @return float
 */
function microtime_float() {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * @param $text
 *
 * @return array
 */
function pgArrayToPhp($text) {
    return \lib\DatabaseUtils::fromPGToPHPArray($text);
}

/**
 * @param $array
 *
 * @return string
 */
function phpToPgArray($array) {
    return \lib\DatabaseUtils::fromPHPToPGArray($array);
}

/**
 * @param $json
 *
 * @return mixed
 */
function removeTrailingCommas($json){
    $json=preg_replace('/,\s*([\]}])/m', '$1', $json);
    return $json;
}
?>