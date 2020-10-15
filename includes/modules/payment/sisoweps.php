<?php
/*********************
 * Copyright 2012 Sisow
 * osCommerce Sisow EPS module
 */

require_once 'sisow/base.php';

class sisoweps extends SisowBase
{
	function __construct()
	{
		$this->code = 'sisoweps';
		$this->code2 = strtoupper($this->code);
		$this->title = MODULE_PAYMENT_SISOWEPS_TEXT_TITLE;
		$this->public_title = MODULE_PAYMENT_SISOWEPS_TEXT_PUBLIC_TITLE;
		$this->description = MODULE_PAYMENT_SISOWEPS_TEXT_DESCRIPTION;
		$this->sort_order = defined('MODULE_PAYMENT_SISOWEPS_SORT_ORDER') ? MODULE_PAYMENT_SISOWEPS_SORT_ORDER : null;
		$this->enabled = (defined('MODULE_PAYMENT_SISOWEPS_STATUS') && MODULE_PAYMENT_SISOWEPS_STATUS == 'True');
		
		if (null === $this->sort_order) return false;

		if (IS_ADMIN_FLAG === true && (empty(MODULE_PAYMENT_SISOWEPS_MERCHANTID) || empty(MODULE_PAYMENT_SISOWEPS_MERCHANTKEY))) $this->title .= '<span class="alert"> (not configured)</span>';

		if ((int)MODULE_PAYMENT_SISOWEPS_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_SISOWEPS_ORDER_STATUS_ID;
		}
		$this->configuration_group_id = 2000;
		
		$this->merchantid = MODULE_PAYMENT_SISOWEPS_MERCHANTID;
		$this->merchantkey = MODULE_PAYMENT_SISOWEPS_MERCHANTKEY;
		$this->shopid = MODULE_PAYMENT_SISOWEPS_SHOPID;
		$this->payment = 'eps';
		$this->testmode = MODULE_PAYMENT_SISOWEPS_TEST == 'True';
		$this->prefix = MODULE_PAYMENT_SISOWEPS_DESCRIPTION_PREFIX;
		
		$this->_pending = MODULE_PAYMENT_SISOWEPS_ORDER_STATUS_ID;
		if($this->_pending == 0)
			$this->_pending = DEFAULT_ORDERS_STATUS_ID;
	}
	
	function update_status()
	{
    	global $order, $db;
		
		if (!is_object($order)) $this->enabled = false;

		if (empty(MODULE_PAYMENT_SISOWEPS_MERCHANTID) || empty(MODULE_PAYMENT_SISOWEPS_MERCHANTKEY)) $this->enabled = false;
		
		if($this->enabled && ((MODULE_PAYMENT_SISOWEPS_MINAMOUNT != '' && MODULE_PAYMENT_SISOWEPS_MINAMOUNT > 0) || (MODULE_PAYMENT_SISOWEPS_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWEPS_MAXAMOUNT > 0)))
		{	
			if(MODULE_PAYMENT_SISOWEPS_MINAMOUNT != '' && MODULE_PAYMENT_SISOWEPS_MINAMOUNT > 0)
			{
				if(MODULE_PAYMENT_SISOWEPS_MINAMOUNT > $order->info['total'])
					$this->enabled = false;
			}
			
			if(MODULE_PAYMENT_SISOWEPS_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWEPS_MAXAMOUNT > 0)
			{			
				if(MODULE_PAYMENT_SISOWEPS_MAXAMOUNT < $order->info['total'])
					$this->enabled = false;
			}
		}
		
		if(MODULE_PAYMENT_SISOWEPS_GEOZONE > 0)
		{					
			$check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SISOWEPS_GEOZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
			
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
		      '  var eps_bic = document.checkout_payment.eps_bic.value;' . "\n" .
		      '  if (eps_bic == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWEPS_ERROR . '";' . "\n" .
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
			$field = '<link type="text/css" rel="stylesheet" href="https://bankauswahl.giropay.de/widget/v1/style.css" media="all" />
				<script src="https://bankauswahl.giropay.de/eps/widget/v1/epswidget.min.js"></script>
				<script>
					$(document).ready(function() {
						$(\'#eps_bic\').eps_widget({\'return\': \'bic\'});
					});
				</script>';
			
			return array('id' => $this->code,
				'module' => 'EPS',
				'fields' => array(array('title' => MODULE_PAYMENT_SISOWEPS_ENTER_BIC_LABEL, 'field' => $field . '<input type="text" name="eps_bic" id="eps_bic"/>')));
		}
	}

	function pre_confirmation_check()
	{
		$_SESSION['bic'] = $_POST['eps_bic'];
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
