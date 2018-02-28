<?php
	
	require_once(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/../../init.php');
	require_once('ps125_subiektgt_api.php');
	require_once('SubiektApi.php');
	$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
	echo '<pre>';
	$orders = $ps125_subiektgtapi->getOrdersToSend();	

	$subiektapi = new SubiektApi($ps125_subiektgtapi->getAPIKey(),$ps125_subiektgtapi->getAPIEndpoint());
	foreach($orders as $id_order=>$o){
		$fail = false;
		$ps125_subiektgtapi->lockOrder($id_order);
		$order_state = OrderHistory::getLastOrderState($id_order)->id;
		if($order_state == _PS_OS_CANCELED_){
			continue;
		}
		try{
			$result = $subiektapi->call('order/add',$o);			
			if(is_array($result)){
				$ps125_subiektgtapi->logEvent($id_order,'gt_order_sent',$result['state'],isset($result['message'])?$result['message']:json_encode($result['data']));
				if($result['state'] == 'fail'){
					$fail = true;
				}else{
					//zmiana statusu zamówienia na kompletowanie zamówienia. Jeśli zaakceptowana płatność.					
					if($order_state == _PS_OS_PAYMENT_){
						$oh = new OrderHistory();			
						$oh->id_order = $id_order;					
						$oh->id_employee = 0;
						$oh->changeIdOrderState(_PS_OS_PREPARATION_,$id_order);
						$oh->save();
					}
				}
			}else{
				$ps125_subiektgtapi->logEvent($id_order,'gt_order_sent','fail','Check server API logs!');			
				$fail = true;
			}
		}catch(Exception $e){
			$ps125_subiektgtapi->logEvent($id_order,'gt_order_sent','fail','Check server API logs!');			
			$fail = true;
		}
		if(!$fail){			
			$ps125_subiektgtapi->setSentOrderToSubiekt($id_order,$result['data']['order_ref']);
		}else{	
			$error_state = $ps125_subiektgtapi->getErrorOrderState();
			if($error_state>0){
				$oh = new OrderHistory();			
				$oh->id_order = $id_order;					
				$oh->id_employee = 0;
				$oh->changeIdOrderState($error_state,$id_order);
				$oh->save();	
			}
		}		
		$ps125_subiektgtapi->unlockOrder($id_order);
		print_r($result);	
	}
	print("Przetworzonych zamówień:".count($orders)."\n");
?>