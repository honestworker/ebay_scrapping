<?php

    define( 'CURRENT_DIR', dirname(__FILE__).'/' );

    require_once( CURRENT_DIR . './functions.php' );

    $db = new DB();

    function update_item_info($items, $db) {
        $ch = array();
        $items_info_result = array();
        if ($mh = curl_multi_init()) {
            foreach ($items as $item_id => $item_info) {
                if ($curl[$item_id] = curl_init()) {
                    $item_url = get_item_info_by_id_url($item_id, 2);
                    curl_setopt($curl[$item_id], CURLOPT_URL, $item_url);
                    curl_setopt($curl[$item_id], CURLOPT_HEADER, 0);
                    curl_setopt($curl[$item_id], CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl[$item_id], CURLOPT_TIMEOUT, 30);
                    
                    curl_setopt($curl[$item_id], CURLOPT_FOLLOWLOCATION, 1);
                    
                    curl_multi_add_handle($mh, $curl[$item_id]);
                }
            }
            
            $running = null;
            do {
                while (($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status != CURLM_OK) break;
                while ($info = curl_multi_info_read($mh)) {
                    $handle = $info['handle'];
                    $item_json_data = curl_multi_getcontent($handle);
                    if( $item_json_data ) {
                        $item_info_result = json_decode($item_json_data, true);
                        if (isset($item_info_result['Ack'])) {
                            if ($item_info_result['Ack'] = 'Success') {
                                if (isset($item_info_result['Item'])) {
                                    $item_info = $item_info_result['Item'];
                                    
                                    $item_id = $item_info['ItemID'];
                                    
                                    if (!$items[$item_id]['completed']) {
                                        $url = (isset($item_info['ViewItemURLForNaturalSearch'])) ? str_replace("\n", "", str_replace("'", "", $item_info['ViewItemURLForNaturalSearch'])) : "";
                                        $image = "";
                                        if (isset($item_info['GalleryURL'])) {
                                            $image = str_replace("\n", "", str_replace("'", "", $item_info['GalleryURL']));
                                        } else {
                                            if (isset($item_info['Variations'])) {
                                                if (isset($item_info['Variations']['Pictures'][0]['VariationSpecificPictureSet'])) {
                                                    $item_variations_info = $item_info['Variations']['Pictures'][0]['VariationSpecificPictureSet'];
                                                    if (isset($item_variations_info[0])) {
                                                        if (isset($item_variations_info[0]['PictureURL'])) {
                                                            if (is_array($item_variations_info[0]['PictureURL'])) {
                                                                $image = $item_variations_info[0]['PictureURL'][0];
                                                            } else {
                                                                $image = $item_variations_info[0]['PictureURL'];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        if (!$image) {
                                            if (isset($item_info['PictureURL'])) {
                                                if (is_array($item_info['PictureURL'])) {
                                                    $image = $item_info['PictureURL'][0];
                                                } else {
                                                    $image = $item_info['PictureURL'];
                                                }
                                            }
                                        }
                                        if (!$image) {
                                            $image = "https://ir.ebaystatic.com/pictures/aw/pics/nextGenVit/imgNoImg.gif";
                                        }
                                        $title =  (isset($item_info['Title'])) ? str_replace("\n", "", str_replace("'", "", $item_info['Title'])) : "";
                                        $description = (isset($item_info['Description'])) ?  str_replace("\n", "", str_replace("'", "", $item_info['Description'])) : "";
                                        $category_id = (isset($item_info['PrimaryCategoryID'])) ? $item_info['PrimaryCategoryID'] : 0;
                                        $category_name = (isset($item_info['PrimaryCategoryName'])) ? str_replace("'", "", trim($item_info['PrimaryCategoryName'])) : "";
                                        $location = (isset($item_info['Location'])) ? str_replace("\n", "", str_replace("'", "", trim($item_info['Location']))) : "";
                                        $currency = (isset($item_info['CurrentPrice']['CurrencyID'])) ? utf8_decode($item_info['CurrentPrice']['CurrencyID']) : "";
                                        $seller_feddback_score = (isset($item_info['Seller']['FeedbackScore'])) ? $item_info['Seller']['FeedbackScore'] : 0;
                                        $seller_name = (isset($item_info['Seller']['UserID'])) ?  $item_info['Seller']['UserID'] : "";
                                        $seller_country = (isset($item_info['Country'])) ? get_full_country_name_from_to_digit($item_info['Country']) : "";
                                        $listing = (isset($item_info['ListingType'])) ? trim($item_info['ListingType']) : "";
                                        $upload_date = (isset($item_info['StartTime'])) ? date("Y-m-d H:i:s", strtotime($item_info['StartTime']) + (60 * 60)) : "0000-00-00 00:00:00";
                                        $is_multi = (isset($item_info['Variations'])) ? 1 : 0;
                                    }
                                    
                                    $quantity = (isset($item_info['Quantity'])) ? str_replace("'", "", trim($item_info['Quantity'])) : 0;
                                    $total_sold = (isset($item_info['QuantitySold'])) ? trim($item_info['QuantitySold']) : 0;
                                    
                                    $price = (isset($item_info['CurrentPrice']['Value'])) ? $item_info['CurrentPrice']['Value'] : 0;
                                    $condition_id = (isset($item_info['ConditionID'])) ? $item_info['ConditionID'] : 0;
                                    $condition_name = (isset($item_info['ConditionDisplayName'])) ? str_replace("\n", "", str_replace("'", "", trim($item_info['ConditionDisplayName']))) : "";

                                    $now_date = date("Y-m-d H:i:s");
                                    $end_date = (isset($item_info['EndTime'])) ? date("Y-m-d H:i:s", strtotime($item_info['EndTime']) + (60 * 60)) : "0000-00-00 00:00:00";
                                    $item_status = 1;
                                    if ($end_date < $now_date) {
                                        $item_status = 0;
                                    }
                                    $total_completed = 1;
                                    if ($items[$item_id]['total_sold'] < $total_sold) {
                                        $total_completed = 0;
                                    }
                                    if ($items[$item_id]['completed']) {
                                        if ($total_sold) {
                                            $db->update('ds_ebay_items_test',
                                                    array(
                                                        'info_checked' => 1,
                                                        'recommend_info' => 0,
                                                        'quantity' => $quantity,
                                                        'total_sold' => $total_sold,
                                                        'price' => $price,
                                                        'condition_id' => $condition_id,
                                                        'condition_name' => $condition_name,
                                                        'info_update' => $now_date,
                                                        'info_completed' => 1,
                                                        'item_status' => $item_status,                                                        
                                                        'transaction_completed' => $total_completed
                                                    ), array('item_id' => $item_id));
                                        } else {
                                            $db->update('ds_ebay_items_test',
                                                    array(
                                                        'info_checked' => 1,
                                                        'recommend_info' => 0,
                                                        'quantity' => $quantity,
                                                        'total_sold' => $total_sold,
                                                        'price' => $price,
                                                        'condition_id' => $condition_id,
                                                        'condition_name' => $condition_name,
                                                        'info_update' => $now_date,
                                                        'info_completed' => 1,
                                                        'recommend_transaction' => 0,
                                                        'transaction_update' => $now_date,
                                                        'transaction_completed' => 1,
                                                        'item_status' => $item_status
                                                    ), array('item_id' => $item_id));
                                        }
                                    } else {
                                        if ($total_sold) {
                                            $db->update('ds_ebay_items_test',
                                                array(
                                                    'info_checked' => 1,
                                                    'recommend_info' => 0,
                                                    'url' => $url,
                                                    'image' => $image,
                                                    'title' => $title,
                                                    'description' => $description,
                                                    'category_id' => $category_id,
                                                    'category_name' => $category_name,
                                                    'quantity' => $quantity,
                                                    'location' => $location,
                                                    'total_sold' => $total_sold,
                                                    'currency' => $currency,
                                                    'seller_name' => $seller_name,
                                                    'seller_country' => $seller_country,
                                                    'seller_feedback' => $seller_feddback_score,
                                                    'price' => $price,
                                                    'dirty_price' => $price,
                                                    'listing' => $listing,
                                                    'condition_id' => $condition_id,
                                                    'condition_name' => $condition_name,
                                                    'upload_date' => $upload_date,
                                                    'end_date' => $end_date,
                                                    'is_multi' => $is_multi,
                                                    'info_update' => $now_date,
                                                    'info_completed' => 1,
                                                    'item_status' => $item_status,
                                                    'transaction_completed' => $total_completed
                                                ), array('item_id' => $item_id));

                                        } else {                    
                                            $db->update('ds_ebay_items_test',
                                                array(
                                                    'info_checked' => 1,
                                                    'recommend_info' => 0,
                                                    'url' => $url,
                                                    'image' => $image,
                                                    'title' => $title,
                                                    'description' => $description,
                                                    'category_id' => $category_id,
                                                    'category_name' => $category_name,
                                                    'quantity' => $quantity,
                                                    'location' => $location,
                                                    'total_sold' => $total_sold,
                                                    'currency' => $currency,
                                                    'seller_name' => $seller_name,
                                                    'seller_country' => $seller_country,
                                                    'seller_feedback' => $seller_feddback_score,
                                                    'dirty_price' => $price,
                                                    'price' => $price,
                                                    'listing' => $listing,
                                                    'condition_id' => $condition_id,
                                                    'condition_name' => $condition_name,
                                                    'upload_date' => $upload_date,
                                                    'end_date' => $end_date,
                                                    'is_multi' => $is_multi,
                                                    'info_update' => $now_date,
                                                    'info_completed' => 1,
                                                    'recommend_transaction' => 0,
                                                    'transaction_update' => $now_date,
                                                    'transaction_completed' => 1,
                                                    'item_status' => $item_status
                                                ), array('item_id' => $item_id));
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($running && curl_multi_select($mh) === -1) usleep(EBAY_CURL_MULTI_SLEEP);
                    curl_multi_remove_handle($mh, $handle);
                    curl_close($handle);
                }
            } while($running);
            
            curl_multi_close($mh);
        }
    }

    $info_call_count = 0;
    do {
        $continue_flag = 1;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE seller_id != 0 AND info_completed = 0 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 ORDER BY recommend_info DESC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('info_checked' => 0), array('item_id' => $item['item_id']));
                        $items[$item['item_id']] = array('completed' => $item['info_completed'], 'total_sold' => $item['total_sold']);
                    }
                    update_item_info($items, $db);
                    $info_call_count = $info_call_count + count($results);
                    if ($info_call_count > EBAY_ITEM_INFO_ONE_TIME_COUNT) {
                        return;
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);


    $info_call_count = 0;
    do {
        $continue_flag = 1;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE info_completed = 0 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 ORDER BY recommend_info DESC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('info_checked' => 0), array('item_id' => $item['item_id']));
                        $items[$item['item_id']] = array('completed' => $item['info_completed'], 'total_sold' => $item['total_sold']);
                    }
                    update_item_info($items, $db);
                    $info_call_count = $info_call_count + count($results);
                    if ($info_call_count > EBAY_ITEM_INFO_ONE_TIME_COUNT) {
                        return;
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);

    do {
        $continue_flag = 1;
        $now_date = date("Y-m-d H:i:s");
        $date_before = date("Y-m-d H:i:s", strtotime("$now_date  -1 day"));
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE info_update < '{$date_before}' AND recommend_info > 0 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 ORDER BY recommend_info DESC, info_update ASC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('info_checked' => 0), array('item_id' => $item['item_id']));
                        $items[$item['item_id']] = array('completed' => $item['info_completed'], 'total_sold' => $item['total_sold']);
                    }
                    update_item_info($items, $db);
                    $info_call_count = $info_call_count + count($results);
                    if ($info_call_count > EBAY_ITEM_INFO_ONE_TIME_COUNT) {
                        return;
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);

    do {
        $continue_flag = 1;
        $now_date = date("Y-m-d H:i:s");
        $date_before = date("Y-m-d H:i:s", strtotime("$now_date  -1 day"));
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE info_update < '{$date_before}' AND recommend_info = 0 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 ORDER BY info_update ASC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('info_checked' => 0), array('item_id' => $item['item_id']));
                        $items[$item['item_id']] = array('completed' => $item['info_completed'], 'total_sold' => $item['total_sold']);
                    }
                    update_item_info($items, $db);
                    $info_call_count = $info_call_count + count($results);
                    if ($info_call_count > EBAY_ITEM_INFO_ONE_TIME_COUNT) {
                        return;
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);
?>