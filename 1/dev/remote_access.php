<?PHP
header('Content-Type:text/html;charset=utf-8');
header('Access-Control-Allow-Origin: *');
//ERROR_REPORTING(0);

$name = (isset($_GET['name']))?$_GET['name']:'';
$file = (isset($_GET['file']))?($_GET['file']):'';
$host = (isset($_GET['host']))?($_GET['host']):'';
$path = (isset($_GET['path']))?parse_path($_GET['path']):'';
$params = (isset($_GET['params']))?parse_params($_GET['params']):'';
$method = (isset($_GET['method']))?$_GET['method']:'GET';
$type = (isset($_GET['type']))?$_GET['type']:'';

$url = (isset($_GET['url']))?($_GET['url']):((substr($host,0,4)=='http')?$host:'http://'.$host).'/'.$path.((!empty($params))?'?'.$params:'');
$url = str_replace(' ', '%20', $url);
if (count(parse_url($url))<2) die('url not defined');
$type = strtolower($type);

if (!empty($file) and file_exists($file)) {
	$_file = fopen($file, "r") or die("\"$file\" not a local file");
	$filename = isset($name) ? $name : basename($file);
	
	Header("Content-type: application/octet-stream");
	Header("Accept-Ranges: bytes");
	Header("Accept-Length: ".filesize($file));
	Header("Content-Disposition: attachment; filename=$filename");
	
	echo fread($_file, filesize($file));
	fclose($_file);
	exit();
}

if ($type == 'text' or $type == 'view') {
	$opts = array(
		'http' => array(
			'method' => $method,
			'user_agent'=> $_SERVER['HTTP_USER_AGENT'],
			'timeout' => 10 * 3
		)
	);
	
	$context = stream_context_create($opts);
	$result = file_get_contents($url, false, $context);
	if ($type == 'view') Header("Content-type: image/png");
	exit($result);
} elseif ($type == 'html') {
	include_once('libraries/simple_html_dom.php');
	$html = new simple_html_dom();
	$html->load_file($url);
	$body = $html->find('body',0);
	$host = get_host($url);
	//$onload = (isset($body->onload))?($body->onload .';'):'';
	//$body->setAttribute('onbeforeunload', "window.location.href=http://ex.sunteorum.com/access?type=html&url=$host + window.location.href;");
	echo "<!--- {$body->onload} --->";
	echo $html;
	$html->clear();
	exit();
} elseif ($type == 'curl') {
	exit(curl_get($url));
} elseif ($type == 'save') {
	$file_name = (!empty($name))?$name:get_url_name($url);
	http_copy($url, $file_name)?exit("\"$file_name\" save success"):die('save failed');
} elseif ($type == 'test') {
	exit($path.'?'.$params);
} else {
	remote_download($url, $name);
}


function curl_get($url, $gzip=false) {
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
	if($gzip) curl_setopt($curl, CURLOPT_ENCODING, "gzip");
	$content = curl_exec($curl);
	curl_close($curl);
	return $content;
}

function http_copy($url, $file='', $method='GET', $timeout=60) {
	$root = str_replace("\\","/",dirname(realpath(dirname(__FILE__))));
	$path = $root.'/cache/downloads/';
    $file = empty($file) ? pathinfo($url, PATHINFO_BASENAME) : $file;
	$file = $path.$file;
    $dir = pathinfo($file, PATHINFO_DIRNAME);
    !is_dir($dir) && @mkdir($dir, 0755, true);
    $url = str_replace(" ", "%20", $url);
	
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $temp = curl_exec($ch);
        if(@file_put_contents($file, $temp) && !curl_error($ch)) {
            return $file;
        } else {
            return false;
        }
    } else {
        $opts = array(
            'http'=>array(
				'method'=>$method,
				'header'=>'',
				'user_agent'=> $_SERVER['HTTP_USER_AGENT'],
				'timeout'=>$timeout)
        );
        $context = stream_context_create($opts);
        if(@copy($url, $file, $context)) {
            //$http_response_header
            return $file;
        } else {
            return false;
        }
    }
}

function remote_download($url, $name) {
	$file_name = (!empty($name))?$name:get_url_name($url);
	$file = @fopen($url, 'r') or die("\"$url\" $file_name access error.");
	
	header('content-type: application/octet-stream');
	header('content-disposition: attachment; filename='.$file_name);
	while (!feof($file)) {
		echo fread($file, 10240);
	}
	fclose ($file);
	
}

function parse_path($path) {
	$path = implode('/', array_map('rawurlencode', explode('/', $path)));
	
	return $path;
}

function parse_params($params) {
	$params = explode(',', $params);
	$data = array();
	foreach ($params as $param) {
		$param = explode(':', $param);
		$data[$param[0]] = (count($param)==2)?rawurlencode($param[1]):'';
	}
	
	return http_build_query($data);
}

function get_url($url) {
	$arr = parse_url($url);
	$path = $arr['path'];
	$query = (isset($arr['query'])?'?'.$arr['query']:'');
	$fragment = (isset($arr['fragment'])?'#'.($arr['fragment']):'');
	
	return $arr['scheme'].'://'.$arr['host'].($path.$query.$fragment);
}

function get_host($url) {
	$arr = parse_url($url);
	
	return $arr['scheme'].'://'.$arr['host'].'/';
}

function get_url_name($url) {
	$arr = parse_url($url);
	$str = explode('/', $arr['path']);
	$count = count($str);
	if ($count == 0 or empty($str[$count-1])) return urlencode($url);
	else return urldecode($str[$count-1]);
} 

?>