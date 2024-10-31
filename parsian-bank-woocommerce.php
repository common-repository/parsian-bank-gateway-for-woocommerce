<?php
/*
Plugin Name: Parsian Bank Gateway for Woocommerce
Version: 1.0
Description: درگاه بانک پارسیان برای ووکامرس مطابق با آخرین تغییرات
Plugin URI: https://dpsoft.ir/%D9%88%D8%A8%D9%84%D8%A7%DA%AF/26/%D8%A7%D8%B3%D8%AA%D9%81%D8%A7%D8%AF%D9%87-%D8%A7%D8%B2-%D8%AF%D8%B1%DA%AF%D8%A7%D9%87-%D9%BE%D8%B1%D8%AF%D8%A7%D8%AE%D8%AA-%D8%A8%D8%A7%D9%86%DA%A9-%D9%BE%D8%A7%D8%B1%D8%B3%DB%8C%D8%A7%D9%86-%D8%AF%D8%B1-%D8%B2%D8%A8%D8%A7%D9%86-PHP
Author: Dpsoft
Author URI: https://dpsoft.ir
*/

require_once __DIR__ . "/vendor/autoload.php";

function Load_ParisanBankDp_Gateway() {
	//is it possible to load gateway
	if (
		extension_loaded( 'soap' ) &&
		class_exists( 'WC_Payment_Gateway' ) &&
		! class_exists( 'WC_BankParsian_Dp_Gateway' ) &&
		! function_exists( 'Woocommerce_Add_ParisanBankDp_Gateway' )
	) {
		add_filter( 'woocommerce_payment_gateways', 'Woocommerce_Add_ParisanBankDp_Gateway' );

		function Woocommerce_Add_ParisanBankDp_Gateway( $methods ) {
			$methods[] = 'WC_BankParsian_Dp_Gateway';

			return $methods;
		}

		//load payment gateway class
		require_once __DIR__ . "/src/WC_BankParsian_Dp_Gateway.php";
	}
}

add_action( 'plugins_loaded', 'Load_ParisanBankDp_Gateway', 0 );

//check whether soap extension loaded and if not loaded show proper error message
register_activation_hook( __FILE__, 'ParisanBankDp_activation_hook' );

function ParisanBankDp_activation_hook() {
	if ( ! extension_loaded( 'soap' ) ) {
		set_transient( 'ParisanBankDp_no_soap_notice', true, 5 );
	}
}

add_action( 'admin_notices', 'ParisanBankDp_no_soap_show_message' );

function ParisanBankDp_no_soap_show_message() {
	if ( get_transient( 'ParisanBankDp_no_soap_notice' ) and ! extension_loaded( 'soap' ) ) {
		$message = <<<'WARNING'
<div class="notice notice-error">
    <p><strong>%s</strong></p>
    <p>برای اطلاعات بیشتر از این مشکل و نحوه ی رفع این مشکل میتوانید از <a target="_blank"
            href="http://4xmen.ir/%D8%A8%D8%B1%D8%B1%D8%B3%DB%8C-%D9%86%D8%B5%D8%A8-%D8%A8%D9%88%D8%AF%D9%86-soap-%D8%AF%D8%B1-php-%D9%88-%D9%86%D8%AD%D9%88%D9%87-%D9%86%D8%B5%D8%A8-%D8%A2%D9%86-%D8%B1%D9%88%DB%8C-%D8%B3%D8%B1%D9%88/">بررسی
        نصب بودن Soap در PHP و نحوه نصب آن روی سرور های لینوکس
    </a> استفاده کنید.
    </p>
</div>
WARNING;
		echo sprintf(
			$message,
			__( "افزونه ی درگاه بانک پارسیان نیازمند افزونه‌ی Soap مرتبط با php می باشد. بدون این افزونه امکان استفاده از این پلاگین نیست. لطفا ابتدا این افزونه رو فعال کنید.",
				"woocommerce" )
		);
	}
	delete_transient( 'ParisanBankDp_no_soap_notice' );
}
