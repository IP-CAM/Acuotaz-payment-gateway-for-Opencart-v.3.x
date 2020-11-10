<?php
class ModelExtensionPaymentApurata extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/apurata');

		$method_data = array();

		$this->acuotazLog('amount: ' . $total);
		if (!$this->should_hide_apurata_gateway($total)) {
			$method_data = array(
				'code'       => 'apurata',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => '3'
			);
		}
		return $method_data;
	}

	function should_hide_apurata_gateway($total) {
		$isHttps = array_key_exists('HTTPS', $_SERVER)? $_SERVER['HTTPS'] : array_key_exists('REQUEST_SCHEME', $_SERVER)? $_SERVER['REQUEST_SCHEME'] : null;

		$isHttps = $isHttps && (
			strcasecmp('1', $isHttps) == 0
			|| strcasecmp('on', $isHttps) == 0
			|| strcasecmp('https', $isHttps) == 0
		);

		if (!$this->config->get('payment_apurata_allow_http') && !$isHttps) {
			$this->acuotazLog('Allow HTTP hide payment method');
			return true;
		}

		if ($this->session->data['currency'] != $this->language->get('apurata_currency')) {
			$this->acuotazLog('Currency: ' . $this->session->data['currency'] . ' hide payment method, must be ' . $this->language->get('apurata_currency'));
			return true;
		}

		$landing_config = $this->get_landing_config();
		
		if (!is_object($landing_config) || (is_object($landing_config) && ($landing_config->min_amount > $total || $landing_config->max_amount < $total))) {
			$this->acuotazLog('Amount: ' . $total . ' hide payment method');
			return true;
		}
		return false;
	}

	function get_landing_config() {
		list ($httpCode, $landing_config) = $this->make_curl_to_apurata("GET", "/pos/client/landing_config");
		$landing_config = json_decode($landing_config);
		return isset($landing_config)? $landing_config : null;
	}

	function make_curl_to_apurata($method, $path) {
		// $method: "GET" or "POST"
		// $path: e.g. /pos/client/landing_config
		$ch = curl_init();
		$this->load->language('extension/payment/apurata');

		$url = $this->language->get('apurata_api_domain') . $path;
		curl_setopt($ch, CURLOPT_URL, $url);

		// Timeouts
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);    // seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 2); // seconds

		$headers = array("Authorization: Bearer " . $this->config->get('payment_apurata_client_secret'));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		if (strtoupper($method) == "GET") {
			curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
		} else if (strtoupper($method) == "POST") {
			curl_setopt($ch, CURLOPT_POST, TRUE);
		} else {
			throw new Exception("Method not supported: " . $method);
		}
		$ret = curl_exec($ch);
		$this->acuotazLog('Making curl to ' . $url);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($httpCode != 200) {
			$this->acuotazLog("Apurata responded with http_code ". $httpCode . " on " . $method . " to " . $url);
			error_log("Apurata responded with http_code ". $httpCode . " on " . $method . " to " . $url);
		}
		curl_close($ch);
		$this->acuotazLog('code: ' . $httpCode . ' | ret: ' . $ret);
		return array($httpCode, $ret);
	}

	function acuotazLog($var) {
		echo "<script>console.log('aCuotaz model','" . json_encode($var) . "');</script>";
	}
}