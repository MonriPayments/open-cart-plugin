<?php
class ModelExtensionPaymentPikpay extends Model
{
    public function getMethod()
    {
        $this->load->language('extension/payment/pikpay');
        $status = true;
        $method_data = array();

        if ($status)
        {
            $method_data = array(
                'code'       => 'pikpay',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => ''
            );
        }

        return $method_data;
    }
}