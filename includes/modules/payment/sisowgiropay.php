<?php
/*********************
 * Copyright 2012 Sisow
 * osCommerce Sisow Giropay module
 */

require_once 'sisow/base.php';

class sisowgiropay extends SisowBase
{
	function __construct()
	{
		$this->code = 'sisowgiropay';
		$this->code2 = strtoupper($this->code);
		$this->title = MODULE_PAYMENT_SISOWGIROPAY_TEXT_TITLE;
		$this->public_title = MODULE_PAYMENT_SISOWGIROPAY_TEXT_PUBLIC_TITLE;
		$this->description = MODULE_PAYMENT_SISOWGIROPAY_TEXT_DESCRIPTION;
		$this->sort_order = defined('MODULE_PAYMENT_SISOWGIROPAY_SORT_ORDER') ? MODULE_PAYMENT_SISOWGIROPAY_SORT_ORDER : null;
		$this->enabled = (defined('MODULE_PAYMENT_SISOWGIROPAY_STATUS') && MODULE_PAYMENT_SISOWGIROPAY_STATUS == 'True');
		
		if (null === $this->sort_order) return false;

		if (IS_ADMIN_FLAG === true && (empty(MODULE_PAYMENT_SISOWGIROPAY_MERCHANTID) || empty(MODULE_PAYMENT_SISOWGIROPAY_MERCHANTKEY))) $this->title .= '<span class="alert"> (not configured)</span>';

		if ((int)MODULE_PAYMENT_SISOWGIROPAY_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_SISOWGIROPAY_ORDER_STATUS_ID;
		}
		$this->configuration_group_id = 2000;
		
		$this->merchantid = MODULE_PAYMENT_SISOWGIROPAY_MERCHANTID;
		$this->merchantkey = MODULE_PAYMENT_SISOWGIROPAY_MERCHANTKEY;
		$this->shopid = MODULE_PAYMENT_SISOWGIROPAY_SHOPID;
		$this->payment = 'giropay';
		$this->testmode = MODULE_PAYMENT_SISOWGIROPAY_TEST == 'True';
		$this->prefix = MODULE_PAYMENT_SISOWGIROPAY_DESCRIPTION_PREFIX;
		
		$this->_pending = MODULE_PAYMENT_SISOWGIROPAY_ORDER_STATUS_ID;
		if($this->_pending == 0)
			$this->_pending = DEFAULT_ORDERS_STATUS_ID;
	}
	
	function update_status()
	{
    	global $order, $db;	
		
		if (!is_object($order)) $this->enabled = false;

		if (empty(MODULE_PAYMENT_SISOWGIROPAY_MERCHANTID) || empty(MODULE_PAYMENT_SISOWGIROPAY_MERCHANTKEY)) $this->enabled = false;
		
		if($this->enabled && ((MODULE_PAYMENT_SISOWGIROPAY_MINAMOUNT != '' && MODULE_PAYMENT_SISOWGIROPAY_MINAMOUNT > 0) || (MODULE_PAYMENT_SISOWGIROPAY_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWGIROPAY_MAXAMOUNT > 0)))
		{	
			if(MODULE_PAYMENT_SISOWGIROPAY_MINAMOUNT != '' && MODULE_PAYMENT_SISOWGIROPAY_MINAMOUNT > 0)
			{
				if(MODULE_PAYMENT_SISOWGIROPAY_MINAMOUNT > $order->info['total'])
					$this->enabled = false;
			}
			
			if(MODULE_PAYMENT_SISOWGIROPAY_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWGIROPAY_MAXAMOUNT > 0)
			{			
				if(MODULE_PAYMENT_SISOWGIROPAY_MAXAMOUNT < $order->info['total'])
					$this->enabled = false;
			}
		}
		
		if(MODULE_PAYMENT_SISOWGIROPAY_GEOZONE > 0)
		{					
			$check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SISOWGIROPAY_GEOZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
			
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
		      '  var giropay_bic = document.checkout_payment.giropay_bic.value;' . "\n" .
		      '  if (giropay_bic == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWGIROPAY_ERROR . '";' . "\n" .
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
			$field = '<link type="text/css" rel="stylesheet" href="https://bankauswahl.giropay.de/eps/widget/v1/style.css" media="all" />
				<script src="https://bankauswahl.giropay.de/widget/v1/giropaywidget.min.js"></script>
				<script>
					$(document).ready(function() {
						$(\'#giropay_bic\').giropay_widget({\'return\': \'bic\',\'kind\': 0});
					});
				</script>';
			
			return array('id' => $this->code,
				'module' => 'Giropay',
				'fields' => array(array('title' => MODULE_PAYMENT_SISOWGIROPAY_ENTER_BIC_LABEL, 'field' => $field . '<input type="text" name="giropay_bic" id="giropay_bic"/>')));
		}
	}

	function pre_confirmation_check()
	{
		$_SESSION['bic'] = $_POST['giropay_bic'];
	}

	function confirmation()
	{ 
	}
	
	function process_button()
	{
	}
	
	function before_process()
	{
		$this->bic = $_SESSION['bic'];
		$this->_init();
		$this->betaling();
	}
}
?>
