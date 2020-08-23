<?php
	require_once('library/SimpleAmoApi.php');
	
	$amoAPI = new SimpleAmoApi();
	
	$account = $amoAPI->getAccount();
	$contacts = $amoAPI->getContacts('limit_rows=5');
	
	echo '<pre>';
	print_r($account);
	print_r($contacts);
	echo '</pre>';
