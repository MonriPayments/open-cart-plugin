<?php
class ControllerExtensionPaymentPikpay extends Controller
{

    public function index()
    {
        $data['button_confirm'] = $this->language->get('button_confirm');
        return $this->load->view('extension/payment/pikpay', $data);
    }

    public function form()
    {
        // All the necessary page elements
        $this->load->language('extension/payment/pikpay');

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
            'href' => $this->url->link('extension/payment/pikpay/form')
        );

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $data['test_mode']                = $this->config->get('payment_pikpay_test'); //Podaci iz administracije
        $data['pikpay_key']               = $this->config->get('payment_pikpay_key');
        $data['pikpay_secret_key']        = $this->config->get('payment_pikpay_secret_key');
        $data['pikpay_processing_method'] = $this->config->get('payment_pikpay_processing_method');
        $data['pikpay_processor']         = $this->config->get('payment_pikpay_processor');
        $data['pikpay_language']          = $this->config->get('payment_pikpay_language');
        $data['pikpay_transaction_type']  = $this->config->get('payment_pikpay_transaction_type');

        //Linkovi za formu
        if($data['test_mode'])
            {
                $data['liveurl'] = 'https://ipgtest.monri.com/v2/form';
            }
            else{
                $data['liveurl'] = 'https://ipg.monri.com/v2/form';
            }

        // Order data
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);


        $data["order_number"] = $this->session->data["order_id"];
        $data["amount"]       = $order_info["total"]*100;
        $data["ch_full_name"] = $order_info["payment_firstname"] . " " . $order_info["payment_lastname"];
        $data["ch_address"]   = $order_info["payment_address_1"];
        $data["ch_city"]      = $order_info["payment_city"];
        $data["ch_zip"]       = $order_info["payment_postcode"];
        $data["ch_country"]   = $order_info["payment_country"];
        $data["ch_phone"]     = $order_info["telephone"];
        $data["ch_email"]     = $order_info["email"];
        $data["currency"]     = $order_info["currency_code"];
        $data["language"]     = $this->config->get('payment_pikpay_language');
        $data["authenticity_token"] = $this->config->get('payment_pikpay_secret_key');
        $data['order_description'] = $data["order_number"] . " - " . date('d.m.Y H:i');

        $pikpay_key = $this->config->get('payment_pikpay_key');
        if($data['pikpay_processor'] == "pikpay")
        {
            $data["digest"] = $this->digestV1($pikpay_key, $data["order_number"], $data['amount'], $data["currency"]);
        }else{
            $data["digest"] = $this->digestV2($pikpay_key, $data["order_number"], $data['amount'], $data["currency"]);
        }



        if($data["language"] == 'ba')
        {
            $data["language"] = 'hr';
        }

        // Load the template file and show output
        $this->response->setOutput($this->load->view('extension/payment/pikpay_form_1', $data));
    }

    /**
     * Funkcija Success radi update narudžbe u Success ili Failed ako se digesti ne podudaraju
     */
    public function success()
    {
        $this->load->language('extension/payment/pikpay'); //File language
        $this->load->model('checkout/order');
        $data['pikpay_transaction_type']  = $this->config->get('payment_pikpay_transaction_type');
        $order_number = $_REQUEST['order_number'];
        $data['order_id'] = (int)$order_number;

        $pikpay_key = $this->config->get('payment_pikpay_key');
        $digest_pikpay = $_REQUEST['digest'];


        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        {
            $protocol = 'https://';
        }
        else {
            $protocol = 'http://';
        }

        $success_url = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
        $params = $_GET;
        $get_data = $success_url . "/success";
        $split = "?";
        foreach ($params as $key => $value)
        {
            $value2 = str_replace(' ', '+', $value);
            if($key != "digest" && $key != "route")
            {
                $get_data .= $split . $key . "=" . $value2;
                $split = "&";
            }

        }

        $data['pikpay_processor'] = $this->config->get('payment_pikpay_processor');

        if($data['pikpay_processor'] == "pikpay")
        {
            $digest_shop   = $this->digestUpdateV1($pikpay_key, $order_number);
        }else{

            $digest_shop   = $this->digestUpdateV2($pikpay_key, $get_data);
        }


        if($digest_shop == $digest_pikpay)
        {
            if($data['pikpay_transaction_type'] == 'authorize')
            {
                $data['order_status'] = 1; //Status pending
            }else{
                $data['order_status'] = 5; //Status complete
            }

            $data['comment'] = $this->language->get('text_success_message');
            $this->model_checkout_order->addOrderHistory($data['order_id'],  $data['order_status'], $data['comment'], true, false);
            $this->response->redirect($this->url->link('checkout/success'));
        }else{
            $data['order_status'] = 10; //Status failed
            $data['comment'] = $this->language->get('text_error_message');
            $this->model_checkout_order->addOrderHistory($data['order_id'],  $data['order_status'], $data['comment'], true, false);
            $this->response->redirect($this->url->link('checkout/failure'));
        }
    }

    /**
     * Funkcija mijenja status missing order u canceled
     */
    public function fail()
    {
        $this->load->language('extension/payment/pikpay'); //File language
        $this->load->model('checkout/order');
        $order_id1 = $_REQUEST['order_number'];
        $data['order_id'] = (int)$order_id1;

        $data['order_status'] = 7; //Status canceled
        $this->model_checkout_order->addOrderHistory($data['order_id'],  $data['order_status'], true, false);
        $this->document->setTitle($this->language->get('heading_title_fail'));
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        //https://payment.demo.ba/opencart/opencart_test_pikpay1/extension/payment/pikpay/fail?language=hr&order_number=14vz_test

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
        $this->response->setOutput($this->load->view('common/pikpay_cancel_message', $data));
    }



    // Računanje digesta za slanje podataka form V1
    public function digestV1($key, $order_number, $amount, $currency)
    {
        $digest = SHA1($key.$order_number.$amount.$currency);
        return $digest;
    }

    // Računanje digesta za slanje podataka form V2
    public function digestV2($key, $order_number, $amount, $currency)
    {
        $digest = hash('sha512', $key.$order_number.$amount.$currency);
        return $digest;
    }

    // Računanje digesta za update narudžbe form V1
    public function digestUpdateV1($key, $order_number)
    {
        $digest = SHA1($key.$order_number);
        return $digest;
    }

    // Računanje digesta za update narudžbe form V2
    public function digestUpdateV2($key, $url)
    {
        $digest = hash('sha512', $key.$url);
        return $digest;
    }


}