<?php
	
	require_once(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/../../init.php');
	require_once('ps125_subiektgt_api.php');
	require_once('SubiektApi.php');
	$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
	echo '<pre>';
	$orders = $ps125_subiektgtapi->getOrdersToCheckState();	

	$subiektapi = new SubiektApi($ps125_subiektgtapi->getAPIKey(),$ps125_subiektgtapi->getAPIEndpoint());
	foreach($orders as $id_order=>$o){
		$fail = false;		
		try{
			$result = $subiektapi->call('document/getstate',$o);
			if(is_array($result)){				
				if($result['state'] == 'fail'){
					$fail = true;
				}elseif($result['data']['is_exists']==false){
					$ps125_subiektgtapi->logEvent($id_order,'gt_check_doc_sell_status',$result['state'],'Dokument sprzedaży usunięty');		
					$ps125_subiektgtapi->setRemoveDocSell($id_order);
				}
			}else{
				$ps125_subiektgtapi->logEvent($id_order,'gt_check_sell_doc','fail','Check server API logs!');			
				$fail = true;
			}
		}catch(Exception $e){
			$ps125_subiektgtapi->logEvent($id_order,'gt_check_sell_doc','fail','Check server API logs!');			
			$fail = true;
		}		
		$ps125_subiektgtapi->unlockOrder($id_order);
		print_r($result);	
	}
	print("Przetworzonych zamówień:".count($orders)."\n");
?>