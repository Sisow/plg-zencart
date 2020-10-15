<?php

class SisowBase
{
    //base file Sisow Payment
    private $products_ordered; //nodig voor de mailing
    
    public $merchantid;
    public $merchantkey;
	public $shopid;
    public $customer;
    public $billing_firsname;
    public $billing_lastname;
    public $billing_mail;
    public $billing_company;
    public $billing_address1;
    public $billing_address2;
    public $billing_zip;
    public $billing_city;
    public $billing_country;
    public $billing_phone;
    public $shipping_firstname;
    public $shipping_lastname;
    public $shipping_mail;
    public $shipping_company;
    public $shipping_address1;
    public $shipping_address2;
    public $shipping_zip;
    public $shipping_city;
    public $shipping_country;
    public $shipping_countrycode;
    public $shipping_phone;
    public $shipping;
    public $handling;
    public $birthdate;
    public $makeinvoice;
    public $producten; //array met bestelde producten
    public $weight;
    public $tax;
    public $currency;
    public $reference;
    public $billing_countrycode;
    public $testmode;
    
    public $arg;
    public $payment;
    public $amount;
    public $issuerid;
	public $bic;
	public $coc;
	public $iban;
	public $phone;
	public $gender;
	public $birthday;
    public $description;
    public $notifyUrl;
    public $callbackUrl;
    public $returnUrl;
    public $cancelUrl;
    public $purchaseId;
    public $trxid;
    public $order_id;

    //function check cart
    //uitvoeren in de pre_confrimation_check()
    function check_cart()
    {
        global $cart;

        if (empty($_SESSION['cart']->cartID)) {
            $cartID = $_SESSION['cart']->cartID = $cart->generate_cart_id();
        }
    }

    //functie insert order()
    //uitvoeren in de pre_confirmation_check()
    function insert_order()
    {
        global $db;
        global $languages_id, $order, $order_totals;
		
		$this->order_id = $insert_id = $order->create($order_totals, 2);
		$order->create_add_products($insert_id);
    }

    function delete_order($order_id) {
        global $db;

        $db->Execute('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int)$order_id . '"');
        $db->Execute('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int)$order_id . '"');
        $db->Execute('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int)$order_id . '"');
        $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int)$order_id . '"');
        $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int)$order_id . '"');
        $db->Execute('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int)$order_id . '"');
    }

    //functie betaling()
    //uitvoeren in pre_confrimation_check()
    function betaling()
    {
		require_once 'sisow.cls5.php';
        global $db, $order, $messageStack;
		
		$sisow = new sisow($this->merchantid, $this->merchantkey, $this->shopid);
        if($this->testmode == 'true')
        {
            $this->arg['testmode'] = 'true';
            $sisow->issuerId = '99';
        }
        else
        {
            $sisow->issuerId = $this->issuerid;
        }

        $sisow->payment = $this->payment;
        $sisow->amount = round($order->info['total'], 2);
        $sisow->issuerId = $this->issuerid;
        $sisow->description = $this->description;

        $sisow->cancelUrl = zen_href_link(FILENAME_SHOPPING_CART, '', 'SSL');
        $sisow->notifyUrl = $this->notifyUrl;
        $sisow->callbackUrl = $this->notifyUrl;
        $sisow->returnUrl = $this->returnUrl;

        $sisow->purchaseId = $this->order_id;
		
		if($sisow->payment == 'overboeking')
		{
			$this->arg['days'] = MODULE_PAYMENT_SISOWOB_DAYS;
			$this->arg['including'] = MODULE_PAYMENT_SISOWOB_INCLUDING;
		}
		if($sisow->payment == 'ebill')
		{
			$this->arg['days'] = MODULE_PAYMENT_SISOWEB_DAYS;
			$this->arg['including'] = MODULE_PAYMENT_SISOWEB_INCLUDING;
		}
		
		if(!empty($this->bic))
			$this->arg['bic'] = $this->bic;
		
		if(!empty($this->coc))
			$this->arg['billing_coc'] = $this->coc;
		
		if(!empty($this->iban))
			$this->arg['iban'] = $this->iban;
		
		if(!empty($this->phone))
			$this->arg['billing_phone'] = $this->phone;
		
		if(!empty($this->gender))
			$this->arg['gender'] = $this->gender;
		
		if(!empty($this->birthday))
			$this->arg['birthdate'] = $this->birthday;		


        if (($ex = $sisow->TransactionRequest($this->arg)) < 0) {  			
			if($this->restock == true)
				$this->restock($this->order_id);

            $this->delete_order($this->order_id);

            $error = ($sisow->errorCode == 'TA8000') ? 'Controleer uw BTW instellingen' : 'Fout tijdens betalen('.$ex.', '.$sisow->errorCode.')';
            $messageStack->add_session('checkout_payment',$error, 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '' , 'SSL'));
			exit;
        }
        else 
        {          
            if($sisow->payment != 'afterpay' && $sisow->payment != 'focum' && $sisow->payment != 'overboeking')
            {
				
                $db->Execute('INSERT INTO sisow(trxid, orderid, betaalmogelijkheid) Values
                        ("'.$sisow->trxId.'",
                        "'.$this->order_id.'",
                        "'.$sisow->payment.'")'); 

                zen_redirect($sisow->issuerUrl);
				exit;
            }
			
			if($sisow->payment == 'afterpay' || $sisow->payment == 'focum')
			{
			    switch ($sisow->payment) {
                    case 'focum':
                        $statusid = MODULE_PAYMENT_SISOWFOCUM_ORDER_SUCCESS_STATUS_ID;
                        $_name = 'Focum AchterafBetalen';
                        break;
                    case 'afterpay':
                        if ($this->code == 'sisowafterpay' && defined(MODULE_PAYMENT_SISOWAFTERPAY_INVOICE) && MODULE_PAYMENT_SISOWAFTERPAY_INVOICE == 'True') {
                            $statusid = MODULE_PAYMENT_SISOWAFTERPAY_ORDER_SUCCESS_STATUS_ID;
                        } else {
                            $statusid = MODULE_PAYMENT_SISOWAFTERPAY_ORDER_RESERVATION_STATUS_ID;
                        }
                        $_name = 'Afterpay';
                        break;
                }

				$commentaar = 'Transactie betaald met Sisow '.$_name.'.';	
					
				if(!isset($statusid) || $statusid == 0)
					$statusid = DEFAULT_ORDERS_STATUS_ID;

				$ex = $db->Execute("update " . TABLE_ORDERS . " set orders_status = '" . $statusid . "', last_modified = now() where orders_id = '" . $this->order_id . "'");
				
				$sql_data_array = array('orders_id' => $this->order_id,
						'orders_status_id' => $statusid,
						'date_added' => 'now()',
						'customer_notified' => '1',
						'comments' => $commentaar);
				zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
			}
		   
            $this->billto = $_SESSION['billto'];
			$this->sendto = $_SESSION['sendto'];
			$this->customer_id = $_SESSION['customer_id'];
			$this->langcode = $_SESSION['language'];
			
			$this->send_mail();
			$this->emptyCart($this->customer_id);
			zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
			exit;
        }
    }
    
    //functie restock()
    //uitvoeren bij mislukte transactie
    function restock($orderid)
    {        
		global $db;
		$order = $db->Execute("select products_id, products_quantity
					from " . TABLE_ORDERS_PRODUCTS . "
					where orders_id = '" . (int)$orderid . "'");
					
				while (!$order->EOF) {
					$product = $db->Execute("SELECT products_quantity, products_ordered FROM ".TABLE_PRODUCTS." WHERE products_id = " . (int)$order->fields['products_id']);
					
					$products_quantity = $product->fields['products_quantity'] + $order->fields['products_quantity'];
					$products_ordered = $product->fields['products_ordered'] - $order->fields['products_quantity'];
					
					$db->Execute("update " . TABLE_PRODUCTS . "
						set products_quantity = '" . $products_quantity . "', products_ordered = '" . $products_ordered . "' where products_id = " . (int)$order->fields['products_id']);		
								
					$order->MoveNext();
				}
    }

    //functie send_mail()
    //uitvoeren in before process
    function send_mail()
    {
        global $order, $order_total_modules, $db, $currencies, $order_totals, $template_dir;
		
		if(!class_exists('order'))
			require_once(DIR_WS_CLASSES . 'order.php');
		if(!class_exists('payment'))
			require_once(DIR_WS_CLASSES . 'payment.php');

        if(isset($_GET['notify']) || isset($_GET['callback']))
        {
            if (strpos($_GET['ec'], 'zen') === 0 ) {
                $orderid = substr($_GET['ec'],3);
            } else {
                $orderid = $_GET['ec'];
            }
            $this->order_id = (int)$orderid;
		
			$this->billto = $_GET['billto'];
			$this->sendto = $_GET['sendto'];
			$this->customer_id = $_GET['cid'];
			$this->langcode = $_GET['language'];
        }
        $this->comment = (isset($_GET['comment'])) ? base64_decode($_GET['comment']) : '';

        if (!isset($_SESSION['language'])) $_SESSION['language'] = 'english';

        require(zen_get_file_directory(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . "/", 'checkout_process.php', 'false'));


		$order = new order($this->order_id);
		$payment = new payment($order->info['payment_module_code']);
		$GLOBALS[$_SESSION['payment']] = $payment->paymentClass;
		
		$order_totals = $order->totals;
		$_SESSION['customer_id'] = $this->customer_id;
		$_SESSION['sendto'] = $this->sendto;
		$_SESSION['billto'] = $this->billto;
		$_SESSION['payment'] = $order->info['payment_module_code'];

		//get customer firstname and lastname
		$name_query = "SELECT entry_firstname as firstname, entry_lastname as lastname
                    FROM   " . TABLE_ADDRESS_BOOK . "
                    WHERE  customers_id = :customersID";

        $name_query = $db->bindVars($name_query, ':customersID', $_SESSION['customer_id'], 'integer');
        $names = $db->Execute($name_query);
		
		$order->customer['firstname']	= $names->fields['firstname'];
		$order->customer['lastname']	= $names->fields['lastname'];
		
		//producten correct inladen
		$order->products_ordered = '';
		$order->products_ordered_html = '';

		if ((!isset($order->info['comments']) || empty($order->info['comments']) ) && !empty($this->comment) ) {
            $order->info['comments'] = $this->comment;
        }
		
		for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
			
			$order->products_ordered_attributes = '';
			
			if (isset($order->products[$i]['attributes'])) {
				for ($j=0, $n2=sizeof($order->products[$i]['attributes']); $j<$n2; $j++) {
					$order->products_ordered_attributes .= "\n\t" . zen_decode_specialchars($order->products[$i]['attributes'][$j]['option']) . ' ' . zen_decode_specialchars($order->products[$i]['attributes'][$j]['value']);
				}
			}
			
			$order->products_ordered .=  
				$order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ($order->products[$i]['model'] != '' ? ' (' . $order->products[$i]['model'] . ') ' 	: '') . ' = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . ($order->products[$i][			'onetime_charges'] !=0 ? "\n" . TEXT_ONETIME_CHARGES_EMAIL . $currencies->display_price($order->products[$i]['onetime_charges'], $order->products[$i]['tax'], 1) : '') . $order->	products_ordered_attributes . "\n";

			$order->products_ordered_html .=
				'<tr>' . "\n" .
				'<td class="product-details" align="right" valign="top" width="30">' . $order->products[$i]['qty'] . '&nbsp;x</td>' . "\n" .
				'<td class="product-details" valign="top">' . nl2br($order->products[$i]['name']) . ($order->products[$i]['model'] != '' ? ' (' . nl2br($order->products[$i]['model']) . ') ' : '') . "\n" .
				'<nobr>' .
				'<small><em> '. nl2br($order->products_ordered_attributes) .'</em></small>' .
				'</nobr>' .
				'</td>' . "\n" .
				'<td class="product-details-num" valign="top" align="right">' .
					$currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) .
				($order->products[$i]['onetime_charges'] !=0 ?
				'</td></tr>' . "\n" . '<tr><td class="product-details">' . nl2br(TEXT_ONETIME_CHARGES_EMAIL) . '</td>' . "\n" .
				'<td>' . $currencies->display_price($order->products[$i]['onetime_charges'], $order->products[$i]['tax'], 1) : '') .
				'</td></tr>' . "\n";
		}
		
		$order->send_order_email($this->order_id);
    }

    function install()
    {
			switch ($this->code2)
			{
				case 'SISOWIDEAL':
					$naam = 'iDEAL';
					break;
				case 'SISOWDE':
					$naam = 'Sofort Banking';
					break;			
				case 'SISOWMC':
					$naam = 'Bancontact';
					break;
				case 'SISOWEB':
					$naam = 'ebill';
					break;
				case 'SISOWECARE':
					$naam = 'ecare';
					break;
				case 'SISOWOB':
					$naam = 'OverBoeking';
					break;
				case 'SISOWPD':
					$naam = 'Podium Cadeaukaart';
					break;			
				case 'SISOWWG':
					$naam = 'Webshop Giftcard';
					break;
				case 'SISOWFC':
					$naam = 'Fijn Cadeaukaart';
					break;
				case 'SISOWPP':
					$naam = 'PayPal';
					break;
				default:
				    $naam = $this->title;
				    if (strpos($naam, 'Sisow ') == 0) {
				        $naam = substr($naam, 6);
                    }
				    break;
			}
			
            global $db;

			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Sisow ".$naam."', 'MODULE_PAYMENT_".$this->code2."_STATUS', 'True', 'Do you want to accept payments with Sisow ".$naam."?', '".$this->configuration_group_id."', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_".$this->code2."_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '".$this->configuration_group_id."', '2' , now())");
           
		   $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set default status', 'MODULE_PAYMENT_".$this->code2."_ORDER_STATUS_ID', '0', 'Default order status', '".$this->configuration_group_id."', '3', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
            
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set success status', 'MODULE_PAYMENT_".$this->code2."_ORDER_SUCCESS_STATUS_ID', '0', 'Set success order status', '".$this->configuration_group_id."', '4', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
           
		   $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set failed status', 'MODULE_PAYMENT_".$this->code2."_ORDER_FAILED_STATUS_ID', '0', 'Set failed order status', '".$this->configuration_group_id."', '5', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

		   if ($this->code2 == 'SISOWKLARNA' || $this->code2 == 'SISOWAFTERPAY') {
               $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Set reservation status', 'MODULE_PAYMENT_".$this->code2."_ORDER_RESERVATION_STATUS_ID', '0', 'Set reservation order status', '".$this->configuration_group_id."', '6', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
           }
           
		   $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Restock', 'MODULE_PAYMENT_".$this->code2."_RESTOCK', 'True', 'Do you want Sisow ".$naam." to restock?', '".$this->configuration_group_id."', '7', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Description prefix', 'MODULE_PAYMENT_".$this->code2."_DESCRIPTION_PREFIX', '', 'Description prefix', '".$this->configuration_group_id."', '8')");
            
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Sisow merchant ID', 'MODULE_PAYMENT_".$this->code2."_MERCHANTID', '', 'Set your Sisow merchant ID', '".$this->configuration_group_id."', '9')");
            
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Sisow merchant Key', 'MODULE_PAYMENT_".$this->code2."_MERCHANTKEY', '', 'Set your Sisow merchant Key', '".$this->configuration_group_id."', '10')");
			
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Sisow Shop ID', 'MODULE_PAYMENT_".$this->code2."_SHOPID', '', 'Set your Sisow Shop ID (optional)', '".$this->configuration_group_id."', '11')");
            
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Test mode', 'MODULE_PAYMENT_".$this->code2."_TEST', 'True', 'Do you want to use Sisow ".$naam." in test mode?', '".$this->configuration_group_id."', '12', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
					
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Min Order Amount', 'MODULE_PAYMENT_".$this->code2."_MINAMOUNT', '0', 'The min order amount', '".$this->configuration_group_id."', '13')");
			
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Max Order Amount', 'MODULE_PAYMENT_".$this->code2."_MAXAMOUNT', '0', 'The max order amount', '".$this->configuration_group_id."', '14')");
			
			$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_".$this->code2."_GEOZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '".$this->configuration_group_id."', '15', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

			if($this->code2 == 'SISOWOB')
			{
				$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Include Paymentlink', 'MODULE_PAYMENT_".$this->code2."_INCLUDING', 'True', 'Include Paymentlink in de mail', '".$this->configuration_group_id."', '16', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Dagen', 'MODULE_PAYMENT_".$this->code2."_DAYS', '', 'Dagen nadat herinnering verstuurd wordt', '".$this->configuration_group_id."', '17')");
			}
			elseif($this->code2 == 'SISOWEBILL')
			{
				$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Include Bank gegevens', 'MODULE_PAYMENT_".$this->code2."_INCLUDING', 'True', 'Include Bankgegevens voor een overboeking', '".$this->configuration_group_id."', '18', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order) values ('Dagen', 'MODULE_PAYMENT_".$this->code2."_DAYS', '', 'Dagen nadat herinnering verstuurd wordt', '".$this->configuration_group_id."', '19')");
			}

			if ($this->code2 == 'SISOWKLARNA' || $this->code2 == 'SISOWAFTERPAY') {
                $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Invoice', 'MODULE_PAYMENT_".$this->code2."_INVOICE', 'True', 'Make invoice?', '".$this->configuration_group_id."', '20', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            }

            if($this->code2 == 'SISOWECARE')
            {
                $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Invoice', 'MODULE_PAYMENT_".$this->code2."_INVOICE', 'True', 'Direct invoice?', '".$this->configuration_group_id."', '20', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
                $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Mail', 'MODULE_PAYMENT_".$this->code2."_MAIL', 'True', 'Factuur/Creditnota mailen?', '".$this->configuration_group_id."', '21', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
            }
			
			$db->Execute("CREATE TABLE IF NOT EXISTS sisow(
                    trxid varchar(20),
                    orderid int,
                    factuur varchar(20),
                    creditnota varchar(20),
                    teruggave varchar(20),
                    status varchar(20),
                    betaalmogelijkheid varchar(20),
                    PRIMARY KEY(trxid))");
        }
        
    function keys()
    {
        $arrayKeys = ['MODULE_PAYMENT_'.$this->code2.'_STATUS',
            'MODULE_PAYMENT_'.$this->code2.'_SORT_ORDER',
            'MODULE_PAYMENT_'.$this->code2.'_ORDER_STATUS_ID',
            'MODULE_PAYMENT_'.$this->code2.'_ORDER_SUCCESS_STATUS_ID',
            'MODULE_PAYMENT_'.$this->code2.'_ORDER_FAILED_STATUS_ID',
            'MODULE_PAYMENT_'.$this->code2.'_RESTOCK',
            'MODULE_PAYMENT_'.$this->code2.'_DESCRIPTION_PREFIX',
            'MODULE_PAYMENT_'.$this->code2.'_MERCHANTID',
            'MODULE_PAYMENT_'.$this->code2.'_MERCHANTKEY',
            'MODULE_PAYMENT_'.$this->code2.'_SHOPID',
            'MODULE_PAYMENT_'.$this->code2.'_TEST',
            'MODULE_PAYMENT_'.$this->code2.'_GEOZONE',
            'MODULE_PAYMENT_'.$this->code2.'_MINAMOUNT',
            'MODULE_PAYMENT_'.$this->code2.'_MAXAMOUNT'];


        if($this->code2 == 'SISOWECARE')
        {
            $arrayKeys[] = 'MODULE_PAYMENT_'.$this->code2.'_INVOICE';
            $arrayKeys[] = 'MODULE_PAYMENT_'.$this->code2.'_MAIL';
        }
		elseif($this->code2 == 'SISOWOB' || $this->code2 == 'SISOWEBILL')
		{
            $arrayKeys[] = 'MODULE_PAYMENT_'.$this->code2.'_INCLUDING';
            $arrayKeys[] = 'MODULE_PAYMENT_'.$this->code2.'_DAYS';
		}
        elseif($this->code2 == 'SISOWKLARNA' || $this->code2 == 'SISOWAFTERPAY') {
            $arrayKeys[] = 'MODULE_PAYMENT_' . $this->code2 . '_ORDER_RESERVATION_STATUS_ID';
            $arrayKeys[] = 'MODULE_PAYMENT_'.$this->code2.'_INVOICE';
        }

        return $arrayKeys;
    }
    
    function remove()
    {
        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key like '%" . $this->code2 . "%'");
    }
    
    function check()
    {
        global $db;
        
        if (!isset($check)) {
                    $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_".$this->code2."_STATUS'");
                    $check = $check_query->RecordCount();
            }
            return $check;
    }
    
	function _init()
    {
        global $order, $order_id, $languages_id, $request_type;
        $orderinfo = $order->info;
        $this->language_id = $languages_id;

        $this->check_cart();
        $this->insert_order();
        $this->_prepare();

        if ($this->testmode) {
            $this->arg['testmode'] = 'true';
        }

        $this->purchaseId = $order_id;
        if ($this->prefix) {
            $this->description = $this->prefix . ' ' . $this->order_id;
        } else {
            $this->description = 'Order ' . $this->order_id;
        }

        $this->comment = $comments = ($orderinfo['comments'] == '') ? '' : '&comment=' . base64_encode($orderinfo['comments']);

        switch ($request_type) {
            case ('SSL'):
                $server = HTTPS_SERVER;
                break;
            case ('NONSSL'):
            default:
                $server = HTTP_SERVER;
                break;
        }

        $this->returnUrl = $server . DIR_WS_CATALOG . 'ext/modules/payment/sisow/notify.php?pmt=' . $this->code .'&cid='.$_SESSION['customer_id'] . '&'.zen_session_name().'='.zen_session_id().$comments;
        $this->notifyUrl = $server . DIR_WS_CATALOG . 'ext/modules/payment/sisow/notify.php?pmt=' . $this->code .'&cid='.$_SESSION['customer_id'] . '&billto=' . $_SESSION['billto'] . '&sendto=' . $_SESSION['sendto'] . '&language='.$_SESSION['languages_code'] . $comments;
    }
	
    function emptyCart($customerid)
    {
        global $db;
        $db->Execute("DELETE FROM ".TABLE_CUSTOMERS_BASKET_ATTRIBUTES." WHERE customers_id = '".$customerid."'"); 
        $db->Execute("DELETE FROM ".TABLE_CUSTOMERS_BASKET." WHERE customers_id = '".$customerid."'");
        $_SESSION['cart']->reset(TRUE);
    }

	function _prepare()
	{
		global $order, $currencies;
		global $customer_id;
					
		$billing = $order->billing;
		$this->arg['billing_firstname'] = $billing['firstname'];
        $this->arg['billing_lastname'] = $billing['lastname'];
        $this->arg['billing_mail'] = (isset($billing['email_address'])) ? $billing['email_address'] : $order->customer['email_address'];
        $this->arg['billing_company'] = $billing['company'];
        $this->arg['billing_address1'] = $billing['street_address'];
        $this->arg['billing_address2'] = $billing['suburb'];
        $this->arg['billing_zip'] = $billing['postcode'];
        $this->arg['billing_city'] = $billing['city'];
        $this->arg['billing_country'] = $billing['country']['title'];
        $this->arg['billing_phone'] = $billing['telephone'];
        $this->arg['billing_countrycode'] = $billing['country']['iso_code_2'];
                
		$delivery = $order->delivery;
        $this->arg['shipping_firstname'] = $delivery['firstname'];
        $this->arg['shipping_lastname'] = $delivery['lastname'];
        $this->arg['shipping_mail'] = (isset($delivery['email_address'])) ? $delivery['email_address'] : $order->customer['email_address'];
        $this->arg['shipping_company'] = $delivery['company'];
        $this->arg['shipping_address1'] = $delivery['street_address'];
        $this->arg['shipping_address2'] = $delivery['suburb'];
        $this->arg['shipping_zip'] = $delivery['postcode'];
        $this->arg['shipping_city'] = $delivery['city'];
        $this->arg['shipping_country'] = $delivery['country']['title'];
        $this->arg['shipping_countrycode'] = $delivery['country']['iso_code_2'];
        
		$this->arg['ipaddress'] = $_SERVER['REMOTE_ADDR'];
		
		$orderinfo = $order->info;
		$this->arg['tax'] = round($orderinfo['tax'], 2) * 100.0;
        $this->arg['currency'] = $orderinfo['currency'];
		$this->arg['shipping'] = round($orderinfo['shipping_cost'], 2) * 100.0; //verzendkosten in centen
				
		$productnr = 0;
		foreach($order->products as $product)
		{			
			$productnr ++;
			$this->arg['product_id_'.$productnr] = $product['model'];
			$this->arg['product_description_'.$productnr] = $product['name'];
			$this->arg['product_quantity_'.$productnr] = $product['qty'];
			$this->arg['product_netprice_'.$productnr] = round($product['final_price']*100.0);
			$this->arg['product_total_'.$productnr] = round((($product['final_price'] * $product['qty']) * (($product['tax'] + 100.0) / 100.0) )*100.0);
			$this->arg['product_nettotal_'.$productnr] = round(($product['final_price'] * $product['qty']) *100);
			$this->arg['product_tax_'.$productnr] = round(((($product['final_price'] * $product['qty']) * (($product['tax'] + 100.0) / 100.0) - ($product['final_price'] * $product['qty']) )*100.0));
			$this->arg['product_taxrate_'.$productnr] = round($product['tax'] * 100.0);
		}
		
		if($order->info['shipping_method'] != '')
		{
			$module = substr($_SESSION['shipping']['id'], 0, strpos($_SESSION['shipping']['id'], '_'));
		  if (zen_not_null($order->info['shipping_method']) && DISPLAY_PRICE_WITH_TAX != 'true') {
			if ($GLOBALS[$module]->tax_class > 0) {
			  $shipping_tax_basis = (!isset($GLOBALS[$module]->tax_basis)) ? STORE_SHIPPING_TAX_BASIS : $GLOBALS[$module]->tax_basis;
			  $shippingOnBilling = zen_get_tax_rate($GLOBALS[$module]->tax_class, $order->billing['country']['id'], $order->billing['zone_id']);
			  $shippingOnDelivery = zen_get_tax_rate($GLOBALS[$module]->tax_class, $order->delivery['country']['id'], $order->delivery['zone_id']);
			  if ($shipping_tax_basis == 'Billing') {
				$shipping_tax = $shippingOnBilling;
			  } elseif ($shipping_tax_basis == 'Shipping') {
				$shipping_tax = $shippingOnDelivery;
			  } else {
				if (STORE_ZONE == $order->billing['zone_id']) {
				  $shipping_tax = $shippingOnBilling;
				} elseif (STORE_ZONE == $order->delivery['zone_id']) {
				  $shipping_tax = $shippingOnDelivery;
				} else {
				  $shipping_tax = 0;
				}
			  }
			  $taxAdjustmentForShipping = zen_round(zen_calculate_tax($order->info['shipping_cost'], $shipping_tax), $currencies->currencies[$_SESSION['currency']]['decimal_places']);
			  $optionsST['SHIPPINGAMT'] += $taxAdjustmentForShipping;
			  $optionsST['TAXAMT'] -= $taxAdjustmentForShipping;

			}
		  }
			
			
			$productnr ++;
			$this->arg['product_id_'.$productnr] = 'shipping';
			$this->arg['product_description_'.$productnr] = $order->info['shipping_method'];
			$this->arg['product_quantity_'.$productnr] = '1';
			$this->arg['product_netprice_'.$productnr] = round($order->info['shipping_cost']*100.0);
			$this->arg['product_nettotal_'.$productnr] = round($order->info['shipping_cost'] *100);
			$this->arg['product_tax_'.$productnr] = round(($this->arg['product_nettotal_'.$productnr] * ($shipping_tax/100)));
			$this->arg['product_taxrate_'.$productnr] = round($shipping_tax) * 100.0;
			
			$this->arg['product_total_'.$productnr] = round($this->arg['product_nettotal_'.$productnr] + $this->arg['product_tax_'.$productnr]);
		}

		if ( ($this->code == 'sisowklarna' && defined(MODULE_PAYMENT_SISOWKLARNA_INVOICE) && MODULE_PAYMENT_SISOWKLARNA_INVOICE == 'True') ||
             ($this->code == 'sisowafterpay' && defined(MODULE_PAYMENT_SISOWAFTERPAY_INVOICE) && MODULE_PAYMENT_SISOWAFTERPAY_INVOICE == 'True')
        ) {
            $this->arg['makeinvoice'] = 'true';
        }
		
        $this->arg['reference'] = $orderid;
		if($this->code == 'sisowecare')
		{
			$this->arg['gender'] = $_SESSION['sisow_gender'];
			$this->arg['initials'] = $_SESSION['sisow_initials'];
			$this->arg['birthdate'] = $_SESSION['sisow_dob'];
			if(MODULE_PAYMENT_SISOWECARE_INVOICE == 'True')
			{
				$this->arg['makeinvoice'] = 'true';
			}
			if(MODULE_PAYMENT_SISOWECARE_MAIL == 'True')
			{
				$this->arg['mailinvoice'] = 'true';
			}
		}
	}
}