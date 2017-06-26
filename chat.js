$(document).ready(function() {

	//create a new WebSocket object.
	var wsUri = "ws://172.28.21.45:9000"; 	
	var websocket = false;

	websocket = new WebSocket(wsUri);

	websocket.onopen = function(ev) {

		ev.preventDefault();
		 $(".notification").html('<a href="messagearchive.php"><img src="images/bell.svg" width="25px;"/><br/><span style="color: green;">Connected</span>    ');
	}

	websocket.onmessage = function(ev) {

		ev.preventDefault();
		console.log(ev.data);
	}
	websocket.onerror = function(ev)  {
		$(".notification").append('<p>Socket has error</p>');
	}
	websocket.onclose = function (ev) {
		$(".notification").append('<p>Socket is closed</p>');
	}	
});
