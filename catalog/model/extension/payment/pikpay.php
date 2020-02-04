<?php
class ModelExtensionPaymentMonri extends Model
{
    public function getMethod()
    {
        $this->load->language('extension/payment/monri');
        $status = true;
        $method_data = array();

        if ($status)
        {
            $method_data = array(
                'code'       => 'monri',
                'title'      => $this->language->get('text_title'),
                'terms'      => '',
                'sort_order' => ''
            );
        }

        return $method_data;
    }
}