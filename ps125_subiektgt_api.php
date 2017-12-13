<?php
class Ps125_SubiektGT_Api extends Module {
	
	private $subiektgt_api;
	private $subiektgt_api_key = '';
	private $store_id = 1;
	private $auto_create_products = 1;
	private $order_prefix = '';
	private $error_state = 0;
	private $docsell_state = 0;
	private $code_ship_cost = '';



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
		$this->code_ship_cost = Configuration::get('SUBIEKTGT_API_CODE_SHIPCOST');					
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
				 `customer_sell_doc_sent` smallint(6) NOT NULL DEFAULT '0',
				 `gt_order_ref` varchar(20) NOT NULL,
				 `gt_sell_doc_ref` varchar(20) NOT NULL,
				 `add_date` datetime NOT NULL,
				 `upd_date` datetime NOT NULL,
				 `is_locked` smallint(6) NOT NULL DEFAULT '0',
				 PRIMARY KEY (`id_gt_order`),
				 UNIQUE KEY `IDX` (`id_order`,`gt_order_ref`),
				 UNIQUE KEY `IDX3` (`id_order`,`is_locked`,`gt_order_sent`,`gt_sell_doc_sent`,`customer_sell_doc_sent`),
				 KEY `IDX2` (`id_gt_order`,`gt_sell_doc_ref`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";
		Db::getInstance()->Execute($dml2);

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

			Configuration::updateValue('SUBIEKTGT_API_DOCSELL_STATE', Tools::getValue('SUBIEKTGT_API_DOCSELL_STATE'));
			$this->docsell_state = Tools::getValue('SUBIEKTGT_API_DOCSELL_STATE');

			Configuration::updateValue('SUBIEKTGT_API_CODE_SHIPCOST', Tools::getValue('SUBIEKTGT_API_CODE_SHIPCOST'));
			$this->code_ship_cost = Tools::getValue('SUBIEKTGT_API_CODE_SHIPCOST');

			

		}
	}
	
	private function _postValidation(){
		$subiektgt_api = Tools::getValue('SUBIEKTGT_API');
		$subiektgt_api_key = Tools::getValue('SUBIEKTGT_API_KEY');
		$store_id = Tools::getValue('SUBIEKTGT_API_STORE_ID');
		$auto_create_products = Tools::getValue('SUBIEKTGT_API_AUTO_CREATE_PRO');
		$code_ship_cost = Tools::getValue('SUBIEKTGT_API_CODE_SHIPCOST');

				
		
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
				<label style="width:350px;">Zmień status gdy wegenerujesz PA lub FS:</label>				
				<div style="margin-left:400px;margin-bottom:30px;margin-top:5px;">
					<select name="SUBIEKTGT_API_DOCSELL_STATE">
					<option value="0" selected>-- nie zmieniaj --</option>
					';
					foreach($order_states as $os){
						$this->_html.= "<option value=\"{$os['id_order_state']}\" ".($this->docsell_state == $os['id_order_state']?"selected":"").">{$os['name']}</option>";
					}
	$this->_html .='</select>
				</div>				
				<label style="width:350px;">Autmatycznie tworzyć nowe produkty</label>
				<div style="margin-left:400px;margin-bottom:5px;margin-top:5px;"><input name="SUBIEKTGT_API_AUTO_CREATE_PRO" type="radio" value="1" '.(1==intval($this->auto_create_products)?'checked':'').'> Tak
					<input name="SUBIEKTGT_API_AUTO_CREATE_PRO" type="radio" value="0" '.(0==intval($this->auto_create_products)?'checked':'').'> Nie
				</div>

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
		$dml = 'INSERT INTO '._DB_PREFIX_.'subiektgt_api VALUES(0,'.$params['order']->id.',0,0,0,\'\',\'\',NOW(),NOW(),0)';
		DB::getInstance()->Execute($dml);	
	}

	public function getErrorOrderState(){
		return $this->error_state;
	}

	public function getDocSellState(){
		return $this->docsell_state;
	}

	public function getOrdersToSend(){
		$SQL = 'SELECT id_order FROM '._DB_PREFIX_.'subiektgt_api WHERE gt_order_sent = 0 AND is_locked = 0 LIMIT 50;';
		$orders = array();
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			//$order = new Or			
			$order_obj = new Order($order['id_order']);
			$products = $order_obj->getProductsDetail();
			$address = new Address($order_obj->id_address_delivery);
			$customer = new Customer($order_obj->id_customer);
			$carrier = new Carrier($order_obj->id_carrier);
			$messages = Message::getMessagesByOrderId($order_obj->id,true);
			$count_msg = count($messages);
			$orders[$order['id_order']] = array(
						'comments' => "Płatność: ".$order_obj->payment.", Wysyłka: ".$carrier->name.", Komentarze do zam.".$count_msg,
						'reference' => $this->order_prefix.' '.$order_obj->id,
						'create_product_if_not_exists' => $this->auto_create_products,
						'amount' => $order_obj->total_paid_real,
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
						),

			);
			$orders[$order['id_order']]['products'] = array();
			foreach($products as $p){
				$price = round($p['product_price']*(1+0.01*$p['tax_rate']),2);				
				$a_p = array(
						'ean'=>$p['product_ean13'],
						'code'=>$p['product_ean13'],
						'qty'=> $p['product_quantity'],
						'price' => $price,
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
				-- AND upd_date<ADDDATE(NOW(), INTERVAL -10 MINUTE)
				LIMIT 50;';
		$orders = array();		
		$order_to_send = DB::getInstance()->ExecuteS($SQL);
		foreach($order_to_send as $order){
			$orders[$order['id_order']]['order_ref'] = $order['gt_order_ref'];
		}			
		return $orders;
	}

	static protected function emailFix($email){
		return eregi_replace("[0-9]+-_-","",$email);
	}

	static public function logEvent($id_order,$type,$result,$result_desc){
		$DML = "INSERT INTO "._DB_PREFIX_."subiektgt_api_log VALUES({$id_order},'{$type}',NOW(),'{$result}','".pSQL($result_desc)."')";
		return DB::getInstance()->Execute($DML);
	}

	static public function unlockOrder($id_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET is_locked = 0, upd_date = NOW() WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	static public function setSentOrderToSubiekt($id_order,$ref_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_order_sent = 1, upd_date = NOW(),gt_order_ref = \''.$ref_order.'\' WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	static public function setSentSellDocToSubiekt($id_order,$ref_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET gt_sell_doc_sent = 1, upd_date = NOW(),gt_sell_doc_ref = \''.$ref_order.'\' WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	static public function lockOrder($id_order){
		$DML = 'UPDATE '._DB_PREFIX_.'subiektgt_api SET is_locked = 1, upd_date = NOW() WHERE id_order = '.$id_order;
		return DB::getInstance()->Execute($DML);
	}


	public function hookpostUpdateOrderStatus($params){
		//print_r($params)	;		
		switch ($params['newOrderStatus']->id) {
			case _PS_OS_PAYMENT_:
					$this->unlockOrder($params['id_order']);
				break;
			case _PS_OS_PREPARATION_:
					$this->unlockOrder($params['id_order']);
				break;
			case _PS_OS_SHIPPING_:
					$this->unlockOrder($params['id_order']);
				break;				
			default: 
					$this->lockOrder($params['id_order']);
				break;
			
		}
		return;

	}

	public function hookAdminOrder($params){
		global $smarty;
		
		$order = new Order($params['id_order']);
		$SQL = 'SELECT * FROM  '._DB_PREFIX_.'subiektgt_api WHERE id_order = '.$params['id_order'];
		$gt_state = DB::getInstance()->getRow($SQL);
		$SQL = 'SELECT * FROM  '._DB_PREFIX_.'subiektgt_api_log WHERE id_order = '.$params['id_order'].' ORDER BY log_date DESC';
		$logs = DB::getInstance()->ExecuteS($SQL);
		

		
		 $smarty->assign(array(
		 	'gtState' => $gt_state,
		 	'logs' => $logs
		// 		'arrayProducts' => $arrayProducts
		 ));
		return $this->display(__FILE__, 'AdminOrder.tpl');	
	}
			
}
?>