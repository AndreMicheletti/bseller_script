<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/* ************* FUNCTION DECLARATIONS ************ */

/* Make a curl request to a @url using @auth on header
	@return an array of stdClass (decoded from json) 
*/
function cURL_GET_REQUEST($url, $auth) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => $url,
		CURLOPT_USERAGENT => 'TrustedCompany cURL Request',
		CURLOPT_HTTPHEADER => array (
			'X-Auth-Token:' . $auth,
			'Content-Type:application/x-www-form-urlencoded'
		)
	));
	$resp = json_decode(curl_exec($curl));
	curl_close($curl);
	return $resp;
}

/* Generate a valid url to request the LIST API
	@return a string containing the url 
*/
function get_list_url($base_url, $startDate, $endDate, $page, $limit = '10', $status = 'ENT') {
	return $base_url . 'pedidos?dataInicial=' . $startDate . '&dataFinal=' . $endDate . '&status=' . $status .  '&page=' . $page .  '&limit=' . $limit;
}

/* Generate a valid url to request the Delivery Details API
	@return a string containing the url 
*/
function get_deliver_details_url($base_url, $id) {
	return $base_url . '/entregas/' . $id;
}

/* Generate a valid url to request the Client Details API
	@return a string containing the url 
*/
function get_client_details_url($base_url, $id) {
	return $base_url . '/clientes/' . $id;
}

/* ************************************************ */


$log = new Logger('name');
$log->pushHandler(new StreamHandler('logs/'. date('Y-m-d') . '.log', Logger::WARNING));

// MAIN ARRAY
$ACF_INVITES = array();

$BASE_URL = 'http://back.bseller.com.br/api/';
$X_AUTH_TOKEN = '3D93D027F587247BE05324F3A8C0438E';

$startDate = '2016-06-01';
$endDate = '2016-06-01';
$status = 'ENT';

$page = 0;
$list_obj = new stdClass();
$list_obj->totalPages = 1;

/* PAGE ITERATION */
while ($page < $list_obj->totalPages) {
	$page++;
	$list_obj = cURL_GET_REQUEST(get_list_url($BASE_URL, $startDate, $endDate, $page),$X_AUTH_TOKEN);
	
	/* ORDER ITERATION */
	foreach($list_obj->content as $order) {
		
		// Pull the deliver id and retrieve the details
		$deliver_id = $order->entrega;		
		$deliver_obj = cURL_GET_REQUEST(get_deliver_details_url($BASE_URL, $deliver_id),$X_AUTH_TOKEN);
		
		// Log the information
		$log->warning('Pulling order number ' . $deliver_id);
		
		// Pull the client id and retrieve the details
		$client_id  = $deliver_obj->idClienteGrupo;
		$client_obj = cURL_GET_REQUEST(get_client_details_url($BASE_URL, $client_id),$X_AUTH_TOKEN);

		$acf_order = array (
			'customerName' 	=> $client_obj->nome,	
			'customerEmail' => $client_obj->email,	
			'orderID'		=> $deliver_id,
			'dateCreated' 	=> $deliver_obj->dataEmissao,	
			'lastUpdated' 	=> $deliver_obj->dataAprovacao,	
			'phoneNumber' 	=> explode(' ',$client_obj->fone1)
		);
		
		array_push($ACF_INVITES, $acf_order);
	}
}

var_dump($ACF_INVITES);

?>