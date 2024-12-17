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
                <th scope="row"><label for="destination">Select Destination</label></th>
                <td>
                    <select name="destination" id="destination" required>
                        <option value="" disabled selected>-- Select an option --</option>
                        <option value="shopify">Shopify</option>
                        <option value="magento">Magento</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="export_shopify_import_key">Shopify Import Key</label>
                </th>
                <td>
                    <input id="export_shopify_import_key" name="shopify_import_key" type="text" required />
                    <p class="description" id="tagline-description" style="display: none">Please copy the <strong>Import Key</strong> from your Shopify Admin Panel > Apps > Magefan Blog > Configuration > Import Key.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="export_shopify_entities_limit">Entities Per Export Request (100 is default, try less if data is not exported)</label>
                </th>
                <td>
                    <input id="export_shopify_entities_limit" name="entities_limit" type="text" value="100" required />
                </td>
            </tr>
            <tr id="domain" style="display: none">
                <th scope="row">
                    <label for="export_domain">Store Domain</label>
                </th>
                <td>
                    <input id="export_domain" name="magento_domain" type="text"/>
                </td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" name="submit_form" value="Start Export" class="button button-primary"></td>
            </tr>
        </tbody>
    </table>
</form>


<script>
    document.getElementById('destination').addEventListener('change', function () {
        const description = document.getElementById('tagline-description');
        if (this.value === 'shopify') {
            description.style.display = 'block';
            document.getElementById('domain').style.display = 'none';
            document.getElementById('export_domain').required = false;
            description.innerHTML = 'Please copy the <strong>Import Key</strong> from your Shopify Admin Panel > Apps > Magefan Blog > Configuration > Import Key.'
        } else if (this.value === 'magento') {
            description.style.display = 'block';
            document.getElementById('domain').style.display = '';
            document.getElementById('export_domain').required = true;
            description.innerHTML = 'Please copy the <strong>Import Key</strong> from your Magento Admin Panel > Stores > Configuration > Magefan Blog > Import Key.'
        }
    });
</script>