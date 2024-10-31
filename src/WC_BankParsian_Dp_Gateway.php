<?php

use Dpsoft\Parsian\Parsian;

/**
 * Parsian bank payment gateway
 *
 * Class WC_BankParsian_Dp_Gateway
 */
class WC_BankParsian_Dp_Gateway extends WC_Payment_Gateway {

	/**
	 * gateway pin
	 *
	 * @var string
	 */
	public $pin;
	/**
	 * @var string
	 */
	private $success_massage;
	/**
	 * @var string
	 */
	private $failed_massage;
	/**
	 * @var string
	 */
	private $connection_error_massage;

	/**
	 * Init gateway settings and set actions
	 *
	 * WC_BankParsian_Dp_Gateway constructor.
	 */
	public function __construct() {
		$this->author             = 'dpsoft.ir';
		$this->id                 = 'bankparsian';
		$this->method_title       = __( 'بانک پارسیان', 'woocommerce' );
		$this->method_description = __(
			'تنظیمات درگاه پرداخت بانک پارسیان برای افزونه فروشگاه ساز ووکامرس',
			'woocommerce'
		);
		$this->icon               = WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/../assets/images/logo.png';
		$this->has_fields         = false;
		$this->init_form_fields();
		$this->init_settings();
		$this->title                    = $this->get_option( 'title' );
		$this->description              = $this->get_option( 'description' );
		$this->pin                      = $this->get_option( 'pin' );
		$this->connection_error_massage = $this->get_option( 'connection_error_massage' );
		$this->success_massage          = $this->get_option( 'success_massage' );
		$this->failed_massage           = $this->get_option( 'failed_massage' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		add_action( 'woocommerce_receipt_' . $this->id . '', array( $this, 'sendToGateway' ) );
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ) . '', array( $this, 'callback' ) );
	}

	/**
	 * prepare gateway settings
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'base_confing'             => array(
				'title'       => __( 'تنظیمات پایه', 'woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'enabled'                  => array(
				'title'       => __( 'فعال/غیرفعال', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'فعال سازی درگاه بانک پارسیان', 'woocommerce' ),
				'description' => __(
					'برای فعال سازی درگاه بانک پارسیان این گزینه را تیک بزنید.',
					'woocommerce'
				),
				'default'     => 'yes',
				'desc_tip'    => true,
			),
			'title'                    => array(
				'title'       => __( 'عنوان', 'woocommerce' ),
				'type'        => 'text',
				'description' => __(
					'این عنوان در هنگام انتخاب روش پرداخت به مشتری نشان داده می شود.',
					'woocommerce'
				),
				'default'     => __( 'بانک پارسیان', 'woocommerce' ),
				'desc_tip'    => true,
			),
			'description'              => array(
				'title'       => __( 'توضیحات', 'woocommerce' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __(
					'این توضیحات در هنگام انتخاب روش پرداخت به مشتری نشان داده می شود.',
					'woocommerce'
				),
				'default'     => __( 'پرداخت امن از طریق درگاه بانک پارسیان', 'woocommerce' ),
			),
			'account_confing'          => array(
				'title'       => __( 'تنظیمات بانک', 'woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'pin'                      => array(
				'title'       => __( 'شناسه پذیرنده', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'پین کد دریافت شده از بانک پارسیان.', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'payment_confing'          => array(
				'title'       => __( 'تنظیمات عملیات پرداخت', 'woocommerce' ),
				'type'        => 'title',
				'description' => '',
			),
			'connection_error_massage' => array(
				'title'       => __( 'پیام خطا در اتصال به بانک', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __(
					'متن پیامی که میخواهید پس از خطا در اتصال به بانک به مشتری نمایش داده شود.',
					'woocommerce'
				),
				'default'     => __( 'خطا در اتصال به بانک، لطفا مجددا تلاش نمایید.', 'woocommerce' ),
			),
			'success_massage'          => array(
				'title'       => __( 'پیام پرداخت موفق', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __(
					'متن پیامی که میخواهید پس از پرداخت موفق به مشتری نمایش داده شود.',
					'woocommerce'
				),
				'default'     => __(
					'با تشکر از شما، پرداخت سفارش شما با موفقیت انجام شد، کد رهگیری خود را یادداشت نمایید.',
					'woocommerce'
				),
			),
			'failed_massage'           => array(
				'title'       => __( 'پیام پرداخت ناموفق', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __(
					'متن پیامی که میخواهید پس از پرداخت ناموفق به مشتری نمایش داده شود.',
					'woocommerce'
				),
				'default'     => __(
					'پرداخت سفارش شما ناموفق بوده است. لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.',
					'woocommerce'
				),
			),
		);
	}

	/**
	 * prepare and redirect customer to payment gateway
	 *
	 * @param $orderId
	 */
	public function sendToGateway( $orderId ) {
		global $woocommerce;
		$woocommerce->session->order_id_bankparsian = $orderId;

		$order       = new \WC_Order( $orderId );
		$callbackUrl = add_query_arg(
			'wc_order',
			$orderId,
			WC()->api_request_url( 'WC_BankParsian_Dp_Gateway' )
		);

		$amount = intval( $order->get_total() );
		echo $this->getHtmlForm( $woocommerce );
		//if user submitted form thus we send him to bank gateway
		if ( isset( $_POST['bankparsian_submit'] ) ) {
			try {
				$parsian                                 = new Parsian( $this->pin );
				$response                                = $parsian->payRequest( $amount, $callbackUrl );
				$woocommerce->session->token_bankparsian = $response['token'];
				$order->add_order_note( __( "در حال ارسال کاربر به درگاه بانک پارسیان", "woocommerce" ) );
				$parsian->redirect();
			} catch ( \Throwable $exception ) {
				$message = $exception->getMessage();
				$order->add_order_note(
					sprintf(
						__( 'خطا در هنگام ارسال به بانک : %s', 'woocommerce' ),
						$message
					)
				);
				wc_add_notice( $this->connection_error_massage, 'error' );
			}
		}
	}

	/**
	 * redirect form for customer
	 *
	 * @param $woocommerce
	 *
	 * @return string the html form
	 */
	private function getHtmlForm( $woocommerce ) {
		return sprintf(
			'<form action="" method="POST" class="bankparsian-checkout-form" id="bankparsian-checkout-form">
						<input type="submit" name="bankparsian_submit" class="button alt" id="bankparsian-payment-button" value="%s"/>
						<a class="button cancel" href="%s">%s</a>
					 </form>
					 <br/>',
			__(
				'پرداخت',
				'woocommerce'
			),
			$woocommerce->cart->get_checkout_url(),
			__(
				'بازگشت',
				'woocommerce'
			)
		);
	}

	/**
	 * handle customer transaction and check whether transaction is succeeded or failed
	 */
	public function callback() {
		global $woocommerce;
		//check whether  $_GET['wc_order'] is valid orderId
		if ( isset( $_GET['wc_order'] ) and is_numeric( $_GET['wc_order'] ) and intval( $_GET['wc_order'] ) > 0 ) {
			$orderId = intval( $_GET['wc_order'] );
		} else {
			$orderId = intval( $woocommerce->session->order_id_bankparsian );
		}
		$token = $woocommerce->session->token_bankparsian;

		if ( $orderId and $token ) {
			try {
				$order   = new \WC_Order( $orderId );
				$parsian = new Parsian( $this->pin );

				$response = $parsian->verify( $token, intval( $order->get_total() ) );


				wc_add_notice( $this->success_massage, 'success' );
				$notice = str_replace(
					'{authority}',
					$response['Token'],
					__( 'کد رهگیری شما: {authority}', 'woocommerce' )
				);
				$order->add_order_note( sprintf( "پرداخت انجام شد.کد رهگیری سفارش :‌ %s", $response['Token'] ) );
				wc_add_notice( $notice, 'success' );

				update_post_meta( $orderId, '_transaction_id', $response['Token'] );
				update_post_meta( $orderId, '_hash_card_number', $response['HashCardNumber'] );
				update_post_meta( $orderId, '_bank_payment', __( 'بانک پارسیان', 'woocommerce' ) );
				$order->payment_complete( $orderId );
				$woocommerce->cart->empty_cart();
				wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );

			} catch ( \Throwable $exception ) {
				$message = __( $exception->getMessage(), 'woocommerce' );
				$order->add_order_note( "خطا در هنگام بازگشت از بانک:  $message" );
				wc_add_notice( $this->failed_massage . "<br><small>$message</small>", 'error' );
				wp_redirect( $woocommerce->cart->get_checkout_url() );
			}
		} else {
			$notice = __( 'شناسه سفارش وجود ندارد.', 'woocommerce' );
			wc_add_notice( $notice, 'error' );

			wp_redirect( $woocommerce->cart->get_checkout_url() );
		}
	}

	public function process_admin_options() {
		return parent::process_admin_options();
	}

	public function process_payment( $order_id ) {
		$order = new \WC_Order( $order_id );

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}
}