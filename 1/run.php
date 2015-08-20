<?php
	ignore_user_abort(); //继续执行php代码
	set_time_limit(0); //程序执行时间无限制
	$interval = 60*30;; //多长时间执行一次
	if (!file_exists('tmp')) {
		mkdir('tmp', 0755);
	}
	do {
		$run = include 'cronconf.php';
		if (! $run) exit();
		$fp = fopen('tmp/test.txt','a+');
		fwrite($fp,'测试 '.date("Y-m-d H:i:s")."\n");
		fclose($fp);
		sleep($interval);
	} while(true);

	// 远程请求（不获取内容）函数，下面可以反复使用
	function _sock($url) {
		$host = parse_url($url,PHP_URL_HOST);
		$port = parse_url($url,PHP_URL_PORT);
		$port = $port ? $port : 80;
		$scheme = parse_url($url,PHP_URL_SCHEME);
		$path = parse_url($url,PHP_URL_PATH);
		$query = parse_url($url,PHP_URL_QUERY);
		if($query) $path .= '?'.$query;
		if($scheme == 'https') {
			$host = 'ssl://'.$host;
		}
		
		$fp = fsockopen($host,$port,$error_code,$error_msg,1);
		if(!$fp) {
			return array('error_code' => $error_code,'error_msg' => $error_msg);
		} else {
			stream_set_blocking($fp,true);//开启了手册上说的非阻塞模式
			stream_set_timeout($fp,1);//设置超时
			$header = "GET $path HTTP/1.1\r\n";
			$header.="Host: $host\r\n";
			$header.="Connection: close\r\n\r\n";//长连接关闭
			fwrite($fp, $header);
			usleep(1000); // 这一句也是关键，如果没有这延时，可能在nginx服务器上就无法执行成功
			fclose($fp);
			return array('error_code' => 0);
		}
	}
	