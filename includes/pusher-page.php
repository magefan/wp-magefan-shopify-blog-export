<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
?>

<style>
    #myProgress {
        width: 500px;
        background-color: grey;
    }

    #myBar {
        width: 0%;
        height: 30px;
        background-color: green;
    }
</style>

<div id="myProgress">
    <div id="myBar"></div>
</div>

<?php

function getExporterKey()
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';

    for ($i = 0; $i < 20; $i++) {
        $string .= $characters[wp_rand(0, strlen($characters) - 1)];
    }

    return $string;
}

?>

<script>
    jQuery(document).ready(function() {
        alert('Don\'t leave the page !');

        var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
        var pushDataToShopify = ajaxurl;
        var shopifyUrl = '<?php echo esc_url( 'https://blog.sfapp.magefan.top/blog/import' ); ?>';
        var importKey = '<?php echo esc_js((sanitize_text_field($_POST['shopify_import_key']) ?? '')) ?>';
        var entitiesLimit = '<?php echo esc_js((sanitize_text_field($_POST['entities_limit']) ?? '')) ?>';
        var exporterKey = '<?php echo esc_js(getExporterKey()); ?>';
        var closedConnection = false;
        var indexPageUrl = '<?php echo esc_url(admin_url('admin.php?page=magefan-shopify-blog-export-form')); ?>';

        var setGetParameters = function (urlStr, getParameters) {
            var url = new URL(urlStr);
            var search_params = url.searchParams;
            for (var key in getParameters) {
                search_params.set(key, getParameters[key]);
            }
            url.search = search_params.toString();
            return url.toString();
        };

        const entities = {1: "category", 2: "tag", 3: "author", 4: "post", 5: 'comment', 6: "media_post", 7: "media_author"};

        var entityIndex = 1;
        var entityIds = {};
        var entityIdsMax = 0;

        var extractEntityIdsPromises = [];
        for (let key in entities) {
            var entityIdsExtractor = setGetParameters(ajaxurl, {'entity': entities[key], 'allIds': true });
            var extractEntityIdsPromise = jQuery.ajax({
                url: entityIdsExtractor,
                type: 'GET',
                data: {
                    action: 'magefan_shopifyblogexport_data_extractor',
                },
                success: function (response) {
                    var data = response.data;

                    if (0 != data.length) {
                        data['entity'] = entities[key];
                        entityIds[entities[key]] = data;
                        entityIdsMax += data.length;
                    }
                },
                error: function() {
                    console.log('error yoy');
                }
            });

            extractEntityIdsPromises.push(extractEntityIdsPromise);
        }

        jQuery.when.apply(null, extractEntityIdsPromises).done(function(){
            if(entityIdsMax) {
                var offset = 1;
                var step = (100 / entityIdsMax) * 100;
                var width = step;
                var maxWidth = step*entityIdsMax;

                ajaxurl = setGetParameters(ajaxurl, {
                    'entity': entities[entityIndex],
                    'offset': offset,
                    'entitiesLimit': entitiesLimit
                });

                var runRequests = function() {

                    if (!(entityIndex in entities)) {
                        console.log("runRequests Success");
                        if (false === closedConnection) {
                            var data = {
                                0: {
                                    exporterKey: exporterKey,
                                    importKey: importKey,
                                    closeConnection: true
                                }
                            };

                            jQuery.ajax({
                                url: pushDataToShopify,
                                type: 'POST',
                                data: {
                                    'data': JSON.stringify(data),
                                    'shopifyUrl': shopifyUrl,
                                    'entity': 'closeConnection',
                                    'action': 'magefan_shopifyblogexport_push_data_to_shopify'
                                },
                                dataType: 'json',
                                success: function (response) {
                                    const jsonResponse = JSON.parse(response.data);
                                    if (jsonResponse.errorMessage) {
                                        alert(jsonResponse.errorMessage);
                                        window.location.href = indexPageUrl;
                                    }

                                    closedConnection = true;
                                    alert('All data was succefully exported');
                                    window.location.href = indexPageUrl;
                                },
                                error: function () {
                                    alert('That was some error while pushing data');
                                    window.location.href = indexPageUrl;
                                }
                            });
                        }
                        return;
                    }

                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'GET',
                        data: {
                            action: 'magefan_shopifyblogexport_data_extractor',
                        },
                        success: function (response) {
                            var data = response.data;

                            if (0 == data.length) {
                                entityIndex += 1;
                                offset = 1;
                                ajaxurl = setGetParameters(ajaxurl, {'entity': entities[entityIndex], 'offset': offset});
                                runRequests();
                            } else {
                                if (data.postMissImg) {
                                    offset += 1;
                                    ajaxurl = setGetParameters(ajaxurl, {'offset': offset});
                                    runRequests();
                                }

                                data[0]['exporterKey'] = exporterKey;
                                data[0]['importKey'] = importKey;

                                jQuery.ajax({
                                    url: pushDataToShopify,
                                    type: 'POST',
                                    data: {
                                        'data': JSON.stringify(data),
                                        'shopifyUrl': shopifyUrl,
                                        'entity': entities[entityIndex],
                                        'action': 'magefan_shopifyblogexport_push_data_to_shopify'
                                    },
                                    dataType: 'json',
                                    success: function (response) {
                                        const jsonResponse = JSON.parse(response.data);
                                        if (jsonResponse.errorMessage) {
                                            alert(jsonResponse.errorMessage);
                                            window.location.href = indexPageUrl;
                                        }

                                        if (maxWidth >= width) {
                                            document.getElementById("myBar").style.width = width + "%";
                                            width += step;
                                        }
                                        offset += 1;
                                        ajaxurl = setGetParameters(ajaxurl, {'offset': offset});
                                        runRequests();
                                    },
                                    error: function () {
                                        if (entities[entityIndex] == 'media_post')
                                        {
                                            if (maxWidth >= width) {
                                                document.getElementById("myBar").style.width = width + "%";
                                                width += step;
                                            }
                                            offset += 1;
                                            ajaxurl = setGetParameters(ajaxurl, {'offset': offset});
                                            runRequests()
                                        }
                                        else {
                                            alert('That was some error while pushing data.');
                                            window.location.href = indexPageUrl;
                                        }
                                    }
                                });
                            }
                        },
                        error: function() {
                            alert('That was some error while pushing data');
                            window.location.href = indexPageUrl;
                        },
                    });
                };
                runRequests();
            }
        });
    });
</script>