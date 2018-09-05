<?php
class Ps125_SubiektGT_Api extends Module {
	
	private $subiektgt_api;
	private $subiektgt_api_key = '';
	private $store_id = 1;
	private $auto_create_products = 1;
	private $order_prefix = '';
	private $error_state = 0;
	private $docsell_state = 0;
	private $unlock_state = 0;
	private $code_ship_cost = '';
	private $pdfs_directory = '';



	private $_postErrors = array();	

	public function __construct(){
		$this->name = 'ps125_subiektgt_api';
		$this->tab = 'Tools';
		$this->version = 0.1;
			
		parent::__construct();

		$this->displayName = $this->l('SubiektGT API Subiekta');
		$this->description = $this->l('Integruje zamówienia z SubiektGT + Sfera GT');

		$this->subiektgt_api = Configuration::get('SUBIEKTGT_API');
		$this->subiektgt_api_key = Configuration::get('SUBIEKTGT_API_KEY');
		$this->store_id = Configuration::get('SUBIEKTGT_API_STORE_ID');
		$this->auto_create_products = Configuration::get('SUBIEKTGT_API_AUTO_CREATE_PRO');			
		$this->order_prefix = Configuration::get('SUBIEKTGT_API_ORDER_PREFIX');	
		$this->error_state = Configuration::get('SUBIEKTGT_API_ERROR_STATE');	
		$this->docsell_state = Configuration::get('SUBIEKTGT_API_DOCSELL_STATE');	
		$this->unlock_state = Configuration::get('SUBIEKTGT_API_UNLOCK_STATE');
		$this->code_ship_cost = Configuration::get('SUBIEKTGT_API_CODE_SHIPCOST');					
		$this->pdfs_directory = Configuration::get('SUBIEKTGT_API_PDFS_DIR');	
	}
	
	public function install(){
		if (!parent::install() || 
				!$this->registerHook('newOrder') || !$this->registerHook('postUpdateOrderStatus')
				|| !$this->registerHook('adminOrder')
			)
			return false;
		$dml = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'subiektgt_api;';
		Db::getInstance()->Execute($dml);
		$dml = 'DROP TABLE IF EXISTS '._DB_PREFIX_.'subiektgt_api_log;';
		Db::getInstance()->Execute($dml);

		$dml1 = "CREATE TABLE `"._DB_PREFIX_."subiektgt_api` (			
			 `id_gt_order` int(11) NOT NULL AUTO_INCREMENT,
			 `id_order` int(11) NOT NULL,
			 `gt_order_sent` smallint(6) NOT NULL DEFAULT '0',
			 `gt_sell_doc_sent` smallint(6) NOT NULL DEFAULT '0',
			 `gt_sell_pdf_request` smallint(6) NOT NULL DEFAULT '0',
			 `email_sell_pdf_sent` smallint(6) NOT NULL DEFAULT '0',
			 `gt_order_ref` varchar(20) NOT NULL,
			 `gt_sell_doc_ref` varchar(20) NOT NULL,
			 `doc_file_pdf` varchar(50) NOT NULL,
			 `add_date` datetime NOT NULL,
			 `upd_date` datetime NOT NULL,
			 `is_locked` smallint(6) NOT NULL DEFAULT '0',
			 PRIMARY KEY (`id_gt_order`),
			 UNIQUE KEY `IDX` (`id_order`,`gt_order_ref`),
			 UNIQUE KEY `IDX3` (`id_order`,`is_locked`,`gt_order_sent`,`gt_sell_doc_sent`,`gt_sell_pdf_request`,`email_sell_pdf_sent`) USING BTREE,
			 KEY `IDX2` (`id_gt_order`,`gt_sell_doc_ref`)
			) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8
		";		

		$dml2 = "CREATE TABLE `"._DB_PREFIX_."subiektgt_api_log` (
			 `id_order` int(11) NOT NULL,
			 `event_type` varchar(30) NOT NULL,
			 `log_date` datetime NOT NULL,
			 `result` varchar(30) NOT NULL,
			 `result_desc` varchar(150) NOT NULL,
			 KEY `IDX` (`id_order`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		return Db::getInstance()->Execute($dml1) & Db::getInstance()->Execute($dml2);
	}
	
	public function uninstall(){
		return parent::uninstall();
	}

	public function getAPIKey(){
		return $this->subiektgt_api_key;
	}
	
	public function getAPIEndpoint(){
		return $this->subiektgt_api;
	}

	public function getPdfPath(){
		return $this->pdfs_directory;
	}

	public function getPdfFile($id_order){
		$sql  = 'SELECT doc_file_pdf FROM '._DB_PREFIX_.'subiektgt_api WHERE id_order = '.intval($id_order);
		var_dump($sql);
		return DB::getInstance()->getValue($sql);
	}

	private function _postProcess(){			
		if (isset($_POST['btnSubmit'])){
			Configuration::updateValue('SUBIEKTGT_API', Tools::getValue('SUBIEKTGT_API'));
			$this->subiektgt_api = Tools::getValue('SUBIEKTGT_API');
			
			Configuration::updateValue('SUBIEKTGT_API_KEY', Tools::getValue('SUBIEKTGT_API_KEY'));
			$this->subiektgt_api_key = Tools::getValue('SUBIEKTGT_API_KEY');

			Configuration::updateValue('SUBIEKTGT_API_STORE_ID', Tools::getValue('SUBIEKTGT_API_STORE_ID'));
			$this->store_id = Tools::getValue('SUBIEKTGT_API_STORE_ID');

			Configuration::updateValue('SUBIEKTGT_API_AUTO_CREATE_PRO', Tools::getValue('SUBIEKTGT_API_AUTO_CREATE_PRO'));
			$this->auto_create_products = Tools::getValue('SUBIEKTGT_API_AUTO_CREATE_PRO');

			Configuration::updateValue('SUBIEKTGT_API_ORDER_PREFIX', Tools::getValue('SUBIEKTGT_API_ORDER_PREFIX'));
			$this->order_prefix = Tools::getValue('SUBIEKTGT_API_ORDER_PREFIX');

			Configuration::updateValue('SUBIEKTGT_API_ERROR_STATE', Tools::getValue('SUBIEKTGT_API_ERROR_STATE'));
			$this->error_state = Tools::getValue('SUBIEKTGT_API_ERROR_STATE');

			Configuration::updateValue('SUBIEKTGT_API_UNLOCK_STATE', Tools::getValue('SUBIEKTGT_API_UNLOCK_STATE'));
			$this->unlock_state = Tools::getValue('SUBIEKTGT_API_UNLOCK_STATE');

			Configuration::updateValue('SUBIEKTGT_API_DOCSELL_STATE', Tools::getValue('SUBIEKTGT_API_DOCSELL_STATE'));
			$this->docsell_state = Tools::getValue('SUBIEKTGT_API_DOCSELL_STATE');

			Configuration::updateValue('SUBIEKTGT_API_CODE_SHIPCOST', Tools::getValue('SUBIEKTGT_API_CODE_SHIPCOST'));
			$this->code_ship_cost = Tools::getValue('SUBIEKTGT_API_CODE_SHIPCOST');

			Configuration::updateValue('SUBIEKTGT_API_PDFS_DIR', Tools::getValue('SUBIEKTGT_API_PDFS_DIR'));
			$this->pdfs_directory = Tools::getValue('SUBIEKTGT_API_PDFS_DIR');			

		}
	}
	
	private function _postValidation(){
		$subiektgt_api = Tools::getValue('SUBIEKTGT_API');
		$subiektgt_api_key = Tools::getValue('SUBIEKTGT_API_KEY');
		$store_id = Tools::getValue('SUBIEKTGT_API_STORE_ID');
		$auto_create_products = Tools::getValue('SUBIEKTGT_API_AUTO_CREATE_PRO');
		$code_ship_cost = Tools::getValue('SUBIEKTGT_API_CODE_SHIPCOST');
		$pdfs_directory = Tools::getValue('SUBIEKTGT_API_PDFS_DIR');	
				
		
		if("" == $subiektgt_api){			
			$this->_postErrors[]=$this->l('Podaj adres http API subiektaGT');			
		}
		if("" == $subiektgt_api_key){			
			$this->_postErrors[]=$this->l('Podaj klucz API');			
		}
		
		if(0 == intval($store_id)){			
			$this->_postErrors[]=$this->l('Wporwadź poprawy id magazynu z którego będą rezerwowane stany');			
		}

		if("" == $code_ship_cost){			
			$this->_postErrors[]=$this->l('Podaj kod usługi transportowej z Subiekta');			
		}

		if("" == $pdfs_directory){			
			$this->_postErrors[]=$this->l('Podaj katalog do przechowywania dokumentów sprzedaży PDF');			
		}
	}
		
	private function _displayForm(){ 
	global $cookie;
	$order_states = OrderState::getOrderStates($cookie->id_lang);
	//print_r($order_states);SUBIEKTGT_API_CODE_SHIPCOST
	$this->_html .=
		'<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
			<fieldset><legend>Subiekt integracja z API</legend>								
				<label style="width:350px;">Podaj adres http API SubiektaGT</label>
				<div style="margin-left:400px;margin-bottom:5px;margin-top:5px;"><input name="SUBIEKTGT_API" value="'.$this->subiektgt_api.'" size="80" type="text"></div>
				<label style="width:350px;">Podaj klucz API SubiektaGT</label>
				<div style="margin-left:400px;margin-bottom:5px;margin-top:5px;"><input name="SUBIEKTGT_API_KEY" value="'.$this->subiektgt_api_key.'" size="50" type="text"></div>
				<label style="width:350px;">Prefix dla nr zamówień on-line:</label>
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;"><input name="SUBIEKTGT_API_ORDER_PREFIX" value="'.$this->order_prefix.'" size="10" type="text"></div>
				<label style="width:350px;">Kod usługi transportowej subiektGT</label>								
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;"><input name="SUBIEKTGT_API_CODE_SHIPCOST" value="'.$this->code_ship_cost.'" size="30" type="text"></div>				
				<label style="width:350px;">Identyfikator magazynu SubiektGT do rezerwacji produktów</label>
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;"><input name="SUBIEKTGT_API_STORE_ID" value="'.$this->store_id.'" size="20" type="text"></div>
				<label style="width:350px;">Zmień status zamówienia gdy wystąpi problem z komunikacją:</label>				
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;">
					<select name="SUBIEKTGT_API_ERROR_STATE">
					<option value="0" selected>-- nie zmieniaj --</option>
					';
					foreach($order_states as $os){
						$this->_html.= "<option value=\"{$os['id_order_state']}\" ".($this->error_state == $os['id_order_state']?"selected":"").">{$os['name']}</option>";
					}
	$this->_html .='</select>	
				</div>
				<label style="width:350px;">Zmień status gdy wygeneruje PA lub FS:</label>				
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;">
					<select name="SUBIEKTGT_API_DOCSELL_STATE">
					<option value="0" selected>-- nie zmieniaj --</option>
					';
					foreach($order_states as $os){
						$this->_html.= "<option value=\"{$os['id_order_state']}\" ".($this->docsell_state == $os['id_order_state']?"selected":"").">{$os['name']}</option>";
					}
	$this->_html .='</select>
				</div>	
				<label style="width:350px;">Zmień status odblokowujące przetwarzanie:</label>				
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;">
					<select name="SUBIEKTGT_API_UNLOCK_STATE">
					<option value="0" selected>-- nie zmieniaj --</option>
					';
					foreach($order_states as $os){
						$this->_html.= "<option value=\"{$os['id_order_state']}\" ".($this->unlock_state == $os['id_order_state']?"selected":"").">{$os['name']}</option>";
					}
	$this->_html .='</select>	
				</div>

				<label style="width:350px;">Autmatycznie tworzyć nowe produkty</label>
				<div style="margin-left:400px;margin-bottom:5px;margin-top:5px;margin-bottom:30px;"><input name="SUBIEKTGT_API_AUTO_CREATE_PRO" type="radio" value="1" '.(1==intval($this->auto_create_products)?'checked':'').'> Tak
					<input name="SUBIEKTGT_API_AUTO_CREATE_PRO" type="radio" value="0" '.(0==intval($this->auto_create_products)?'checked':'').'> Nie
				</div>

				<label style="width:350px;margin-top:">Katalog do przechowywania dokumentów sprzedaży PDFs</label>
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;"><input name="SUBIEKTGT_API_PDFS_DIR" value="'.$this->pdfs_directory.'" size="50" type="text"></div>
				<center><input type="submit" class="button" value="'.$this->l('Zapisz dane').'" name="btnSubmit"></center>				
			</fieldset>
		</form>';
	}
	
	public function getContent()
	{
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		if (!empty($_POST))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= '<div class="alert error">'. $err .'</div>';
		}
		else
			$this->_html .= '<br />';

		$this->_displayForm();

		return $this->_html;
	}
	
	public function hooknewOrder($params){										
		$dml = 'INSERT INTO '._DB_PREFIX_.'subiektgt_api VALUES(0,'.$params['order']->id.',0,0,0,0,\'\',\'\',\'\',NOW(),NOW(),0)';		
		DB::getInstance()->Execute($dml);	
	}

	public function getErrorOrderState(){
		return $this->error_state;
	}

	public function getDocSellState(){
		return $this->docsell_state;
	}

	public function getOrdersToSend(){
		$SQL = 'SELECT id_order FROM '._DB_PREFIX_.'subiektgt_api WHERE gt_order_sent = 0 AND is_locked = 0 LIMIT 20;';
		$orders = array();
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			//$order = new Or			
			$order_obj = new Order($order['id_order']);
			$products = $order_obj->getProductsDetail();
			$address = false;			
			if($order_obj->id_address_delivery!=$order_obj->id_address_delivery){
				$address = new Address($order_obj->id_address_delivery);
			}else{
				$address = new Address($order_obj->id_address_delivery);
			}
			$customer = new Customer($order_obj->id_customer);
			$carrier = new Carrier($order_obj->id_carrier);
			$messages = Message::getMessagesByOrderId($order_obj->id,true);
			$count_msg = count($messages);
			$orders[$order['id_order']] = array(
						'comments' => "Wysyłka: ".$carrier->name.", Płatność: ".$order_obj->payment." ".($count_msg>0?", Komentarze do zam.".$count_msg:''),
						'reference' => $this->order_prefix.' '.$order_obj->id,
						'create_product_if_not_exists' => $this->auto_create_products,
						'amount' => round($order_obj->total_paid_real,2),
						'pay_type' => 'transfer', // 'cart','money','credit'
						'customer'=>array(
							'firstname' => $address->firstname,
							'lastname' => $address->lastname,
							'email' => $this->emailFix($customer->email),							
							'address' => $address->address1,
							'address_no' => $address->address2,
							'city' => $address->city,
							'post_code' => $address->postcode,
							'phone' => $address->phone_mobile,
							'ref_id' => $this->order_prefix.'CUST '.$customer->id,
							'is_company' => strlen($address->tax_identity)>0?true:false,
							'company_name' => $address->company,
							'tax_id' => strlen($address->tax_identity)>0?$address->tax_identity:'',
						),

			);
			$orders[$order['id_order']]['products'] = array();
			foreach($products as $p){
				$price = round($p['product_price']*(1+0.01*$p['tax_rate']),2);				
				$a_p = array(						
						//'ean'=>$p['product_ean13'],
						'code'=>strlen($p['product_ean13'])>0?$p['product_ean13']:$p['product_supplier_reference'],
						'qty'=> $p['product_quantity'],
						'price' => $price,
						//stime_of_delivery' => 2,
						'supplier_code' => strlen($p['product_supplier_reference'])>0?$p['product_supplier_reference']:$p['product_reference'],
						'price_before_discount' => $price,
						'name' => $p['product_name'],
						'id_store' => $this->store_id,
				);
				array_push($orders[$order['id_order']]['products'],$a_p);
			}
			if($order_obj->total_shipping>0){
				$a_sp = array(
						'ean'=>$this->code_ship_cost,
						'code'=>$this->code_ship_cost,
						'qty'=> 1,
						'price' => $order_obj->total_shipping,
						'price_before_discount' => $order_obj->total_shipping,
						'name' => 'Koszty wysyłki',
						'id_store' => $this->store_id,
				);
				array_push($orders[$order['id_order']]['products'],$a_sp);
			}
			//var_Dump($order['id_order']);
		}		
		return $orders;
	}


	public function getOrdersToMakeSellDoc(){
		$SQL = 'SELECT id_order,gt_order_ref FROM '._DB_PREFIX_.'subiektgt_api 
				WHERE gt_order_sent = 1 AND gt_sell_doc_sent = 0 AND is_locked = 0 
				-- AND upd_date<ADDDATE(NOW(), INTERVAL -5 MINUTE)
				LIMIT 100';
		$orders = array();		
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			$orders[$order['id_order']]['order_ref'] = $order['gt_order_ref'];
		}		
		return $orders;
	}


	public function getOrdersToSendBills(){
		$SQL = 'SELECT id_order, gt_sell_doc_ref FROM '._DB_PREFIX_.'subiektgt_api 
				WHERE gt_order_sent = 1 AND gt_sell_doc_sent = 1 
				AND 	gt_sell_pdf_request  = 0 AND is_locked = 0 	
				-- AND upd_date<ADDDATE(NOW(), INTERVAL -8 MINUTE) 
				AND upd_date>ADDDATE(NOW(),INTERVAL -1 MONTH)			
				LIMIT 100';
		$orders = array();		
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			$orders[$order['id_order']]['doc_ref'] = $order['gt_sell_doc_ref'];
		}			
		return $orders;
	}

	public function getOrdersToCheckState(){
		$SQL = 'SELECT id_order, gt_sell_doc_ref FROM '._DB_PREFIX_.'subiektgt_api 
				WHERE gt_order_sent = 1 AND gt_sell_doc_sent = 1 
				AND 	gt_sell_pdf_request  = 0 AND is_locked = 0 					
				AND upd_date>ADDDATE(NOW(), INTERVAL -60 MINUTE)
				LIMIT 100';
		$orders = array();		
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			$orders[$order['id_order']]['doc_ref'] = $order['gt_sell_doc_ref'];
		}			
		return $orders;
	}


	public function getOrdersState(){
		$SQL = 'SELECT id_order, gt_order_ref,gt_sell_doc_ref FROM '._DB_PREFIX_.'subiektgt_api 
				WHERE gt_order_sent = 1 AND gt_sell_doc_sent = 0 
				AND 	gt_sell_pdf_request  = 0 	
				AND upd_date>ADDDATE(NOW(), INTERVAL -1 HOUR)
				ORDER BY id_order
				LIMIT 200';
		$orders = array();		
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			$orders[$order['id_order']]['order_ref'] = $order['gt_order_ref'];
			$orders[$order['id_order']]['doc_ref'] = $order['gt_sell_doc_ref'];
		}			
		return $orders;
	}


	public function getOrdersReadyToSendBills(){
		$SQL = 'SELECT id_order, doc_file_pdf FROM '._DB_PREFIX_.'subiektgt_api 
				WHERE gt_order_sent = 1 AND gt_sell_doc_sent = 1 
				AND 	gt_sell_pdf_request  = 1 AND email_sell_pdf_sent = 0 AND is_locked = 0 				
				LIMIT 100';
		$orders = array();		
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $o){				
			$order_obj = new Order($o['id_order']);
			
			$address = new Address($order_obj->id_address_delivery);
			$customer = new Customer($order_obj->id_customer);
			
			$orders[$o['id_order']]['file_name'] = $o['doc_file_pdf'];
			$orders[$o['id_order']]['email'] = $customer->email;
			$orders[$o['id_order']]['firstname'] = trim($customer->firstname);
			$orders[$o['id_order']]['lastname'] = $customer->lastname;
			$orders[$o['id_order']]['id_order'] = $order_obj->id;
			$orders[$o['id_order']]['add_date'] = $order_obj->date_add;
		}			
		return $orders;
	}

	public function savePdf($pdf,$id_order){
		$filename = $this->pdfs_directory.'/'.$pdf['data']['file_name'];
		if(isset($pdf['data']['pdf_file'])){
			print_r($filename);
			
			$result = @file_put_contents($filename, base64_decode($pdf['data']['pdf_file']));
			if(!$result){
				$this->logEvent($id_order,'internal_error','fail','Brak zapisu do: '.$this->pdfs_directory);
				return false;
			}
		
		}
		return true;
	}


	public function sendEmailWithBill($order_data){
		//$order_data['email'] = 'lukasz.golonka@gmail.com';
		include_once(_PS_SWIFT_DIR_.'Swift.php');
		include_once(_PS_SWIFT_DIR_.'Swift/Connection/SMTP.php');
		include_once(_PS_SWIFT_DIR_.'Swift/Connection/NativeMail.php');
		include_once(_PS_SWIFT_DIR_.'Swift/Plugin/Decorator.php');
		try{
			
			$configuration = Configuration::getMultiple(array('PS_SHOP_EMAIL', 'PS_MAIL_METHOD', 'PS_MAIL_SERVER', 'PS_MAIL_USER', 'PS_MAIL_PASSWD', 'PS_SHOP_NAME', 'PS_MAIL_SMTP_ENCRYPTION', 'PS_MAIL_SMTP_PORT', 'PS_MAIL_METHOD', 'PS_MAIL_TYPE'));
			$message = '';
			if (intval($configuration['PS_MAIL_METHOD']) == 2)
			{
				$connection = new Swift_Connection_SMTP($configuration['PS_MAIL_SERVER'], $configuration['PS_MAIL_SMTP_PORT'], ($configuration['PS_MAIL_SMTP_ENCRYPTION'] == "ssl") ? Swift_Connection_SMTP::ENC_SSL : (($configuration['PS_MAIL_SMTP_ENCRYPTION'] == "tls") ? Swift_Connection_SMTP::ENC_TLS : Swift_Connection_SMTP::ENC_OFF));
				$connection->setTimeout(10);
				if (!$connection)
					return false;
				if (!empty($configuration['PS_MAIL_USER']) AND !empty($configuration['PS_MAIL_PASSWD']))
				{
					$connection->setUsername($configuration['PS_MAIL_USER']);
					$connection->setPassword($configuration['PS_MAIL_PASSWD']);
				}
			}
			else
				$connection = new Swift_Connection_NativeMail();

			if (!$connection)
				return false;
			$swift = new Swift($connection);


			$from = $configuration['PS_SHOP_EMAIL'];
			$fromName = $configuration['PS_SHOP_NAME'];
			$templateHtml = file_get_contents(dirname(__FILE__).'/message_tpl/bill_pdf.html');			
			$subject = "Dziękujemy za zakupy w Outdoorzy.pl";
			$to = $this->emailFix($order_data['email']);
			$to_plugin = $to;

			/* Create mail and attach differents parts */
			$message = new Swift_Message($subject);

			foreach($order_data as $key=>$value){
				$templateVars["{$key}"] = $value;
			}
						
			$templateVars['{shop_name}'] = Configuration::get('PS_SHOP_NAME');
			$templateVars['{shop_url}'] = 'http://'.htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8').__PS_BASE_URI__;
			$swift->attachPlugin(new Swift_Plugin_Decorator(array($to_plugin => $templateVars)), 'decorator');

			$message->attach(new Swift_Message_Part($templateHtml, 'text/html', '8bit', 'utf-8'));



			$fileAttachment['content'] = file_get_contents($this->pdfs_directory.'/'.$order_data['file_name']);
			$fileAttachment['name'] = $order_data['file_name'];
			$fileAttachment['mime'] = 'application/pdf';

			if ($fileAttachment AND isset($fileAttachment['content']) AND isset($fileAttachment['name']) AND isset($fileAttachment['mime']))
				$message->attach(new Swift_Message_Attachment($fileAttachment['content'], $fileAttachment['name'], $fileAttachment['mime']));
			/* Send mail */
			$send = $swift->send($message, $to, new Swift_Address($from, $fromName));
			$swift->disconnect();
			return $send;
			}catch(Exception $e){
				error_log($e->getMessage());
				error_log($e->getFile());
				error_log($e->getTraceAsString());
				error_log($e->getCode());
				error_log($e->getLine());
				error_log(var_export($message,true));
				var_dump($e->getMessage());
				return false;
			}
		return false;
	}


	public function setGetPdf($id_order,$filename){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_sell_pdf_request = 1, upd_date = NOW(),	doc_file_pdf = \''.$filename.'\' WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	static protected function emailFix($email){
		return eregi_replace("[0-9]+-_-","",$email);
	}

	static public function logEvent($id_order,$type,$result,$result_desc){
		$DML = "INSERT INTO "._DB_PREFIX_."subiektgt_api_log VALUES({$id_order},'{$type}',NOW(),'{$result}','".pSQL($result_desc)."')";		
		return DB::getInstance()->Execute($DML);
	}

	public function unlockOrder($id_order,$order_state = 0){
		$unlockOrder = false;
		if($order_state == 0){
			$order_state = OrderHistory::getLastOrderState($id_order)->id;			
		}

		switch ($order_state) {
			case _PS_OS_PAYMENT_:
					$unlockOrder = true;
				break;
			case _PS_OS_PREPARATION_:
					$unlockOrder = true;
				break;
			case $this->unlock_state:
					$unlockOrder = true;
				break;	
			case $this->docsell_state:
					$unlockOrder = true;
				break;									
		}
		
		if($unlockOrder){
			$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET is_locked = 0, upd_date = NOW() WHERE id_order = '.$id_order;			
			return DB::getInstance()->Execute($DML);
		}		
		return false;
	}


	static public function setSentOrderToSubiekt($id_order,$ref_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_order_sent = 1, upd_date = NOW(),gt_order_ref = \''.$ref_order.'\' WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	static public function setRemoveDocSell($id_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_sell_doc_sent = 0,email_sell_pdf_sent = 0,gt_sell_pdf_request = 0, upd_date = NOW(),gt_sell_doc_ref = \'\' WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}

	static public function setRemoveOrder($id_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_sell_doc_sent = 0,email_sell_pdf_sent = 0,gt_sell_pdf_request = 0, upd_date = NOW(),gt_order_ref = \'\',is_locked = 1 WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}

	static public function setSentSellDocToSubiekt($id_order,$ref_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_sell_doc_sent = 1, upd_date = NOW(),gt_sell_doc_ref = \''.$ref_order.'\' WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}

	static public function setSentDocSellToClient($id_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET email_sell_pdf_sent = 1, upd_date = NOW() WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	public function lockOrder($id_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET is_locked = 1, upd_date = NOW() WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	public function hookpostUpdateOrderStatus($params){
		return $this->unlockOrder($params['id_order'],$params['newOrderStatus']->id);		
	}

	public function hookAdminOrder($params){
		global $smarty;
		
		$order = new Order($params['id_order']);
		$SQL = 'SELECT * FROM  '._DB_PREFIX_.'subiektgt_api WHERE id_order = '.$params['id_order'];
		$gt_state = DB::getInstance()->getRow($SQL);
		$SQL = 'SELECT * FROM  '._DB_PREFIX_.'subiektgt_api_log WHERE id_order = '.$params['id_order'].' ORDER BY log_date DESC';
		$logs = DB::getInstance()->ExecuteS($SQL);			
		
		 $smarty->assign(array(
		 	'module_path' => _MODULE_DIR_.$this->name.'/',
		 	'id_order' => $params['id_order'],		 	
		 	'gtState' => $gt_state,
		 	'logs' => $logs
		// 		'arrayProducts' => $arrayProducts
		 ));
		return $this->display(__FILE__, 'AdminOrder.tpl');	
	}
	

	public function productQtyUpdate($ean13, $qty){
	  if(strlen($ean13)==0){
	  	return false;
	  };
	  
	  Db::getInstance()->Execute('UPDATE '._DB_PREFIX_.'product_attribute pa, '._DB_PREFIX_.'product p
	    SET pa.quantity = '.$qty.'  WHERE  (pa.ean13  = \''.$ean13.'\')    	   	   
	    AND pa.id_product = p.id_product');
	  	
	  if (Db::getInstance()->Affected_Rows() === 0) {	 		  		  	
	  		$sql_query = 'UPDATE '._DB_PREFIX_.'product p
				SET p.quantity = '.$qty.','
				 .($qty>0?'p.active = 1':'p.active = 0').	  	    
			' WHERE p.ean13  = \''.$ean13.'\'';
	  	
		  Db::getInstance()->Execute($sql_query);
		  //var_dump($sql_query);		  
	  }else{
		$sql_query = 'UPDATE '._DB_PREFIX_.'product p INNER JOIN
	       (SELECT SUM(quantity) as q_s, id_product
	       FROM '._DB_PREFIX_.'product_attribute GROUP BY id_product) as pa
	       ON (p.id_product = pa.id_product)                                
	         SET
	           p.quantity = q_s                        
	         WHERE 
	         pa.ean13  = \''.$ean13.'\'';
       	Db::getInstance()->Execute($sql_query); 
	  }
	  return true;
	}
}
?>