<?php
	
	require_once(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/../../init.php');
	require_once('ps125_subiektgt_api.php');
	require_once('SubiektApi.php');
	$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
	echo '<pre>';
	$orders = $ps125_subiektgtapi->getOrdersState();	

	$subiektapi = new SubiektApi($ps125_subiektgtapi->getAPIKey(),$ps125_subiektgtapi->getAPIEndpoint());
	foreach($orders as $id_order=>$o){
		$fail = false;		
		try{
			$result = $subiektapi->call('order/getstate',$o);
			if(is_array($result)){				
				if($result['state'] == 'fail'){
					$fail = true;
				}else{
					$order_state = OrderHistory::getLastOrderState($id_order)->id;
					if($result['data']['is_exists']==false){
						//$ps125_subiektgtapi->logEvent($id_order,'gt_check_order_state',$result['state'],'Dokument sprzedaży usunięty');		
						//$ps125_subiektgtapi->setRemoveDocSell($id_order);
					}elseif($result['data']['is_exists']==true && $result['data']['order_processing'] == 1){
						//zmiana statusu zamówienia na kompletowanie zamówienia. Jeśli zaakceptowana płatność.
						if($order_state == _PS_OS_PAYMENT_){
							$oh = new OrderHistory();			
							$oh->id_order = $id_order;					
							$oh->id_employee = 0;
							$oh->changeIdOrderState(_PS_OS_PREPARATION_,$id_order);
							$oh->save();
						}
						$ps125_subiektgtapi->logEvent($id_order,'gt_check_order_state',$result['state'],'Zamówienie przetwarzane');		
					}elseif($order_state == _PS_OS_CANCELED_ && strlen($o['doc_ref']) == 0){
						$subiektapi->call('document/delete',array('doc_ref'=>$o['order_ref']));
						$ps125_subiektgtapi->setRemoveOrder($id_order);						
						$ps125_subiektgtapi->logEvent($id_order,'gt_check_order_state',$result['state'],'Zamówienie anulowane');								
					}
				}
			}else{
				$ps125_subiektgtapi->logEvent($id_order,'gt_check_order_state','fail','Check server API logs!');			
				$fail = true;
			}
		}catch(Exception $e){
			$ps125_subiektgtapi->logEvent($id_order,'gt_check_order_state','fail','Check server API logs!');			
			$fail = true;
		}				
		print_r($result);	
	}
	print("Przetworzonych zamówień:".count($orders)."\n");
?>