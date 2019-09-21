<?php

if(  empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off"  ){
    exit("You should only serve this page over HTTPS. See <a href='https://www.troyhunt.com/heres-why-your-static-website-needs-https/' >Here's Why Your Static Website Needs HTTPS</a>");
}

$egregious = "<a href='https://ts.la/stuart17902' target='_blank'><button>Free Supercharging</button></a>";

if( isset( $_COOKIE['tesla_access_token'] ) ){

	define("ACCESS_TOKEN", 	$_COOKIE['tesla_access_token']);
	
	$url 	= 'https://owner-api.teslamotors.com/api/1/vehicles';
	
	$headers 	= [
		'Content-type: application/json',
		'Accept: application/json+v6',
		'User-Agent: https://github.com/norfolkmustard/tesla--owner-ap',	
		'Authorization: Bearer ' . ACCESS_TOKEN
	];
	
	$post_data = array();
	
	$vehicles = tesla( $url, $headers, $post_data );
	
	echo "<pre>";
	
	if( isset( $vehicles['tesla']['response'][0]['id_s'] ) ){
	
		$url 		= 'https://owner-api.teslamotors.com/api/1/vehicles/'.$vehicles['tesla']['response'][0]['id_s'].'/vehicle_data';
		$vehicle 	= tesla( $url, $headers, $post_data );	
		print_r($vehicle);
		print_r($vehicles);
	
	} else {
	
		print_r($vehicles);
	
	}
	
	echo "</pre>".$egregious;


} elseif( isset( $_POST['email'] ) && isset( $_POST['password'] ) ) {

	# Where will the request be sent to
	$url = 'https://owner-api.teslamotors.com/oauth/token';

	# -- Set up HTTP Headers
	$headers = [
		'Content-type: application/json',
		'Accept: application/json+v6',
		'User-Agent: https://github.com/norfolkmustard/tesla--owner-ap'	
	];

	# -- Set up the request data
	$post_data = array(
		"grant_type" 	=> "password",
		"client_id" 	=> "81527cff06843c8634fdc09e8ac0abefb46ac849f38fe1e431c2ef2106796384",
		"client_secret" => "c7257eb71a564034f9419ee651c7d0e5f7aa6bfbd18bafb5c5c033b093bb2fa3",
		"email"		=> $_POST['email'],
		"password"	=> $_POST['password']
	);
	
	$auth = tesla( $url, $headers, $post_data );
	
	if( isset( $auth['tesla']['access_token'] ) ){
	
		setcookie('tesla_access_token', $auth['tesla']['access_token'], time()+(60*60*24*44), '/', $_SERVER['HTTP_HOST'], true, true);
	
	}
	
	echo "<pre>";
	print_r( $auth );
	echo "</pre><p><a href='".$_SESSION['PHP_SELF']."'><button>Show Vehicles</button></a></p>";


} else {

	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'><input type='email' name='email' placeholder='Tesla account email address' ><input type='password' name='password' placeholder='password'><input type='submit' value='Retrieve access_token'></form> " ;

}


function tesla( $url, $headers = array(), $post = array() ){
	
	$return = array();

	# -- create a CURL handle containing the settings & data
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $url);
	
	if( !empty( $post ) ){
	
		$post_data_encoded	= json_encode($post);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data_encoded);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); 
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	
	curl_setopt($ch, CURLOPT_VERBOSE, true);
	
	$verbose = fopen('php://temp', 'w+');
	curl_setopt($ch, CURLOPT_STDERR, $verbose);

	# -- Make the request
	$result = curl_exec($ch);
	$curl_info = curl_getinfo($ch);
	curl_close($ch);
	
	if( $curl_info['http_code'] != 200 ){
	
		rewind($verbose);
		$verboseLog 		= stream_get_contents($verbose);
		$return['verbose_log'] 	= htmlspecialchars($verboseLog);
		$return['curl_info'] 	= print_r($curl_info, true);
				
	}


	# -- Try to decode the api response as json
	$result_json = json_decode($result, true);
	
	
	if( json_last_error() === JSON_ERROR_NONE ){
	
		$return['tesla'] = $result_json;
	
	} else {
	
		switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $return['json_error'] = ' - No errors';
        break;
        case JSON_ERROR_DEPTH:
            $return['json_error'] = ' - Maximum stack depth exceeded';
        break;
        case JSON_ERROR_STATE_MISMATCH:
            $return['json_error'] = ' - Underflow or the modes mismatch';
        break;
        case JSON_ERROR_CTRL_CHAR:
            $return['json_error'] = ' - Unexpected control character found';
        break;
        case JSON_ERROR_SYNTAX:
            $return['json_error'] = ' - Syntax error, malformed JSON';
        break;
        case JSON_ERROR_UTF8:
            $return['json_error'] = ' - Malformed UTF-8 characters, possibly incorrectly encoded';
        break;
        default:
            $return['json_error'] = ' - Unknown error';
        break;
    	}
    	
	
	}
	
	return $return;

}

