<?php
/*********************
 * Copyright 2012 Sisow
 * osCommerce Sisow Focum Achteraf Betalen module
 */

require_once 'sisow/base.php';

class sisowfocum extends SisowBase
{
	function __construct()
	{
		$this->code = 'sisowfocum';
		$this->code2 = strtoupper($this->code);
		$this->title = MODULE_PAYMENT_SISOWFOCUM_TEXT_TITLE;
		$this->public_title = MODULE_PAYMENT_SISOWFOCUM_TEXT_PUBLIC_TITLE;
		$this->description = MODULE_PAYMENT_SISOWFOCUM_TEXT_DESCRIPTION;
		$this->sort_order = defined('MODULE_PAYMENT_SISOWFOCUM_SORT_ORDER') ? MODULE_PAYMENT_SISOWFOCUM_SORT_ORDER : null;
		$this->enabled = (defined('MODULE_PAYMENT_SISOWFOCUM_STATUS') && MODULE_PAYMENT_SISOWFOCUM_STATUS == 'True');
		
		if (null === $this->sort_order) return false;

		if (IS_ADMIN_FLAG === true && (empty(MODULE_PAYMENT_SISOWFOCUM_MERCHANTID) || empty(MODULE_PAYMENT_SISOWFOCUM_MERCHANTKEY))) $this->title .= '<span class="alert"> (not configured)</span>';

		if ((int)MODULE_PAYMENT_SISOWFOCUM_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_SISOWFOCUM_ORDER_STATUS_ID;
		}
		$this->configuration_group_id = 2000;
		
		$this->merchantid = MODULE_PAYMENT_SISOWFOCUM_MERCHANTID;
		$this->merchantkey = MODULE_PAYMENT_SISOWFOCUM_MERCHANTKEY;
		$this->shopid = MODULE_PAYMENT_SISOWFOCUM_SHOPID;
		$this->payment = 'focum';
		$this->testmode = MODULE_PAYMENT_SISOWFOCUM_TEST == 'True';
		$this->prefix = MODULE_PAYMENT_SISOWFOCUM_DESCRIPTION_PREFIX;
		
		$this->_pending = MODULE_PAYMENT_SISOWFOCUM_ORDER_STATUS_ID;
		if($this->_pending == 0)
			$this->_pending = DEFAULT_ORDERS_STATUS_ID;
	}
	
	function update_status()
	{
    	global $order, $db;	
		
		if (!is_object($order)) $this->enabled = false;

		if (empty(MODULE_PAYMENT_SISOWFOCUM_MERCHANTID) || empty(MODULE_PAYMENT_SISOWFOCUM_MERCHANTKEY)) $this->enabled = false;
		
		if($this->enabled && ((MODULE_PAYMENT_SISOWFOCUM_MINAMOUNT != '' && MODULE_PAYMENT_SISOWFOCUM_MINAMOUNT > 0) || (MODULE_PAYMENT_SISOWFOCUM_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWFOCUM_MAXAMOUNT > 0)))
		{	
			if(MODULE_PAYMENT_SISOWFOCUM_MINAMOUNT != '' && MODULE_PAYMENT_SISOWFOCUM_MINAMOUNT > 0)
			{
				if(MODULE_PAYMENT_SISOWFOCUM_MINAMOUNT > $order->info['total'])
					$this->enabled = false;
			}
			
			if(MODULE_PAYMENT_SISOWFOCUM_MAXAMOUNT != '' && MODULE_PAYMENT_SISOWFOCUM_MAXAMOUNT > 0)
			{			
				if(MODULE_PAYMENT_SISOWFOCUM_MAXAMOUNT < $order->info['total'])
					$this->enabled = false;
			}
		}
		
		if(MODULE_PAYMENT_SISOWFOCUM_GEOZONE > 0)
		{					
			$check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_SISOWFOCUM_GEOZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
			
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
				'  var focum_gender = document.checkout_payment.focum_gender.value;' . "\n" .
		      '  if (focum_gender == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWFOCUM_GENDER_ERROR . '\n";' . "\n" .
		      '    error = 1;' . "\n" .
		      '  }' . "\n" .
			  '  var focum_phone = document.checkout_payment.focum_phone.value;' . "\n" .
		      '  if (focum_phone == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWFOCUM_PHONE_ERROR . '\n";' . "\n" .
		      '    error = 1;' . "\n" .
		      '  }' . "\n" .
			  '  var focum_iban = document.checkout_payment.focum_iban.value;' . "\n" .
		      '  if (focum_iban == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWFOCUM_IBAN_ERROR . '\n";' . "\n" .
		      '    error = 1;' . "\n" .
		      '  }' . "\n" .
		      '  var focum_day = document.checkout_payment.focum_day.value;' . "\n" .
			  '  var focum_month = document.checkout_payment.focum_month.value;' . "\n" .
			  '  var focum_year = document.checkout_payment.focum_year.value;' . "\n" .
		      '  if (focum_day == "" || focum_month == "" || focum_year == "") {' . "\n" .
		      '    error_message = error_message + "' . MODULE_PAYMENT_SISOWFOCUM_BIRTHDAY_ERROR . '\n";' . "\n" .
		      '    error = 1;' . "\n" .
		      '  }' . "\n" .
		      '}' . "\n";
		return $js;
	}

	function selection()
	{
		global $order;
		
		$this->update_status();
		
		$gender = '<select name="focum_gender" id="focum_gender">';
		$gender .= '<option value="">' . MODULE_PAYMENT_SISOWFOCUM_ENTER_GENDER_LABEL . '</option>';
		$gender .= '<option value="m">' . MODULE_PAYMENT_SISOWFOCUM_ENTER_GENDER_MALE . '</option>';
		$gender .= '<option value="f">' . MODULE_PAYMENT_SISOWFOCUM_ENTER_GENDER_FEMALE . '</option>';
		$gender .= '</select>';
		
		$day = '<select name="focum_day" id="focum_day">';
		$day .= '<option value="">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_DAY . '</option>';
		for($i = 1; $i <32; $i++)
			$day .= '<option value="'.sprintf('%02d', $i).'">' . sprintf('%02d', $i) . '</option>';
		$day .= '</select>';
		
		$month = '<select name="focum_month" id="focum_month">';
		$month .= '<option value="">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH . '</option>';
		$month .= '<option value="01">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_JANUARY . '</option>';
		$month .= '<option value="02">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_FEBRUARY . '</option>';
		$month .= '<option value="03">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_MARCH . '</option>';
		$month .= '<option value="04">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_APRIL . '</option>';
		$month .= '<option value="05">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_MAY . '</option>';
		$month .= '<option value="06">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_JUNE . '</option>';
		$month .= '<option value="07">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_JULY . '</option>';
		$month .= '<option value="08">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_AUGUST . '</option>';
		$month .= '<option value="09">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_SEPTEMBER . '</option>';
		$month .= '<option value="10">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_OCTOBER . '</option>';
		$month .= '<option value="11">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_NOVEMBER . '</option>';
		$month .= '<option value="12">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_MONTH_DECEMBER . '</option>';
		$month .= '</select>';
		
		$year = '<select name="focum_year" id="focum_year">';
		$year .= '<option value="">' . MODULE_PAYMENT_SISOWFOCUM_SELECT_YEAR . '</option>';
		for($i = date("Y") - 17; $i > date("Y") - 130; $i--)
			$year .= '<option value="'.$i.'">' . $i . '</option>';
		$year .= '</select>';
		
		return array('id' => $this->code,
				'module' => 'FOCUM',
				'fields' => array(
								array('title' => MODULE_PAYMENT_SISOWFOCUM_ENTER_GENDER_LABEL, 'field' => $gender),
								array('title' => MODULE_PAYMENT_SISOWFOCUM_ENTER_PHONE_LABEL, 'field' => '<input type="text" name="focum_phone" id="focum_phone"/>'),
								array('title' => MODULE_PAYMENT_SISOWFOCUM_ENTER_IBAN_LABEL, 'field' => '<input type="text" name="focum_iban" id="focum_iban"/>'),
								array('title' => MODULE_PAYMENT_SISOWFOCUM_ENTER_BIRTHDAYDAG_LABEL, 'field' => $day),
								array('title' => MODULE_PAYMENT_SISOWFOCUM_ENTER_BIRTHDAYMONTH_LABEL, 'field' => $month),
								array('title' => MODULE_PAYMENT_SISOWFOCUM_ENTER_BIRTHDAYYEAR_LABEL, 'field' => $year),
								)
					);
	}

	function pre_confirmation_check()
	{
		$_SESSION['focum_gender'] = $_POST['focum_gender'];
		$_SESSION['focum_phone'] = $_POST['focum_phone'];
		$_SESSION['focum_iban'] = $_POST['focum_iban'];
		$_SESSION['focum_dob'] = $_POST['focum_day'] . $_POST['focum_month'] . $_POST['focum_year'];
	}

	function confirmation()
	{ 
	}
	
	function process_button()
	{
	}
	
	function before_process()
	{
		$this->gender = $_SESSION['focum_gender'];
		$this->phone = $_SESSION['focum_phone'];
		$this->iban = $_SESSION['focum_iban'];
		$this->birthday = $_SESSION['focum_dob'];
		$this->_init();
		$this->betaling();
	}
}
?>
