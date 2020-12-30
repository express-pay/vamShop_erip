<?php
/*------------------------------------------------------------------------------
   $Id: easypay.php 998 2012/09/16 13:24:46 VaM $

   VaM Shop - open source ecommerce solution
   http://vamshop.ru
   http://vamshop.com

   Copyright (c) 2012 VamShop
  -----------------------------------------------------------------------------
   based on:
   (c) 2005 Vetal (robox.php,v 1.48 2003/05/27); metashop.ru

  Released under the GNU General Public License
------------------------------------------------------------------------------*/

require_once ('includes/application_top.php');
require_once (DIR_WS_CLASSES.'order.php');
require_once (DIR_FS_INC.'vam_send_answer_template.inc.php');

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
	echo('Test OK');
	die();
}


	// Обработка POST запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$json = $_POST['Data'];
	$signature = $_POST['Signature'];
	$tmp = str_replace ('amp;', null, $json);

	// Преобразуем из JSON в Object
	$data = json_decode(htmlspecialchars_decode ($tmp));
		
		// Проверяем использование цифровой подписи
		if(MODULE_PAYMENT_EXPRESSPAYERIP_USESIGNATUREFORNOTIF == 'True')
		{
			// Секретное слово указывается в настройках личного кабинета
			$secretWord = MODULE_PAYMENT_EXPRESSPAYERIP_SECRETWORDFORNOTIF;
		
			// Проверяем цифровую подпись
			if($signature == computeSignature($json, $secretWord))
			{			
				updateOrder($data);
				
				$status = 'OK | payment received';
				echo($status);
				header("HTTP/1.0 200 OK");
				die();
			}
			else
			{			
				$status = 'FAILED | wrong notify signature'; 
				echo($status);
				header("HTTP/1.0 400 Bad Request");
				die();
			}
		}
		else
		{
			updateOrder($data);

			$status = 'OK | payment received';
			echo($status);
			header("HTTP/1.0 200 OK");
			die();
		}
	}
	else
	{
		$status = 'FAILED | ID заказа неизвестен'; 
		echo($status);
		header("HTTP/1.0 200 Bad Request");
		die();
	}
  

function computeSignature($json, $secretWord)
{
    $hash = NULL;
    
	$secretWord = trim($secretWord);
	
    if (empty($secretWord))
		$hash = strtoupper(hash_hmac('sha1', $json, ""));
    else
        $hash = strtoupper(hash_hmac('sha1', $json, $secretWord));
    return $hash;
}

function updateOrder($data)
{
	$sql_data_array = array('orders_status' => MODULE_PAYMENT_EXPRESSPAYERIP_ORDER_STATUS_ID);
	vam_db_perform('orders', $sql_data_array, 'update', "orders_id='".$data->AccountNo."'");
  
	$sql_data_arrax = array('orders_id' => $data->AccountNo,
							'orders_status_id' => MODULE_PAYMENT_EXPRESSPAYERIP_ORDER_STATUS_ID,
							'date_added' => 'now()',
							'customer_notified' => '0',
							'comments' => 'EasyPay accepted this order payment');
	vam_db_perform('orders_status_history', $sql_data_arrax);

	 //Send answer template
	vam_send_answer_template($data->AccountNo,MODULE_PAYMENT_EXPRESSPAYERIP_ORDER_STATUS_ID,'on','on');

}

?>