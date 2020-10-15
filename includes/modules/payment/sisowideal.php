<?php
/*********************
 * Copyright 2012 Sisow
 * osCommerce Sisow iDEAL module
 */

require_once 'sisow/base.php';

class sisowideal extends SisowBase
{
	function __construct()
	{
		$this->code = 'sisowideal';
		$this->code2 = strtoupper($this->code);
		$this->title = MODULE_PAYMENT_SISOWIDEAL_TEXT_TITLE;
		$this->public_title = MODULE_PAYMENT_SISOWIDEAL_TEXT_PUBLIC_TITLE;
		$this->description = MODULE_PAYMENT_SISOWIDEAL_TEXT_DESCRIPTION;
		$this->sort_order = defined('MODULE_PAYMENT_SISOWIDEAL_SORT_ORDER') ? MODULE_PAYMENT_SISOWIDEAL_SORT_ORDER : null;
		$this->enabled = (defined('MODULE_PAYMENT_SISOWIDEAL_STATUS') && MODULE_PAYMENT_SISOWIDEAL_STATUS == 'True');
		
		if (null === $this->sort_order) return false;

		if (IS_ADMIN_FLAG === true && (empty(MODULE_PAYMENT_SISOWIDEAL_MERCHANTID) || empty(MODULE_PAYMENT_SISOWIDEAL_MERCHANTKEY))) $this->title .= '<span class="alert"> (not configured)</span>';

		if ((int)MODULE_PAYMENT_SISOWIDEAL_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_SISOWIDEAL_ORDER_STATUS_ID;
		}
		$this->configuration_group_id = 2000;
		
		$this->merchantid = MODULE_PAYMENT_SISOWIDEAL_MERCHANTID;
		$this->merchantkey = MODULE_PAYMENT_SISOWIDEAL_MERCHANTKEY;
		$this->shopid = MODULE_PAYMENT_SISOWIDEAL_SHOPID;
		$this->payment = '';
		$this->testmode = MODULE_PAYMENT_SISOWIDEAL_TEST == 'True';
		$this->prefix = MODULE_PAYMENT_SISOWIDEAL_DESCRIPTION_PREFIX;
		
		$this->_pending = MODULE_PAYMENT_SISOWIDEAL_ORDER_STATUS_ID;
		if($this->_pending == 0)
			$this->_pending = DEFAULT_ORDERS_STATUS_ID;
	}
	
	function update_status()
	{
    	global $order, $db;	
		
		if (!is_object($order)) $this->enabled = false;

		if (empty(MODULE_PAYMENT_SISOWIDEAL_MERCHANTID) || empty(MODULE_PAYMENT_SISOWIDEAL_MERCHANTKEY)) $this->enabled = false;
		
		if($this->enabled && ((MODULE_PAYMENT_SISOWIDEAL_MINAMOUNT != '' && MODULE_PAYMENT_SISOWIDEAL_MINAMOUNT > 0) || (MODULE_PAYMENT_SISOWIDEAL_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWIDEAL_MAXAMOUNT > 0)))
		{	
			if(MODULE_PAYMENT_SISOWIDEAL_MINAMOUNT != '' && MODULE_PAYMENT_SISOWIDEAL_MINAMOUNT > 0)
			{
				if(MODULE_PAYMENT_SISOWIDEAL_MINAMOUNT > $order->info['total'])
					$this->enabled = false;
			}
			
			if(MODULE_PAYMENT_SISOWIDEAL_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWIDEAL_MAXAMOUNT > 0)
			{			
				if(MODULE_PAYMENT_SISOWIDEAL_MAXAMOUNT < $order->info['total'])
					$this->enabled = false;
			}
		}
		
		if(MODULE_PAYMENT_SISOWIDEAL_GEOZONE > 0)
		{					
			$check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SISOWIDEAL_GEOZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
			
			$check_flag_geo = false;
			
			while (!$check->EOF) {
				if ($check->fields['zone_id'] < 1) {
					$check_flag_geo = true;
					break;
				} 
				elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
					$check_flag_geo = true;
					break;
				}
				
				$check->MoveNext();
			}
			
			if ($check_flag_geo == false) 
			{
				$this->enabled = false;
			}
		}
	}
    
	function javascript_validation()
	{
		$js = 'if (payment_value == "' . $this->code . '") {' . "\n" .
		      '  var sisow_bank = document.checkout_payment.sisow_bank.value;' . "\n" .
		      '  if (sisow_bank == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWIDEAL_ERROR . '";' . "\n" .
		      '    error = 1;' . "\n" .
		      '  }' . "\n" .
		      '}' . "\n";
		return $js;
	}

	function selection()
	{
		global $order;

		$this->update_status();
		if ($this->enabled) {
			$select = '<select name="sisow_bank" ' . ($_SESSION['payment'] != 'sisowideal' ? 'enabled' : '') . '>\n';
			if (MODULE_PAYMENT_SISOWIDEAL_TEST == 'True') {
				$select .= '<option value="">Kies uw bank...</option>\n';
				$select .= '<option value="99">Sisow Bank (Test)</option>\n';
			}
			else {
				$select .= "<script type=\"text/javascript\" src=\"https://www.sisow.nl/Sisow/iDeal/issuers.js\"></script>\n";
			}
			$select .= "</select>";
			
			return array('id' => $this->code,
				'module' => 'iDEAL',
				'fields' => array(array('title' => '<img src="https://www.ideal.nl/img/statisch/iDEAL-klein.gif" ALIGN="middle" />'.MODULE_PAYMENT_SISOWIDEAL_SELECT_BANK_LABEL, 'field' => $select)));
		}
	}

	function pre_confirmation_check()
	{
		$_SESSION['issuerid'] = $_POST['sisow_bank'];
	}

	function confirmation()
	{
		/*$form_array = array ();
		$form_array = array_merge($form_array, array (array ('field' => $_SESSION['issuerid'], 'title' => 'Gekozen bank:&nbsp;')));
		$confirmation = array ('title' => '' , 'fields' => $form_array);
      	return $confirmation;*/
	}
	
	function process_button()
	{
		$process_button = zen_draw_hidden_field('sisow_bank', $_POST['sisow_bank']);
		return $process_button; 
	}
	
	function before_process()
	{
		
		$this->issuerid = $_SESSION['issuerid'];
		$this->_init();
		$this->betaling();
	}
}
?>
