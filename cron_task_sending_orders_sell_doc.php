<?php
	
	require_once(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/../../init.php');
	require_once('ps125_subiektgt_api.php');
	require_once('SubiektApi.php');
	$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
	echo '<pre>';
	$orders = $ps125_subiektgtapi->getOrdersToMakeSellDoc();	
	
	$subiektapi = new SubiektApi($ps125_subiektgtapi->getAPIKey(),$ps125_subiektgtapi->getAPIEndpoint());
	foreach($orders as $id_order=>$o){
		$fail = false;
		$ps125_subiektgtapi->lockOrder($id_order);
		try{
			$result = $subiektapi->call('order/makesaledoc',$o);			
			if(is_array($result)){
				$ps125_subiektgtapi->logEvent($id_order,'gt_sell_doc_sent',$result['state'],isset($result['message'])?$result['message']:json_encode($result['data']));		
				if($result['state'] == 'fail'){
					$fail = true;
				}
			}else{
				$ps125_subiektgtapi->logEvent($id_order,'gt_sell_doc_sent','fail','Check server API logs!');			
				$fail = true;
			}
		}catch(Exception $e){
			$ps125_subiektgtapi->logEvent($id_order,'gt_sell_doc_sent','fail','Check server API logs!');			
			$fail = true;
		}
		if(!$fail){			
			$ps125_subiektgtapi->setSentSellDocToSubiekt($id_order,$result['data']['doc_ref']);
			$docsell_state = $ps125_subiektgtapi->getDocSellState();
			if($docsell_state>0){
				$oh = new OrderHistory();			
				$oh->id_order = $id_order;					
				$oh->id_employee = 0;
				$oh->changeIdOrderState($docsell_state,$id_order);
				$oh->save();	
			}

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
	print("Przetworzonych zamówień:".count($orders));
?>