<?php

/* Uncomment this comment block only in the case of catastrophic failure.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

class ControllerExtensionPaymentMonri extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/monri');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('payment_monri', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }


        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
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
            'href' => $this->url->link('extension/payment/monri', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/monri', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        // Test
        if (isset($this->request->post['payment_monri_test'])) {
            $data['payment_monri_test'] = $this->request->post['payment_monri_test'];
        } else {
            $data['payment_monri_test'] = $this->config->get('payment_monri_test');
        }

        $data['payment_monri_test'] = true;

        // Status
        if (isset($this->request->post['payment_monri_status'])) {
            $data['payment_monri_status'] = $this->request->post['payment_monri_status'];
        } else {
            $data['payment_monri_status'] = $this->config->get('payment_monri_status');
        }

        // Monri Key
        if (isset($this->request->post['payment_monri_key'])) {
            $data['payment_monri_key'] = $this->request->post['payment_monri_key'];
        } else {
            $data['payment_monri_key'] = $this->config->get('payment_monri_key');
        }

        // Error poruka monri key
        if (isset($this->error['payment_monri_key'])) {
            $data['error_payment_monri_key'] = $this->error['payment_monri_key'];
        } else {
            $data['error_payment_monri_key'] = '';
        }

        // Monri secret key
        if (isset($this->request->post['payment_monri_secret_key'])) {
            $data['payment_monri_secret_key'] = $this->request->post['payment_monri_secret_key'];
        } else {
            $data['payment_monri_secret_key'] = $this->config->get('payment_monri_secret_key');
        }

        // Error poruka secret key
        if (isset($this->error['secret_key'])) {
            $data['error_secret_key'] = $this->error['secret_key'];
        } else {
            $data['error_secret_key'] = '';
        }

        // Monri transaction type
        if (isset($this->request->post['payment_monri_transaction_type'])) {
            $data['payment_monri_transaction_type'] = $this->request->post['payment_monri_transaction_type'];
        } else {
            $data['payment_monri_transaction_type'] = $this->config->get('payment_monri_transaction_type');
        }

        // Odabir jezika
        if (isset($this->request->post['payment_monri_language'])) {
            $data['payment_monri_language'] = $this->request->post['payment_monri_language'];
        } else {
            $data['payment_monri_language'] = $this->config->get('payment_monri_language');
        }

        //
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        {
            $protocol = 'https://';
        }
        else {
            $protocol = 'http://';
        }

        $dirname = dirname(dirname(dirname(dirname($_SERVER['REQUEST_URI']))));

        $data['success_url'] = $protocol . $_SERVER['SERVER_NAME'] . $dirname . "/extension/payment/monri/success"; //$protocol . $domain_name;
        $data['fail_url']    = $protocol . $_SERVER['SERVER_NAME'] . $dirname . "/extension/payment/monri/fail"; //$protocol . $domain_name;

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/monri', $data));
    }

    // Validacija polja
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/monri')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        // Monri Key
        if (!$this->request->post['payment_monri_key']) {
            $this->error['payment_monri_key'] = $this->language->get('error_payment_monri_key');
        }

        // Monri secret key
        if (!$this->request->post['payment_monri_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        return !$this->error;
    }

    public function apiRequest()
    {
        // All the necessary page elements
        $this->load->language('extension/payment/monri');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $data['test_mode']                = $this->config->get('payment_monri_test'); //Podaci iz administracije
        $data['monri_key']               = $this->config->get('payment_monri_key');
        $data['monri_secret_key']        = $this->config->get('payment_monri_secret_key');
        $data['monri_processing_method'] = $this->config->get('payment_monri_processing_method');

        if($data['test_mode'])
        {
            $data['liveurl'] = 'https://ipgtest.monri.com/transactions/';
        }
        else{
            $data['liveurl'] = 'https://ipg.monri.com/transactions/';
        }

        // Podaci ordera
        $order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);

        $data["order_number"] = $this->request->get['order_id'];
        $data["amount"]       = $order_info["total"]*100;
        $data["currency"]     = $order_info["currency_code"];
        $data["authenticity_token"] = $this->config->get('payment_monri_secret_key');

        $monri_key = $this->config->get('payment_monri_key');
        $data["digest"] = $this->digest($monri_key, $data["order_number"], $data['amount'], $data["currency"]);

        $xml = $this->generateXml($data);
        $type = $_REQUEST['type'];
        if($type == 'capture')
        {
            $url = $data['liveurl'] . $data["order_number"] . '/capture.xml';
        }
        elseif($type == 'void')
        {
            $url = $data['liveurl'] . $data["order_number"] . '/void.xml';
        }
        else{
            $url = $data['liveurl'] . $data["order_number"] . '/refund.xml';
        }

        $sendData = $this->sendXml($url, $xml);

        // Provjera da li vrat xml ili poruku
        $check = @simplexml_load_string($sendData);
        if ($check)
        {
            $xml_data = new \SimpleXmlElement($sendData);
            $json = json_encode($xml_data);
            $data['api_answer']  = json_decode($json,TRUE);
            $data['api_answer_order']  = "Order number: <b>" . $data['api_answer']["order-number"] . "</b>";
            $amoount_total = $data['api_answer']["amount"]/100;
            $data['api_answer_amount'] = "Amount: <b>" . number_format($amoount_total, 2) . "</b>";
            $data['api_answer_card']   = "Card: <b>" . $data['api_answer']["cc-type"] . "</b>";
            $data['api_answer_response_message'] = "Response message: <b>" . $data['api_answer']["response-message"] . "</b>";
            $data['api_answer_response_status']  = "Response status: <b>" . $data['api_answer']["status"] . "</b>";
            $data['api_answer_transaction_type'] = "Transaction status: <b>" . $data['api_answer']["transaction-type"] . "</b>";

            $comment =  $data['api_answer_response_message'] . "\n" . $data['api_answer_transaction_type'];
            $notify = 0;
            if($type == 'capture')
            {
                $order_status_id = 5;
            }
            elseif($type == 'void')
            {
                $order_status_id = 7;
            }
            else{
                $order_status_id = 11;
            }

            $this->addOrderHistory($data["order_number"], $order_status_id, $comment, $notify);
            $this->updateOrderStatus($data["order_number"], $order_status_id);

        } else {
            $data['error']= "Message: " . $sendData;
        }

        $data['link_orders_monri'] = $this->url->link('sale/order_monri', 'user_token=' . $this->session->data['user_token'], true);

        // Load the template file and show output
        $this->response->setOutput($this->load->view('extension/payment/monri_xml_request', $data));
    }

    // Računanje digesta za slanje podataka
    public function digest($key, $order_number, $amount, $currency)
    {
        $digest = SHA1($key.$order_number.$amount.$currency);
        return $digest;
    }

    /**
     * Generates XML string for purchase and authorize requests
     *
     * @param string purchase or authorize
     * @return string generated xml
     */
    public function generateXml($data)
    {
        $xml = "<?xml version='1.0' encoding='UTF-8'?>
                <transaction>
                    <amount>{$data['amount']}</amount>
                    <currency>{$data['currency']}</currency>
                    <digest>{$data['digest']}</digest>
                    <authenticity-token>{$data['authenticity_token']}</authenticity-token>
                    <order-number>{$data['order_number']}</order-number>
                </transaction>";

        return $xml;
    }


    public function sendXml($url, $xml)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // For xml, change the content-type.
        curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned

        $result = curl_exec($ch);

        if ($result === FALSE)
        {
            die('cURL error: '.curl_error($ch)."<br />\n");
        }

        curl_close($ch);
        return $result;
    }

    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false, $override = false)
    {
        $add_history = $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");
        return $add_history;
    }

    public function updateOrderStatus($order_id, $order_status_id)
    {
        $update_order_status = $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
        return $update_order_status;
    }

}
