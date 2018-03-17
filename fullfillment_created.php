<?php

$SHOPIFY_WEBHOOK_SECRET = "c18d9524a9fa0b99d35c66e7810ee4e2d30b0709a34a2f3699541b13734c7e0c";
$API_KEY = "ba8302a422ad8aa25de221996279a503";
$PASSWORD = "95399b6e1a585e4e5ec568b2be752874";
$SHARED_SECRET ="c3fd28c7108fe3be7b7a2cefa608fe14";
$STOREFRONT_TOKEN="730b19ed2b454bfb4f94b7688465fd19";


function verifyWebhook($data, $hmacHeader)
{
	global $SHOPIFY_WEBHOOK_SECRET;
    $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $SHOPIFY_WEBHOOK_SECRET, true));
    return ($hmacHeader == $calculatedHmac);
}

function my_log($object){
	$req_dump = print_r( $object, true );
	file_put_contents( 'request.log', PHP_EOL . $req_dump, FILE_APPEND );

}


/**
 * Call Delivery API
 * @param  $fulfillment [description]
 * @return 
 */
function callDeliveryApi ($fulfillment)
{
    $delivery = new Paymentwall_GenerericApiObject('delivery');
    my_log("sending delivery data");
    return $delivery->post(prepareDeliveryData($fulfillment));
}
/**
 * Prepare Delivery Data
 * @param  $fulfillment
 * @return array
 */
function prepareDeliveryData ($fulfillment)
{
    $data = array(
        'payment_id' => $fulfillment['order_id'],
        'merchant_reference_id' => $fulfillment['order_id'],
        'status' => 'delivered',
        'estimated_delivery_datetime' => $fulfillment['created_at'],
        'estimated_update_datetime' => $fulfillment['updated_at'],
        'refundable' => true, //change to false if you don't support refund
        'details' => 'Item will be delivered by ' . $fulfillment['created_at'],
        'shipping_address[email]' => $fulfillment['email'],
        'shipping_address[firstname]' => $fulfillment['destination']['first_name'],
        'shipping_address[lastname]' => $fulfillment['destination']['last_name'],
        'shipping_address[country]' => $fulfillment['destination']['country'],
        'shipping_address[street]' => $fulfillment['destination']['address1'],
        'shipping_address[state]' => $fulfillment['destination']['province_code'] ? $fulfillment['destination']['province_code'] : 'NA',
        'shipping_address[phone]' => $fulfillment['destination']['phone'] ? $fulfillment['destination']['phone'] : 'NA',
        'shipping_address[zip]' => $fulfillment['destination']['zip'],
        'shipping_address[city]' => $fulfillment['destination']['city'],
        'carrier_type' => $fulfillment['tracking_company'],
        'reason' => 'none',
        'carrier_trackind_id' => $fulfillment['tracking_number'],
        'is_test' => 1 // change to 0 iff you're on live mode
    );
    
    if(!empty($fulfillment['destination'])) {
        $data['type'] = 'physical';
    } else {
        $data['type'] = 'digital';
    }
    return $data;
} 


header('Content-Type: application/json');
$request = file_get_contents('php://input');
my_log($request);





$hmacHeader = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];

$verified = verifyWebhook($request, $hmacHeader);

if (false === $verified)
{
    my_log('HMAC HASH not verified, exiting.'); 
    exit;
}else{
	my_log("webhook validated");
	if (!class_exists('Paymentwall_Config')) {
	    require_once(__DIR__ . '/paymentwall/lib/paymentwall.php');
	}

	Paymentwall_Config::getInstance()->set(array(
	    'private_key' => $PASSWORD,
	    'public_key' => $SHARED_SECRET
	));
	$webhook = $request;
	$fulfillment = json_decode($webhook, TRUE);
	callDeliveryApi($fulfillment);
}

?>