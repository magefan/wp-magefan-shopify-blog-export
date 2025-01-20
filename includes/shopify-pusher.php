<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

class ShopifyPusher
{
    protected $curl;

    public function execute(string $url, string $data, string $entity) {
        $args = [
            'body' => [
                'data' => $data,
                'entity' => $entity,
            ],
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return wp_json_encode([
                'errorMessage' => $response->get_error_message(),
            ]);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== $response_code) {
            return wp_json_encode([
                'errorMessage' => 'Wrong Import Key',
            ]);
        }

        $response_body = wp_remote_retrieve_body($response);

        return $response_body;
    }

}