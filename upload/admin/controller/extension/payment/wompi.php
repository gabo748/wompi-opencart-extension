<?php

class ControllerExtensionPaymentWompi extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/wompi');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_wompi', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect(
                $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
            );
        }

        $data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
            ),
            array(
                'text' => $this->language->get('text_extension'),
                'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true),
            ),
            array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/payment/wompi', 'user_token=' . $this->session->data['user_token'], true),
            ),
        );

        $data['action'] = $this->url->link('extension/payment/wompi', 'user_token=' . $this->session->data['user_token'], true);
        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['payment_wompi_status'] = $this->config->get('payment_wompi_status') ?: '';
        $data['payment_wompi_client_id'] = $this->config->get('payment_wompi_client_id') ?: '';
        $data['payment_wompi_client_secret'] = $this->config->get('payment_wompi_client_secret') ?: '';
        $data['payment_wompi_order_status_id'] = $this->config->get('payment_wompi_order_status_id') ?: '';

        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->response->setOutput($this->load->view('extension/payment/wompi', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/wompi')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }
}
