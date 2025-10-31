<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

class ShopifyMediaPusher
{
    public function execute(string $url, string $data, string $entity) {
        $decodedData = json_decode($data,true);
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_init
        $ch = curl_init();

        // phpcs:disable WordPress.WP.AlternativeFunctions.curl_curl_setopt
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        $result = [];

        foreach ($decodedData as $item) {
            if (file_exists($item['featured_img'])) {

                $cf = new \CURLFile($item['featured_img']);

                curl_setopt($ch, CURLOPT_POSTFIELDS, ["data" => $data, "file" => $cf, 'old_id' => $item['old_id'], 'entity' => str_replace('media_','',$entity)]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // phpcs:enable WordPress.WP.AlternativeFunctions.curl_curl_setopt
                // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_exec
                $result[] = curl_exec($ch);
            }
        }
        // phpcs:ignore WordPress.WP.AlternativeFunctions.curl_curl_close
        curl_close ($ch);

        return (string)json_encode($result);
    }

}