<?php
/*********************
 * Copyright 2012 Sisow
 * osCommerce Sisow Mastercard module
 */

require_once 'sisow/base.php';

class sisowmastercard extends SisowBase
{
	function __construct()
	{
		$this->code = 'sisowmastercard';
		$this->code2 = strtoupper($this->code);
		$this->title = MODULE_PAYMENT_SISOWMASTERCARD_TEXT_TITLE;
		$this->public_title = MODULE_PAYMENT_SISOWMASTERCARD_TEXT_PUBLIC_TITLE;
		$this->description = MODULE_PAYMENT_SISOWMASTERCARD_TEXT_DESCRIPTION;
		$this->sort_order = defined('MODULE_PAYMENT_SISOWMASTERCARD_SORT_ORDER') ? MODULE_PAYMENT_SISOWMASTERCARD_SORT_ORDER : null;
		$this->enabled = (defined('MODULE_PAYMENT_SISOWMASTERCARD_STATUS') && MODULE_PAYMENT_SISOWMASTERCARD_STATUS == 'True');

		if (null === $this->sort_order) return false;

		if (IS_ADMIN_FLAG === true && (empty(MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTID) || empty(MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTKEY))) $this->title .= '<span class="alert"> (not configured)</span>';

		if ((int)MODULE_PAYMENT_SISOWMASTERCARD_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_SISOWMASTERCARD_ORDER_STATUS_ID;
		}
		$this->configuration_group_id = 2000;
		
		$this->merchantid = MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTID;
		$this->merchantkey = MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTKEY;
		$this->shopid = MODULE_PAYMENT_SISOWMASTERCARD_SHOPID;
		$this->payment = 'mastercard';
		$this->testmode = MODULE_PAYMENT_SISOWMASTERCARD_TEST == 'True';
		$this->prefix = MODULE_PAYMENT_SISOWMASTERCARD_DESCRIPTION_PREFIX;
		
		$this->_pending = MODULE_PAYMENT_SISOWMASTERCARD_ORDER_STATUS_ID;
		if($this->_pending == 0)
			$this->_pending = DEFAULT_ORDERS_STATUS_ID;
	}
	
	function update_status()
	{
    	global $order, $db;	
		
		if (!is_object($order)) $this->enabled = false;

		if (empty(MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTID) || empty(MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTKEY)) $this->enabled = false;
		
		if($this->enabled && ((MODULE_PAYMENT_SISOWMASTERCARD_MINAMOUNT != '' && MODULE_PAYMENT_SISOWMASTERCARD_MINAMOUNT > 0) || (MODULE_PAYMENT_SISOWMASTERCARD_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWMASTERCARD_MAXAMOUNT > 0)))
		{	
			if(MODULE_PAYMENT_SISOWMASTERCARD_MINAMOUNT != '' && MODULE_PAYMENT_SISOWMASTERCARD_MINAMOUNT > 0)
			{
				if(MODULE_PAYMENT_SISOWMASTERCARD_MINAMOUNT > $order->info['total'])
					$this->enabled = false;
			}
			
			if(MODULE_PAYMENT_SISOWMASTERCARD_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWMASTERCARD_MAXAMOUNT > 0)
			{			
				if(MODULE_PAYMENT_SISOWMASTERCARD_MAXAMOUNT < $order->info['total'])
					$this->enabled = false;
			}
		}
		
		if(MODULE_PAYMENT_SISOWMASTERCARD_GEOZONE > 0)
		{					
			$check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SISOWMASTERCARD_GEOZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
			
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
	}

	function selection()
	{
		global $order;
		
		$this->update_status();
		
		return array('id' => $this->code,
			'module' => $this->public_title);
	}

	function pre_confirmation_check()
	{
	}

	function confirmation()
	{ 
	}
	
	function process_button()
	{
	}
	
	function before_process()
	{
		$this->_init();
		$this->betaling();
	}
}
?>
