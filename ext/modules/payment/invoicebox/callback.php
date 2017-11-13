<?php
/*
	Invoicebox payment module
*/

	chdir('../../../../');
	require ('includes/application_top.php');
	
		$participantId 		= (int)$HTTP_POST_VARS["participantId"];
			$participantOrderId 	= (int)$HTTP_POST_VARS["participantOrderId"];
			
			
			if ( !($participantId && $participantOrderId )){
				die('Not participantOrderId');
			}
			$ucode 		= $HTTP_POST_VARS["ucode"];
			$timetype 	= $HTTP_POST_VARS["timetype"];
			$time 		= str_replace(' ','+',$HTTP_POST_VARS["time"]);
			$amount 	= $HTTP_POST_VARS["amount"];
			$currency 	= $HTTP_POST_VARS["currency"];
			$agentName 	= stripslashes($HTTP_POST_VARS["agentName"]);
			$agentPointName = stripslashes($HTTP_POST_VARS["agentPointName"]);
			$testMode 	= $HTTP_POST_VARS["testMode"];
			$sign	 	= $HTTP_POST_VARS["sign"];
            $orderid = $participantOrderId;
			
			$sign_strA = md5(
			$participantId .
			$participantOrderId .
			$ucode .
			$timetype .
			$time .
			$amount .
			$currency .
			$agentName .
			$agentPointName .
			$testMode .
			MODULE_PAYMENT_INVOICEBOX_DATA_INTEGRITY_CODE);
			
			if ($sign != $sign_strA) {
				die('Bad Sign');
			}
 
	
	// checking and handling


			$order_query = tep_db_query("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . (int)$participantOrderId . "'");

			if (tep_db_num_rows($order_query) > 0) {
				$order = tep_db_fetch_array($order_query);
				$total_query = tep_db_query("select value from ".TABLE_ORDERS_TOTAL." where orders_id = '".$participantOrderId."' and class = 'ot_total' limit 1");
    $total = tep_db_fetch_array($total_query);
			print_r(number_format($total['value']*$order['currency_value'], $currencies->get_decimal_places($order['currency'])));
			if (number_format($amount*$order['currency_value'], $currencies->get_decimal_places($order['currency'])) != number_format($total['value']*$order['currency_value'], $currencies->get_decimal_places($order['currency']))) 
			{
				die('Bad Amount');
			}
				if ($order['orders_status'] == MODULE_PAYMENT_INVOICEBOX_PREPARE_ORDER_STATUS_ID) {

					$order_status_id = (MODULE_PAYMENT_INVOICEBOX_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_INVOICEBOX_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID);

					tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . $order_status_id . "', last_modified = now() where orders_id = '" . (int)$participantOrderId . "'");

					$sql_data_array = array('orders_id' => $participantOrderId,
																	'orders_status_id' => $order_status_id,
																	'date_added' => 'now()',
																	'customer_notified' => '0',
																	'comments' => 'Invoicebox accepted this order payment');

					tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
				}
			}
			die("SUCCESS");
		
	
	require('includes/application_bottom.php');
?>
