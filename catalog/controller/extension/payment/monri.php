<?php

class ControllerExtensionPaymentMonri extends Controller
{
    /**
     * @var array $payload Payload to pass into view(s).
     */
    private $payload;

    /**
     * ControllerExtensionPaymentMonri constructor.
     *
     * @param $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);

        if($this->request->get['route'] === 'extension/payment/monri/callback') {
            return;
        }

        $data['test_mode'] = $this->config->get('payment_monri_test');
        $data['monri_key'] = $this->config->get('payment_monri_merchant_key');
        $data['monri_secret_key'] = $this->config->get('payment_monri_authenticity_token');
        $data['monri_processing_method'] = $this->config->get('payment_monri_processing_method');
        $data['monri_language'] = $this->config->get('payment_monri_language');
        $data['monri_transaction_type'] = $this->config->get('payment_monri_transaction_type');

        $data['liveurl'] = $data['test_mode'] ? 'https://ipgtest.monri.com/v2/form' : 'https://ipg.monri.com/v2/form';

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $data['order_number'] = $this->session->data['order_id'];
        $data['amount'] = $order_info['total'] * 100;
        $data['ch_full_name'] = $order_info['payment_firstname'] . " " . $order_info['payment_lastname'];
        $data['ch_address'] = $order_info['payment_address_1'];
        $data['ch_city'] = $order_info['payment_city'];
        $data['ch_zip'] = $order_info['payment_postcode'];
        $data['ch_country'] = $order_info['payment_country'];
        $data['ch_phone'] = $order_info['telephone'];
        $data['ch_email'] = $order_info['email'];
        $data['currency'] = $order_info['currency_code'];
        $data['language'] = $this->config->get('payment_monri_language');
        $data['authenticity_token'] = $this->config->get('payment_monri_authenticity_token');
        $data['order_description'] = $data['order_number'] . " - " . date('d.m.Y H:i');

        $monriKey = $this->config->get('payment_monri_merchant_key');

        $data['digest'] = $this->digestV2($monriKey, $data['order_number'], $data['amount'], $data['currency']);

        if ($data['language'] === 'ba') {
            $data['language'] = 'hr';
        }

        $this->payload = $data;
    }

    public function index()
    {
        $data = $this->payload;
        $data['button_confirm'] = $this->language->get('button_confirm');

        return $this->load->view('extension/payment/monri', $data);
    }

    public function form()
    {
        // All the necessary page elements
        $this->load->language('extension/payment/monri');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout')
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_form'),
            'href' => $this->url->link('extension/payment/monri/form')
        );

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $data += $this->payload;

        // Load the template file and show output
        $this->response->setOutput($this->load->view('extension/payment/monri_form_1', $data));
    }

    /**
     * Funkcija Success radi update narudžbe u Success ili Failed ako se digesti ne podudaraju
     */
    public function success()
    {
        $this->load->language('extension/payment/monri'); //File language
        $this->load->model('checkout/order');
        $data['monri_transaction_type'] = $this->config->get('payment_monri_transaction_type');
        $order_number = $_REQUEST['order_number'] ?? null;
        $data['order_id'] = (int)$order_number;

        if(!$order_number) {
            return $this->response->redirect(
                $this->url->link('common/home')
            );
        }

        $monri_key = $this->config->get('payment_monri_merchant_key');
        $digest_monri = $_REQUEST['digest'];

        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }

        $success_url = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
        $params = $_GET;
        $get_data = $success_url . "/success";
        $split = "?";
        foreach ($params as $key => $value) {
            $value2 = str_replace(' ', '+', $value);
            if ($key != "digest" && $key != "route") {
                $get_data .= $split . $key . "=" . $value2;
                $split = "&";
            }
        }

        $digest_shop = $this->digestUpdateV2($monri_key, $get_data);

        if ($digest_shop == $digest_monri) {
            if ($data['monri_transaction_type'] == 'authorize') {
                $data['order_status'] = 1; //Status pending
            } else {
                $data['order_status'] = 5; //Status complete
            }

            $data['comment'] = $this->language->get('text_success_message');
            $this->model_checkout_order->addOrderHistory($data['order_id'], $data['order_status'], $data['comment'], true, false);
            $this->response->redirect($this->url->link('checkout/success'));
        } else {
            $data['order_status'] = 10; //Status failed
            $data['comment'] = $this->language->get('text_error_message');
            $this->model_checkout_order->addOrderHistory($data['order_id'], $data['order_status'], $data['comment'], true, false);
            $this->response->redirect($this->url->link('checkout/failure'));
        }
    }

    /**
     * Funkcija mijenja status missing order u canceled
     */
    public function fail()
    {
        $this->load->language('extension/payment/monri'); //File language
        $this->load->model('checkout/order');
        $order_id = $_REQUEST['order_number'] ?? null;
        $data['order_id'] = (int)$order_id;

        if(!$order_id) {
            return $this->response->redirect(
                $this->url->link('common/home')
            );
        }

        $data['order_status'] = 7; //Status canceled
        $this->model_checkout_order->addOrderHistory($data['order_id'], $data['order_status'], true, false);
        $this->document->setTitle($this->language->get('heading_title_fail'));
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        //https://payment.demo.ba/opencart/opencart_test_monri1/extension/payment/monri/fail?language=hr&order_number=14vz_test

        $data['heading_title'] = $this->language->get('heading_title_fail');
        $data['text_message'] = $this->language->get('text_message_fail');
        $data['continue'] = $this->url->link('common/home');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        /**
         * Set custom output
         */
        $this->response->setOutput($this->load->view('common/monri_cancel_message', $data));
    }

    public function callback()
    {
        define('MONRI_CALLBACK_IMPL', true);
        require_once 'callback-url.php';

        $merchant_key = $this->config->get('payment_monri_merchant_key');

        if(!$merchant_key) {
            monri_error('Monri key is not defined or does not exist.', array(404, 'Not Found'));
        }

        $directory = dirname(dirname(dirname(dirname($_SERVER['REQUEST_URI']))));
        $pathname = $directory . '/extension/payment/monri/callback';

        monri_handle_callback($pathname, $merchant_key, function($payload) {
            $order_number = $payload['order_number'];
            $transaction_type = $this->config->get('payment_monri_transaction_type');

            $status = $transaction_type === 'authorize' ? 1 : 5;

            $this->model_checkout_order->addOrderHistory(
                $order_number, $status, $this->language->get('text_success_message'), true, false
            );
        });
    }


    // Računanje digesta za slanje podataka form V1
    public function digestV1($key, $order_number, $amount, $currency)
    {
        $digest = SHA1($key . $order_number . $amount . $currency);
        return $digest;
    }

    // Računanje digesta za slanje podataka form V2
    public function digestV2($key, $order_number, $amount, $currency)
    {
        $digest = hash('sha512', $key . $order_number . $amount . $currency);
        return $digest;
    }

    // Računanje digesta za update narudžbe form V2
    public function digestUpdateV2($key, $url)
    {
        $digest = hash('sha512', $key . $url);
        return $digest;
    }


}
