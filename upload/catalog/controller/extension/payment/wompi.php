<?php

class ControllerExtensionPaymentWompi extends Controller {
    public function index() {
        $this->load->language('extension/payment/wompi');
        $data['action'] = $this->url->link('extension/payment/wompi/send', '', true);
        return $this->load->view('extension/payment/wompi', $data);
    }

    public function send() {
        $json = array();

        // Load the required model
        $this->load->model('checkout/order');

        // Check if the order ID exists
        if (!isset($this->session->data['order_id'])) {
            $json['error'] = 'No order ID found.';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Get the order details
        $order_id = $this->session->data['order_id'];
        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info) {
            $json['error'] = 'Order not found.';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Obtain the access token
        $client_id = $this->config->get('payment_wompi_client_id');
        $client_secret = $this->config->get('payment_wompi_client_secret');
        $access_token = $this->getAccessToken($client_id, $client_secret);

        if (!$access_token) {
            $json['error'] = 'Failed to obtain access token.';
            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Create the payment link
        $payment_url = $this->createPaymentLink($access_token, $order_info['total']);

        if (!$payment_url) {
            $json['error'] = 'Failed to create payment link.';
        } else {
            $json['redirect'] = $payment_url;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    private function getAccessToken($client_id, $client_secret) {
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://id.wompi.sv/connect/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query(array(
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'audience' => 'wompi_api',
            )),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
            ),
        ));

        // Execute cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Close cURL
        curl_close($curl);

        // Check for cURL errors
        if ($err) {
            $this->log->write("Wompi cURL Error: " . $err);
            return null;
        }

        // Decode the JSON response
        $data = json_decode($response, true);

        // Check for access token in the response
        if (isset($data['access_token'])) {
            return $data['access_token'];
        } else {
            $this->log->write("Wompi Token Error: " . json_encode($data));
            return null;
        }
    }

    private function createPaymentLink($access_token, $amount) {
        $curl = curl_init();

        // Set cURL options
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.wompi.sv/EnlacePago',
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode(array(
                'identificadorEnlaceComercio' => 'Mugiwaras open cart',
                'monto' => $amount,
                'nombreProducto' => 'Productos desde opencart',
            )),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ),
        ));

        // Execute cURL request
        $response = curl_exec($curl);
        $err = curl_error($curl);

        // Close cURL
        curl_close($curl);

        // Check for cURL errors
        if ($err) {
            $this->log->write("Wompi Payment Link cURL Error: " . $err);
            return null;
        }

        // Decode the JSON response
        $data = json_decode($response, true);

        // Return the payment URL if available
        if (isset($data['urlEnlace'])) {
            return $data['urlEnlace'];
        } else {
            $this->log->write("Wompi Payment Link Error: " . json_encode($data));
            return null;
        }
    }
}
