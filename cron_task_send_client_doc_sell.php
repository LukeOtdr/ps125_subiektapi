<?php
	require_once(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/../../init.php');
	require_once('ps125_subiektgt_api.php');
	require_once('SubiektApi.php');
	

	$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
	echo '<pre>';

	$orders = $ps125_subiektgtapi->getOrdersReadyToSendBills();	
	foreach($orders as $o){
		$ps125_subiektgtapi->lockOrder($o['id_order']);
		if($ps125_subiektgtapi->sendEmailWithBill($o)){
			$ps125_subiektgtapi->setSentDocSellToClient($o['id_order']);
			//echo "Wysłał\n";		
			$oh = new OrderHistory();			
			$oh->id_order = $o['id_order'];					
			$oh->id_employee = 0;
			$oh->changeIdOrderState(_PS_OS_SHIPPING_,$o['id_order']);
			$oh->save();			
			$ps125_subiektgtapi->logEvent($o['id_order'],'sent_email_bill_to customer','ok','sent email');

		}else{
			echo "Nie wysłał";
			$ps125_subiektgtapi->logEvent($o['id_order'],'sent_email_bill_to customer','fail','email not sent');
		}
		$ps125_subiektgtapi->unlockOrder($o['id_order']);
	}
?>