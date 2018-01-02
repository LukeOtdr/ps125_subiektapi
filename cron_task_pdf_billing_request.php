<?php
	
	require_once(dirname(__FILE__).'/../../config/config.inc.php');
	require_once(dirname(__FILE__).'/../../init.php');
	require_once('ps125_subiektgt_api.php');
	require_once('SubiektApi.php');
	$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
	echo '<pre>';
	$orders = $ps125_subiektgtapi->getOrdersToSendBills();	
	//print_r($orders);
	$subiektapi = new SubiektApi($ps125_subiektgtapi->getAPIKey(),$ps125_subiektgtapi->getAPIEndpoint());
	foreach($orders as $id_order=>$o){
		$fail = false;
		//$ps125_subiektgtapi->lockOrder($id_order);
		try{
			$result = $subiektapi->call('document/getstate',$o);
			if(is_array($result)){
				$ps125_subiektgtapi->logEvent($id_order,'gt_pdf_billing_request',$result['state'],isset($result['message'])?$result['message']:$result['data']['doc_ref']);
				if($result['state'] == 'fail'){
					$fail = true;
				}else{					
					$pdf_result = false;
					if($result['data']['doc_type'] == 'PA' && $result['data']['fiscal_state'] == 1 || true){
						
						$pdf_result = $subiektapi->call('document/getpdf',$o);
					}elseif($result['data']['doc_type'] == 'FS'){
						$pdf_result = $subiektapi->call('document/getpdf',$o);
					}
					//var_Dump($pdf_result);
					if(is_array($pdf_result) && $result['state'] == 'success'){						
						if($ps125_subiektgtapi->savePdf($pdf_result,$id_order)){
							$ps125_subiektgtapi->setGetPdf($id_order,$pdf_result['data']['file_name']);							
						}
					}else{
						$fail = true;
					}
					$ps125_subiektgtapi->logEvent($id_order,'gt_pdf_billing_request',$result['state'],isset($result['message'])?$result['message']:$result['data']['doc_ref']);
				}
			}else{
				$ps125_subiektgtapi->logEvent($id_order,'pdf_billing_request','fail','Check server API logs!');			
				$fail = true;
			}
		}catch(Exception $e){
			$ps125_subiektgtapi->logEvent($id_order,'pdf_billing_request','fail','Check server API logs!');			
			$fail = true;
		}
	
		if($fali){
			$error_state = $ps125_subiektgtapi->getErrorOrderState();
			if($error_state>0){
				$oh = new OrderHistory();			
				$oh->id_order = $id_order;					
				$oh->id_employee = 0;
				$oh->changeIdOrderState($error_state,$id_order);
				$oh->save();	
			}
		}		
		//$ps125_subiektgtapi->unlockOrder($id_order);
		print_r($result);	
	}
	print("Przetworzonych zamówień:".count($orders));
?>