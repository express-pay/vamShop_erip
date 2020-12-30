<?php
include ('includes/application_top.php');
// create template elements
$vamTemplate = new vamTemplate;
// include boxes
require (DIR_FS_CATALOG.'templates/'.CURRENT_TEMPLATE.'/source/boxes.php');
require (DIR_WS_INCLUDES.'header.php');
$_SESSION['cart']->reset();

$param = isset($_REQUEST['param'])? $_REQUEST['param'] : '';

if($param == 'success'){
	$message = '<table style="width: 100%;text-align: left;">
	<tbody>
			<tr>
				<td valign="top" style="text-align:left;">
				<h3>Ваш номер заказа: ##ORDER_ID##</h3>
					Вам необходимо произвести платеж в любой системе, позволяющей проводить оплату через ЕРИП (пункты банковского обслуживания, банкоматы, платежные терминалы, системы интернет-банкинга, клиент-банкинга и т.п.).
					<br />
					<br />1. Для этого в перечне услуг ЕРИП перейдите в раздел:  <b>##ERIP_PATH##</b> <br />
					<br />2. В поле <b>"Номер заказа"</b> введите <b>##ORDER_ID##</b> и нажмите "Продолжить" <br />
					<br />3. Укажите сумму для оплаты <b>##AMOUNT##</b><br />
					<br />4. Совершить платеж.<br />
				</td>
					<td style="text-align: center;padding: 70px 20px 0 0;vertical-align: middle">
						##OR_CODE##
						<p><b>##OR_CODE_DESCRIPTION##</b></p>
						</td>
				</tr>
		</tbody>
	</table>
	<br />
	<input type="button" value="Продолжить" onClick=\'location.href="##HOME_URL##"\'>';
}else{
	$message = 	'<br />
	<h3>Ваш номер заказа: ##ORDER_ID##</h3>
	<p>При выполнении запроса произошла непредвиденная ошибка. Пожалуйста, повторите запрос позже или обратитесь в службу технической поддержки магазина</p>
	<input type="button" value="Продолжить" onClick=\'location.href="##HOME_URL##"\'>';
}

$message = str_replace('##ORDER_ID##', $_REQUEST['ExpressPayAccountNumber'],  $message);
$message = str_replace('##ERIP_PATH##', MODULE_PAYMENT_EXPRESSPAYERIP_PATHTOERIP,  $message);
$message = str_replace('##AMOUNT##', $_REQUEST['amount'],  $message);
$message = str_replace('##HOME_URL##', vam_href_link("index.php"),  $message);

if (MODULE_PAYMENT_EXPRESSPAYERIP_SHOWQRCODE == "True") {
	$qr_code = getQrCode($_REQUEST['ExpressPayInvoiceNo'], MODULE_PAYMENT_EXPRESSPAYERIP_SECRETWORD, MODULE_PAYMENT_EXPRESSPAYERIP_TOKEN);
	$message = str_replace('##OR_CODE##', '<img src="data:image/jpeg;base64,' . $qr_code . '"  width="200" height="200"/>',  $message);
	$message = str_replace('##OR_CODE_DESCRIPTION##', 'Отсканируйте QR-код для оплаты',  $message);
} else {
	$message = str_replace('##OR_CODE##', '',  $message);
	$message = str_replace('##OR_CODE_DESCRIPTION##', '',  $message);
}

	// Отправка GET запроса
	function sendRequestGET($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

		//Получение Qr-кода
	function getQrCode($ExpressPayInvoiceNo, $secretWord, $token)
	{
		$request_params_for_qr = array(
			"Token" => $token,
			"InvoiceId" => $ExpressPayInvoiceNo,
			'ViewType' => 'base64'
		);
		$request_params_for_qr["Signature"] = compute_signature($request_params_for_qr, $secretWord, $token, 'get_qr_code');

		$request_params_for_qr  = http_build_query($request_params_for_qr);
		$response_qr = sendRequestGET('https://api.express-pay.by/v1/qrcode/getqrcode/?' . $request_params_for_qr);
		$response_qr = json_decode($response_qr);
		$qr_code = $response_qr->QrCodeBody;
		return $qr_code;
	}

		//Вычисление цифровой подписи
	function compute_signature($request_params, $secret_word, $token, $method = 'add_invoice')
	{
		$secret_word = trim($secret_word);
		$normalized_params = array_change_key_case($request_params, CASE_LOWER);
		$api_method = array(
			'add_invoice' => array(
				"serviceid",
				"accountno",
				"amount",
				"currency",
				"expiration",
				"info",
				"surname",
				"firstname",
				"patronymic",
				"city",
				"street",
				"house",
				"building",
				"apartment",
				"isnameeditable",
				"isaddresseditable",
				"isamounteditable",
				"emailnotification",
				"smsphone",
				"returntype",
				"returnurl",
				"failurl"
			),
			'get_qr_code' => array(
				"invoiceid",
				"viewtype",
				"imagewidth",
				"imageheight"
			),
			'add_invoice_return' => array(
				"accountno",
				"invoiceno"
			)
		);
	
		$result =  $token;
	
		foreach ($api_method[$method] as $item)
			$result .= (isset($normalized_params[$item])) ? $normalized_params[$item] : '';
	
			$hash = strtoupper(hash_hmac('sha1', $result, $secret_word));
	
			return $hash;
	}

$vamTemplate->assign('MESSAGE', $message);


$vamTemplate->assign('language', $_SESSION['language']);
$vamTemplate->assign('PAYMENT_BLOCK', $payment_block);
$vamTemplate->caching = 0;
$main_content = $vamTemplate->fetch('expresspayerip_result.html');

$vamTemplate->assign('language', $_SESSION['language']);
$vamTemplate->assign('main_content', $main_content);
$vamTemplate->caching = 0;
if (!defined(RM)) $vamTemplate->loadFilter('output', 'note');
$template = (file_exists('templates/'.CURRENT_TEMPLATE.'/'.FILENAME_CHECKOUT_SUCCESS.'.html') ? CURRENT_TEMPLATE.'/'.FILENAME_CHECKOUT_SUCCESS.'.html' : CURRENT_TEMPLATE.'/index.html');
$vamTemplate->display($template);
include ('includes/application_bottom.php');
