<?php
class ControllerExtensionPaymentApurata extends Controller {
	private $error = array();
	
	public function index() {
		$this->load->language('extension/payment/apurata');

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$this->model_setting_setting->editSetting('payment_apurata', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');
			
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['apurata'])) {
			$data['error_apurata'] = $this->error['apurata'];
		} else {
			$data['error_apurata'] = array();
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/apurata', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/payment/apurata', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);


		$data['payment_apurata'] = array();

		if (isset($this->request->post['payment_apurata_client_id'])) {
				$data['payment_apurata_client_id'] = $this->request->post['payment_apurata_client_id'];
		} else {
				$data['payment_apurata_client_id'] = $this->config->get('payment_apurata_client_id');
		}
						
		if (isset($this->request->post['payment_apurata_client_secret'])) {
				$data['payment_apurata_client_secret'] = $this->request->post['payment_apurata_client_secret'];
		} else {
				$data['payment_apurata_client_secret'] = $this->config->get('payment_apurata_client_secret');
		}

		if (isset($this->request->post['payment_apurata_status'])) {
			$data['payment_apurata_status'] = $this->request->post['payment_apurata_status'];
		} else {
			$data['payment_apurata_status'] = $this->config->get('payment_apurata_status');
		}

		if (isset($this->request->post['payment_apurata_allow_http'])) {
			$data['payment_apurata_allow_http'] = $this->request->post['payment_apurata_allow_http'];
		} else {
			$data['payment_apurata_allow_http'] = $this->config->get('payment_apurata_allow_http');
		}

		$this->load->model('localisation/order_status');
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$this->template = 'payment/custom.tpl';
										
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/apurata', $data));
	}
}