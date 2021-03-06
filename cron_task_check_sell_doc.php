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
			$result = $subiektapi->call('order/get',$o);
			if(is_array($result)){
				$ps125_subiektgtapi->logEvent($id_order,'gt_check_sell_doc',$result['state'],isset($result['message'])?$result['message']:$result['data']['selling_doc']);		
				if($result['state'] == 'fail'){
					$fail = true;
				}
			}else{
				$ps125_subiektgtapi->logEvent($id_order,'gt_check_sell_doc','fail','Check server API logs!');			
				$fail = true;
			}
		}catch(Exception $e){
			$ps125_subiektgtapi->logEvent($id_order,'gt_check_sell_doc','fail','Check server API logs!');			
			$fail = true;
		}
		if(!$fail){	
			if(isset($result['data']['selling_doc']) && $result['data']['selling_doc']!=''){
				$ps125_subiektgtapi->setSentSellDocToSubiekt($id_order,$result['data']['selling_doc']);
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
	print("Przetworzonych zamówień:".count($orders)."\n");
?>