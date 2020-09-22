<?php
class ControllerExtensionPaymentApurata extends Controller {
	public function index() {
		$this->load->language('extension/payment/apurata');

		return $this->load->view('extension/payment/apurata', $data);
	}

	public function confirm() {
		$json = array();
		
		if ($this->session->data['payment_method']['code'] == 'apurata') {
			$this->load->language('extension/payment/apurata');

			$this->load->model('checkout/order');

			$comment  = $this->language->get('text_instruction') . "\n\n";
			$comment .= $this->config->get('payment_bank_transfer_bank' . $this->config->get('config_language_id')) . "\n\n";
			$comment .= $this->language->get('text_payment');

			$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
			
			$apurata_new_order_url = $this->language->get('apurata_api_domain');
			
			if ($order_info) {
				$apurata_new_order_url .= '/pos/crear-orden-y-continuar?' .
				'order_id=' . urlencode($this->session->data['order_id']) .
				'&pos_client_id=' . urlencode($this->config->get('payment_apurata_client_id')) .
				'&amount=' . urlencode($order_info['total']) .
				'&url_redir_on_canceled=' . urlencode($this->url->link('checkout/checkout')) .
				'&url_redir_on_rejected=' . urlencode($this->url->link('checkout/checkout')) .
				'&url_redir_on_success=' . urlencode($this->url->link('checkout/success')) .
				'&customer_data__customer_id=' . urlencode($this->session->data['customer_id']) .
				'&customer_data__billing_company=' . urlencode('') .
				'&customer_data__shipping_company=' . urlencode('') .
				'&customer_data__email=' . urlencode($order_info['email']) .
				'&customer_data__phone=' . urlencode($order_info['telephone']) .
				'&customer_data__billing_address_1=' . urlencode($order_info['payment_address_1']) .
				'&customer_data__billing_address_2=' . urlencode($order_info['payment_address_2']) .
				'&customer_data__billing_first_name=' . urlencode($order_info['payment_firstname']) .
				'&customer_data__billing_last_name=' . urlencode($order_info['payment_lastname']) .
				'&customer_data__billing_city=' . urlencode($order_info['payment_city']) .
				'&customer_data__shipping_address_1=' . urlencode($order_info['shipping_address_1']) .
				'&customer_data__shipping_address_2=' . urlencode($order_info['shipping_address_2']) .
				'&customer_data__shipping_first_name=' . urlencode($order_info['shipping_firstname']) .
				'&customer_data__shipping_last_name=' . urlencode($order_info['shipping_lastname']) .
				'&customer_data__shipping_city=' . urlencode($order_info['shipping_city']) ;
			}

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_bank_transfer_order_status_id'), $comment, true);
		
			$json['redirect'] = $apurata_new_order_url;
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}

	/*
		default order status id
		Canceled = 7
		Canceled Reversal = 9
		Chargeback = 13
		Complete = 5
		Denied = 8
		Expired = 14
		Failed = 10
		Pending = 1
		Processed = 15
		Processing = 2
		Refunded = 11
		Reversed = 12
		Shipped = 3
		Voided = 16
	*/
	public function handle_event() {

		// Check Authorization

		if (!array_key_exists('Apurata-Auth', getallheaders())) {
			http_response_code(401);
			die('Missing authorization header');
		}

		$auth = getallheaders()['Apurata-Auth'];

		list($auth_type, $token) = explode(' ', $auth);
		
		if (strtolower($auth_type) != 'bearer'){
			http_response_code(401);
			die('Invalid authorization type');
		}
		if ($token != $this->config->get('payment_apurata_client_secret')) {
			http_response_code(401);
			die('Invalid authorization token');
		}

		$this->load->model('checkout/order');

		$json = array();
		
		if (isset($this->request->post['order_id']) && isset($this->request->post['event'])) {

			if (!$this->model_checkout_order->getOrder($this->request->post['order_id'])) {
				die('Order not found');
			}

			$order_id = $this->request->post['order_id'];
			
			$event = $this->request->post['event'];
			
			$new_order_status = '0';
			
			$comment = '';

			switch ($event) {
				case 'onhold':
					$new_order_status = '2';
					$comment = 'Apurata puso la orden en onhold';
					break;
				case 'validated':
					$new_order_status = '5';
					$comment = 'Apurata validó identidad';
					break;
				case 'rejected':
					$new_order_status = '10';
					$comment = 'Apurata rechazó la orden';
					break;
				case 'canceled':
					$new_order_status = '10';
					$comment = 'El financiamiento en Apurata fue cancelado';
					break;
				default:
					die('Unsupported event');
			}
		} else {
			die('Illegal Access');
		}

		$this->model_checkout_order->addOrderHistory($order_id, $new_order_status, $comment, true);
	}
}