<?php

class ControllerPaymentHumm extends Controller {
    private $error = [];

    /**
     * @return string
     */
    public function index() {
        $language_data = $this->load->language( 'payment/humm' );

        $this->document->setTitle( $this->language->get( 'heading_title' ) );

        $this->load->model( 'setting/setting' );

        if ( ( $this->request->server['REQUEST_METHOD'] == 'POST' ) && $this->validate() ) {
            $this->model_setting_setting->editSetting( 'humm', $this->request->post );

            $this->session->data['success'] = $this->language->get( 'text_success' );

            $this->response->redirect( $this->url->link( 'extension/payment', 'token=' . $this->session->data['token'] . '&type=payment', true ) );
        }

        // Error Strings
        $keys = [
            'humm_warning',
            'humm_region',
            'humm_gateway_environment',
            'humm_gateway_url',
            'humm_merchant_id',
            'humm_api_key',
        ];

        foreach ( $keys as $key ) {
            if ( isset( $this->error[ $key ] ) ) {
                $data[ 'error_' . $key ] = $this->error[ $key ];
            } else {
                $data[ 'error_' . $key ] = '';
            }
        }

        // Language Strings
        foreach ( $language_data as $key => $value ) {
            $data[ $key ] = $value;
        }

        // Breadcrumbs
        $data['breadcrumbs'] = [
            [
                'text' => $this->language->get( 'text_home' ),
                'href' => $this->url->link( 'common/dashboard', 'token=' . $this->session->data['token'], true ),
            ],
            [
                'text' => $this->language->get( 'text_extension' ),
                'href' => $this->url->link( 'extension/payment', 'token=' . $this->session->data['token'] . '&type=payment', true ),
            ],
            [
                'text' => $this->language->get( 'heading_title' ),
                'href' => $this->url->link( 'payment/humm', 'token=' . $this->session->data['token'], true ),
            ],
        ];

        // Actions / Links
        $data['action'] = $this->url->link( 'payment/humm', 'token=' . $this->session->data['token'], true );
        $data['cancel'] = $this->url->link( 'extension/payment', 'token=' . $this->session->data['token'] . '&type=payment', true );

        // Dropdown Data
        $this->load->model( 'localisation/geo_zone' );
        $this->load->model( 'localisation/order_status' );

        $data['geo_zones']            = $this->model_localisation_geo_zone->getGeoZones();
        $data['order_statuses']       = $this->model_localisation_order_status->getOrderStatuses();
        $data['regions']              = $this->getRegions();
        $data['gateway_environments'] = $this->getGatewayEnvironments();

        // Form Values
        $keys = [
            'humm_title',
            'humm_description',
            'humm_shop_name',
            'humm_region',
            'humm_gateway_environment',
            'humm_gateway_url',
            'humm_merchant_id',
            'humm_api_key',
            'humm_order_status_completed_id',
            'humm_order_status_pending_id',
            'humm_order_status_failed_id',
            'humm_geo_zone_id',
            'humm_status',
            'humm_sort_order',
        ];

        $defaults = [
            'humm_title'                     => 'Humm',
            'humm_description'               => 'Pay the easier way',
            'humm_order_status_completed_id' => 5,
            'humm_order_status_pending_id'   => 1,
            'humm_order_status_failed_id'    => 10,
        ];

        foreach ( $keys as $key ) {
            if ( isset( $this->request->post[ $key ] ) ) {
                $data[ $key ] = $this->request->post[ $key ];
            } else if ( ! $this->config->has( $key ) && isset( $defaults[ $key ] ) ) {
                $data[ $key ] = $defaults[ $key ];
            } else {
                $data[ $key ] = $this->config->get( $key );
            }
        }

        // Layout
        $data['header']      = $this->load->controller( 'common/header' );
        $data['column_left'] = $this->load->controller( 'common/column_left' );
        $data['footer']      = $this->load->controller( 'common/footer' );

        // Render Output
        if ( version_compare( VERSION, '2.2.0.0', '>=' ) ) {
            $tpl_path = 'payment/humm';
        } else {
            $tpl_path = 'payment/humm' . '.tpl';
        }
        $this->response->setOutput( $this->load->view( $tpl_path, $data ) );
    }

    /**
     * @return bool
     */
    protected function validate() {
        if ( ! $this->user->hasPermission( 'modify', 'extension/payment' ) ) { // QUESTION: "payment/humm" and "extension/payment" both works here
            $this->error['humm_warning'] = $this->language->get( 'error_permission' );
        }

        $keys = [
            'humm_title'       => 'Title',
            'humm_region'      => 'Region',
            'humm_merchant_id' => 'Merchant ID',
            'humm_api_key'     => 'API Key',
        ];

        foreach ( $keys as $key => $name ) {
            if ( ! isset( $this->request->post[ $key ] ) || empty( $this->request->post[ $key ] ) ) {
                $this->error[ $key ] = sprintf( $this->language->get( 'error_required' ), $name );
            }
        }

        if (
            $this->request->post['humm_environment'] == 'other'
            && preg_match( '@^https://@', $this->request->post['humm_gateway_url'] ) !== 1
        ) {
            $this->error['humm_gateway_url'] = $this->language->get( 'error_gateway_url_format' );
        }

        return ! $this->error;
    }

    /**
     * @return mixed[]
     */
    private function getRegions() {
        return [
            [
                'code' => 'AU',
                'name' => 'Australia',
            ],
            [
                'code' => 'NZ',
                'name' => 'New Zealand',
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    private function getGatewayEnvironments() {
        return [
            [
                'code' => 'sandbox',
                'name' => 'Sandbox',
            ],
            [
                'code' => 'live',
                'name' => 'Live',
            ],
            [
                'code' => 'other',
                'name' => 'Other',
            ],
        ];
    }
}