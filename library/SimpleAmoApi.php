<?php
/*
 * Простая PHP библиотека для работы с API AmoCRM
 * Документация Amocrm: https://www.amocrm.com/developers/content/api/account/
 * 
*/
class SimpleAmoApi {
	private $auth = [
		'subdomain' => 'SUBDOMAIN',
		'client_id' => 'CLIENT_ID',
		'client_secret' => 'CLIENT_SECRET',
		'grant_type' => 'authorization_code',
		'code' => 'AUTHORIZATION_CODE',
		'redirect_uri' => 'https://colin990.com',
	];
	
	private $tokens;
    
	function __construct() {
		if ( !$this->auth['client_secret'] ) self::ThrowError('Empty Auth Info');
		
		# Получаем access_token
		
		# Если есть файл с полученными ранее ключами
		if ( file_exists(__DIR__ . '/auth.json') ) {
			# Что бы не сверять время жизни ключа, просто генерируем новый
			$authInfo = json_decode( file_get_contents(__DIR__ . '/auth.json') );
			
			if ( !$authInfo->refresh_token ) self::ThrowError('Empty Refresh Token');
			
			# Обновляем access_token
			$newTokens = $this->refreshAccessToken( $authInfo->refresh_token );
			
			# Если новые токены есть, пишем их в файл
			if ( $newTokens->access_token ) {
				$newAuthJson = json_encode($newTokens);
				file_put_contents(__DIR__ . '/auth.json',$newAuthJson);
				
				$this->tokens = $newTokens;
			} else {
				self::ThrowError('Error. Cant generate new Access Token');
			}
		} else {
			
			# Генерируем первый раз ключи
			$newTokens = $this->getAccessToken();
			
			# Если новые токены есть, пишем их в файл
			if ( $newTokens->access_token ) {
				$newAuthJson = json_encode($newTokens);
				file_put_contents(__DIR__ . '/auth.json',$newAuthJson);
				
				$this->tokens = $newTokens;
			} else {
				self::ThrowError('Error. Cant generate Access Token');
			}
		}
		
			
		echo '<pre>';
		print_r($newTokens);
		//die();
		
		return;
	}
	
	function getAccessToken(){
		$data = [
			'client_id' => $this->auth['client_id'],
			'client_secret' => $this->auth['client_secret'],
			'grant_type' => 'authorization_code',
			'code' => $this->auth['code'],
			'redirect_uri' => $this->auth['redirect_uri'],
		];
		
		$newTokens = $this->SendRequest( 'oauth2/access_token', $data );
		
		return $newTokens;
	}
	
	function refreshAccessToken( $refreshToken ){
		$data = [
			'client_id' => $this->auth['client_id'],
			'client_secret' => $this->auth['client_secret'],
			'grant_type' => 'refresh_token',
			'refresh_token' => $refreshToken,
			'redirect_uri' => $this->auth['redirect_uri'],
		];
		
		$newTokens = $this->SendRequest( 'oauth2/access_token', $data );
		
		return $newTokens;
	}
	
	# https://www.amocrm.com/developers/content/api/account/
	function getAccount( $data = 'with=pipelines,groups,users,custom_fields' ){
		$method = 'api/v2/account';
		
		$response = $this->SendGETRequest( $method, $data );
		
		return $response;
	}
	
	# https://www.amocrm.com/developers/content/api/leads/
	function getLeads( $data = '' ){
		$method = 'api/v2/leads';
		
		$response = $this->SendGETRequest( $method, $data );
		
		return $response;
	}
	
	# https://www.amocrm.com/developers/content/api/contacts/
	function getContacts( $data = '' ){
		$method = 'api/v2/contacts';
		
		$response = $this->SendGETRequest( $method, $data );
		
		return $response;
	}
	
	private function SendRequest( $method = '', $data = [], $sendToken = 0 ) {
		if ( !$method ) self::ThrowError('No Method in POST Request');
		
		$url = 'https://' . $this->auth['subdomain'] . '.amocrm.ru/' . $method;

		if (!$curld = curl_init()) {
			self::ThrowError('Curl Error');
		}
		
		if ( $sendToken ) {
			$header = [
				'Authorization: Bearer ' . $this->tokens->access_token
			];
		} else {
			$header = [
				'Content-Type: application/json'
			];
		}
		
		$verbose = fopen('php://temp', 'w+');
		
		curl_setopt($curld,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curld,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
		curl_setopt($curld,CURLOPT_URL, $url);
		if ( $header ) {
			curl_setopt($curld,CURLOPT_HTTPHEADER,$header);
		}
		curl_setopt($curld,CURLOPT_HEADER, false);
		if ( !empty($data) ) {
			curl_setopt($curld,CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curld,CURLOPT_POSTFIELDS, json_encode($data));
		}
		curl_setopt($curld,CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curld,CURLOPT_SSL_VERIFYHOST, 2);
		
		$output = curl_exec($curld);
		curl_close($curld);
		
		if ( $output === FALSE ) {
			self::ThrowError("cUrl error: ".curl_errno($curld).' '.htmlspecialchars(curl_error($curld)));
		}

		rewind($verbose);
		$verboseLog = stream_get_contents($verbose);
		
		$response = json_decode($output);
		
		return $response;
	}
	
	private function SendGETRequest($method = '', $data = '' ){
		if ( !$method ) self::ThrowError('No Method in GET Request');
		
		$url = 'https://' . $this->auth['subdomain'] . '.amocrm.ru/' . $method;
		
		if ( $data ) :
			$url .= '?'. $data;
		endif;
		
		$opts = array(
			'http'=>array(
				'method'=>"GET",
				'header'=> 'Authorization: Bearer ' . $this->tokens->access_token
			)
		);

		$context = stream_context_create($opts);

		$response = file_get_contents($url, false, $context);
		
		return json_decode($response);
	}
	
	function ThrowError( $message ) {
		echo '<div style="margin: 30px 0; padding: 15px; text-align: center; color: #222; background: #ffdbdb;">';
		echo '<div style="margin: 0 0 10px; font-weight: 700;">Error:</div>';
		echo ( $message ) ? $message : 'Unknown problem';
		echo '</div>';
		die();
	}
}
