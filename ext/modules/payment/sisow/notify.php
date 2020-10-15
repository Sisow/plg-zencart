<?php
/*********************
/* Copyright 2010 Sisow
/* osCommerce Sisow ... module
*/

error_reporting(E_ALL);
chdir('../../../../');
require_once('includes/application_top.php');
require_once 'includes/modules/payment/sisow/base.php';
require_once 'includes/modules/payment/sisow/sisow.cls5.php';

global $db;

ob_start();
	echo "POST:\n";
	print_r($_POST);
	echo "\n\n";
	echo "GET:\n";
	print_r($_GET);
	echo "\n\n";
	echo "SERVER:\n";
	print_r($_SERVER);
	echo "\n\n";
	echo date("Y-m-d H:i:s",time());
	$debug_info = ob_get_contents();
ob_clean();

if(isset($_GET['pmt']))
{
    $payment = $_GET['pmt'];
}

switch($payment)
{

    case "sisowafterpay":
        $_name = 'Afterpay';
        if (defined('MODULE_PAYMENT_SISOWAFTERPAY_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWAFTERPAY_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWAFTERPAY_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWAFTERPAY_ORDER_SUCCESS_STATUS_ID;
            $_reservation = MODULE_PAYMENT_SISOWAFTERPAY_ORDER_RESERVATION_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWAFTERPAY_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWAFTERPAY_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWAFTERPAY_RESTOCK;
        }
        break;
    case "sisowbunq":
        $_name = 'Bunq';
        if (defined('MODULE_PAYMENT_SISOWBUNQ_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWBUNQ_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWBUNQ_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWBUNQ_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWBUNQ_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWBUNQ_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWBUNQ_RESTOCK;
        }
        break;
    case "sisowde":
        $_name = 'SofortBanking';
        if (defined('MODULE_PAYMENT_SISOWDE_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWDE_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWDE_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWDE_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWDE_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWDE_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWDE_RESTOCK;
        }
        break;
    case "sisoweps":
        $_name = 'EPS';
        if (defined('MODULE_PAYMENT_SISOWEPS_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWEPS_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWEPS_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWEPS_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWEPS_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWEPS_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWEPS_RESTOCK;
        }
        break;
    case "sisowfocum":
        $_name = 'Focum AchterafBetalen';
        if (defined('MODULE_PAYMENT_SISOWFOCUM_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWFOCUM_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWFOCUM_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWFOCUM_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWFOCUM_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWFOCUM_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWFOCUM_RESTOCK;
        }
        break;
    case "sisowgiropay":
        $_name = 'Giropay';
        if (defined('MODULE_PAYMENT_SISOWGIROPAY_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWGIROPAY_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWGIROPAY_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWGIROPAY_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWGIROPAY_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWGIROPAY_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWGIROPAY_RESTOCK;
        }
        break;
    case "sisowhomepay":
        $_name = 'ING Home\'Pay';
        if (defined('MODULE_PAYMENT_SISOWHOMEPAY_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWHOMEPAY_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWHOMEPAY_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWHOMEPAY_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWHOMEPAY_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWHOMEPAY_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWHOMEPAY_RESTOCK;
        }
        break;
    case "sisowideal":
        $_name = 'iDEAL';
        if (defined('MODULE_PAYMENT_SISOWIDEAL_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWIDEAL_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWIDEAL_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWIDEAL_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWIDEAL_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWIDEAL_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWIDEAL_RESTOCK;
        }
        break;
    case "sisowidealqr":
        $_name = 'iDEAL QR';
        if (defined('MODULE_PAYMENT_SISOWIDEALQR_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWIDEALQR_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWIDEALQR_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWIDEALQR_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWIDEALQR_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWIDEALQR_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWIDEALQR_RESTOCK;
        }
        break;
    case "sisowklarna":
        $_name = 'Klarna Achteraf betalen';
        if (defined('MODULE_PAYMENT_SISOWKLARNA_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWKLARNA_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWKLARNA_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWKLARNA_ORDER_SUCCESS_STATUS_ID;
            $_reservation = MODULE_PAYMENT_SISOWKLARNA_ORDER_RESERVATION_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWKLARNA_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWKLARNA_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWKLARNA_RESTOCK;
        }
        break;
    case "sisowmaestro":
        $_name = 'Maestro';
        if (defined('MODULE_PAYMENT_SISOWMAESTRO_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWMAESTRO_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWMAESTRO_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWMAESTRO_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWMAESTRO_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWMAESTRO_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWMAESTRO_RESTOCK;
        }
        break;
    case "sisowmastercard":
        $_name = 'Mastercard';
        if (defined('MODULE_PAYMENT_SISOWMASTERCARD_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWMASTERCARD_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWMASTERCARD_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWMASTERCARD_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWMASTERCARD_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWMASTERCARD_RESTOCK;
        }
        break;
    case "sisowmc":
        $_name = 'Bancontact';
        if (defined('MODULE_PAYMENT_SISOWMC_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWMC_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWMC_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWMC_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWMC_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWMC_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWMC_RESTOCK;
        }
        break;
    case "sisowob":
        $_name = 'overboeking';
        if (defined('MODULE_PAYMENT_SISOWOB_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWOB_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWOB_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWOB_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWOB_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWOB_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWOB_RESTOCK;
        }
        break;
    case "sisowpp":
        $_name = 'PayPal';
        if (defined('MODULE_PAYMENT_SISOWPP_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWPP_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWPP_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWPP_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWPP_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWPP_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWPP_RESTOCK;
        }
        break;
    case "sisowvisa":
        $_name = 'Visa';
        if (defined('MODULE_PAYMENT_SISOWVISA_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWVISA_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWVISA_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWVISA_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWVISA_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWVISA_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWVISA_RESTOCK;
        }
        break;
    case "sisowvpay":
        $_name = 'V PAY';
        if (defined('MODULE_PAYMENT_SISOWVPAY_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWVPAY_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWVPAY_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWVPAY_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWVPAY_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWVPAY_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWVPAY_RESTOCK;
        }
        break;
    case "sisowvvv":
        $_name = 'VVV Giftcard';
        if (defined('MODULE_PAYMENT_SISOWVVV_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWVVV_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWVVV_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWVVV_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWVVV_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWVVV_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWVVV_RESTOCK;
        }
        break;
    case "sisowwebshop":
        $_name = 'Webshop Giftcard';
        if (defined('MODULE_PAYMENT_SISOWWEBSHOP_STATUS')) {
            $_pending = MODULE_PAYMENT_SISOWWEBSHOP_ORDER_STATUS_ID;
            $_failure = MODULE_PAYMENT_SISOWWEBSHOP_ORDER_FAILED_STATUS_ID;
            $_success = MODULE_PAYMENT_SISOWWEBSHOP_ORDER_SUCCESS_STATUS_ID;
            $_mid = MODULE_PAYMENT_SISOWWEBSHOP_MERCHANTID;
            $_mkey = MODULE_PAYMENT_SISOWWEBSHOP_MERCHANTKEY;
            $_restock = MODULE_PAYMENT_SISOWWEBSHOP_RESTOCK;
        }
        break;
    default:
        exit('No payment defined');
        break;
}

if(isset($_GET['notify']) || isset($_GET['callback']))
{
    if(isset($_GET['trxid']))
    {
        $trxid = $_GET['trxid'];
    }

    if(isset($_GET['ec']))
    {
        if (strpos($_GET['ec'], 'zen') === 0 ) {
            $order_id = substr($_GET['ec'],3);
        } else {
            $order_id = $_GET['ec'];
        }
    }

    if (!isset($_mid) || !isset($_mkey)) {
        exit('Payment not configured');
    }
	$_pending = (isset($_pending) && $_pending > 0) ? $_pending : DEFAULT_ORDERS_STATUS_ID;
	$_failure = (isset($_failure) && $_failure > 0) ? $_failure : DEFAULT_ORDERS_STATUS_ID;
	$_success = (isset($_success) && $_success > 0) ? $_success : DEFAULT_ORDERS_STATUS_ID;
    $_reservation = (isset($_reservation) && $_reservation > 0) ? $_reservation : DEFAULT_ORDERS_STATUS_ID;
	$check = $db->Execute('select * from ' . TABLE_ORDERS . ' where orders_id=' . (int)$order_id);


	while (!$check->EOF) 
	{		
		if ($check->fields['orders_status'] != 1 && $check->fields['orders_status'] != $_pending && $check->fields['orders_status'] != $_failure && $check->fields['orders_status'] != $_reservation) {
			echo 'ZenCart status not allowed ' . $check->fields['orders_status'] . '(' . $_pending . ')';
			exit;
		}
		
		$check->MoveNext();
	}
 
    $sisow = new Sisow($_mid, $_mkey);
    if(($ex = $sisow->StatusRequest($trxid)) == 0)
    {
        $failed = false;
        switch ($sisow->status) {
            case 'Success':
                $statusid = $_success;
                $commentaar = 'Transactie betaald met Sisow '.$_name.'.';
                break;
            case 'Reservation':
                $statusid = $_reservation;
                if ($payment == 'sisowklarna' && defined(MODULE_PAYMENT_SISOWKLARNA_INVOICE) && MODULE_PAYMENT_SISOWKLARNA_INVOICE == 'True') {
                    $sisow->InvoiceRequest($trxid);
                    $statusid = $_success;
                }
                break;

            case 'Failure':
            case 'Expired':
            case 'Cancelled':
            case 'Denied':
                $statusid = $_failure;
                $commentaar = 'Sisow '.$_name.' transactie is mislukt.';
                $failed = true;
                break;
            default:
                break;
        }
    }
    else{ print_r($ex); exit;}
	
	if($statusid == 0)
		$statusid = DEFAULT_ORDERS_STATUS_ID;

    $ex = $db->Execute("update " . TABLE_ORDERS . " set orders_status = '" . $statusid . "', last_modified = now() where orders_id = '" . $order_id . "'");
    
    $sql_data_array = array('orders_id' => $order_id,
            'orders_status_id' => $statusid,
            'date_added' => 'now()',
            'customer_notified' => '1',
            'comments' => $commentaar);
    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

    $base = new SisowBase();
    if($failed == true)
    {
        if ($_restock == 'True'){
            $base->restock($order_id);
        }

        $base->delete_order($order_id);
    }
    else 
    {
        $base->send_mail();
		zen_session_destroy();
		exit;
    }
}
else
{
    if ($_GET['status'] == 'Success' || $_GET['status'] == 'Reservation') {
	    global $cart;
		if ($cart) {
			$cart->remove_all();
		}
		session_start();
		$_SESSION['cart']->reset(TRUE);
		global $db;

		$sql = "delete from " . TABLE_CUSTOMERS_BASKET . " where customers_id = " . (int)$_GET['cid'];
		$db->Execute($sql);
		$sql = "delete from " . TABLE_CUSTOMERS_BASKET_ATTRIBUTES . " where customers_id = " . (int)$_GET['cid'];
		$db->Execute($sql);
		zen_redirect(zen_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
	}
	else {
	    if ( in_array($_GET['status'], ['Failure', 'Expired','Cancelled','Denied'])) {
            global $order, $order_id;

            if (isset($order_id)) {
                $base = new SisowBase();

                if ($_restock == 'True') {
                    $base->restock($order_id);
                }
                $base->delete_order($order_id);
            }
        }

        global $messageStack;
        $messageStack->add_session('checkout_payment','Betaling niet geslaagd!', 'error');
		zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT,'', 'SSL'));
	}
}

