<?php

set_time_limit(0);

$host = "172.28.21.45";
$port = 9000;
$null = NULL;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);//Making socket reusable in future
socket_bind($socket, $host, $port);
socket_listen($socket);
$clients = array();

while(true) {

	$new_socket = socket_accept($socket);//Accepting new connection/socket/client if any
	$clients[] = $new_socket;//Adding the new client/socket/connection to client array

	$header = socket_read($new_socket, 1024);
	perform_handshaking($header, $new_socket, $host, $port);

	/* If I want to notify if new connection is established**/
	socket_getpeername($new_socket, $ip);
	$message = "Welcome to WebSocket $ip";

	$array = array (
		'message' => $message
		);

	$message = mask(json_encode($array)); 
	write_to_socket($message);

	foreach($clients as $client) {

		while(socket_recv($client, $buf, 1024, 0) >= 1) {

			print_r($buf);
		}
	}
}
socket_close($socket);

function write_to_socket($message) {

	global $clients;

	foreach($clients as $client) {
		@socket_write($client, $message, strlen($message));
	}
}


//Following functions below functions are pre set and static, nothing to learn
//handshake new client.
function perform_handshaking($receved_header,$client_conn, $host, $port) {

	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line) {

		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {

			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//hand shaking header
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/server.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}
//Encode message for transfer to client.
function mask($text) {

	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}
 //Unmask incoming framed message
function unmask($text) {

	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}



