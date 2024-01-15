<!--
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
-->

<h1>Export to Shopify Blog by Magefan</h1>
<form method="post" action="<?php echo admin_url('admin.php?page=mf-push-page'); ?>">
    <!-- Your HTML form fields go here -->
    <input type="hidden" name="action" value="mf_handle_form_submission">

    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="export_shopify_import_key">Shopify Import Key</label>
                </th>
                <td>
                    <input id="export_shopify_import_key" name="shopify_import_key" type="text" required />
                    <p class="description" id="tagline-description">Please copy the <strong>Import Key</strong> from your Shopify Admin Panel > Apps > Magefan Blog > Configuration > Import Key.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="export_shopify_domain">Shopify Store Name</label>
                </th>
                <td>
                    <input id="export_shopify_domain" name="shopify_domain" type="text"  required />
                    <p class="description" id="tagline-description">Please enter your shop name. E.g. : "<strong>magefan-dev.myshopify.com</strong>" if your admin url is <strong>https://admin.shopify.com/store/magefan-dev</strong>”</p>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="submit_form" value="Start Export" class="button button-primary"></td>
            </tr>
        </tbody>
    </table>
</form>