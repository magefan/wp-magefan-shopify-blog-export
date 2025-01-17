<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

class ShopifyMediaPusher
{
    /*public function execute(string $url, string $data, string $entity) {
        $decodedData = json_decode($data,true);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        $result = [];

        foreach ($decodedData as $item) {
            if (file_exists($item['featured_img'])) {

                $cf = new \CURLFile($item['featured_img']);

                curl_setopt($ch, CURLOPT_POSTFIELDS, ["data" => $data, "file" => $cf, 'old_id' => $item['old_id'], 'entity' => str_replace('media_','',$entity)]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $result[] = curl_exec ($ch);
            }
        }

        curl_close ($ch);

        return (string)json_encode($result);
    }*/


    public function execute(string $url, string $data, string $entity) {
        $decodedData = json_decode($data, true);
        $result = [];

        foreach ($decodedData as $item) {
            if (file_exists($item['featured_img'])) {
                $file = new \CURLFile($item['featured_img']);

                $postData = [
                    "data"   => $data,
                    "file"   => $file,
                    'old_id' => $item['old_id'],
                    'entity' => str_replace('media_', '', $entity),
                ];

                $response = wp_remote_post($url, [
                    'method'    => 'POST',
                    'body'      => $postData,
                    'timeout'   => 45,
                    'headers'   => [
                        'Content-Type' => 'multipart/form-data',
                    ],
                ]);

                if (is_wp_error($response)) {
                    $result[] = 'Error: ' . $response->get_error_message();
                } else {
                    $result[] = wp_remote_retrieve_body($response);
                }
            }
        }

        return wp_json_encode($result);
    }

}