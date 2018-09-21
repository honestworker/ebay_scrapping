<?php

class Scrapper {
    private $db;
	
    function __construct() {
        $this->db = new DB();
    }

    private function get_api_call_app_no($name) {
        if ($row = $this->db->get_row("SELECT app_no, call_count FROM ds_ebay_api_call WHERE name = '{$name}'")) {
            $app_no = (int)$row[0];
            $call_count = (int)$row[1] + 1;
            if ($call_count >= LIMIT_API_CALL_COUNT) {
                $app_no = $app_no + 1;
                $api_count = API_APP1_COUNT;
                if ($name == 'GetItemTransactions') {
                    $api_count = API_APP2_COUNT;
                }
                if ($app_no > $api_count) {
                    $app_no = 1; 
                }
                $call_count = 0;
            }
            
            $this->db->update('ds_ebay_api_call',
                array(
                    'app_no' => $app_no,
                    'call_count' => $call_count,
                ), array('name' => $name));
                
            return $app_no;
        }
        return 0;
    }

    private function get_items_copies_url($items) {
        if (!$items) { return; }
        
        $conn = [];
        if ($mh = curl_multi_init()) {
            foreach ($items as $item_id) {
                if ($row = $this->db->get_row("SELECT copies_url FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                    $item_copies_url = $row[0];
                    if ($curl = curl_init()) {
                        curl_setopt($curl, CURLOPT_URL, $item_copies_url);
                        curl_setopt($curl, CURLOPT_HEADER, 0);
                        curl_setopt($curl, CURLOPT_TIMEOUT, 3000);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                        
                        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                        
                        curl_multi_add_handle($mh, $curl);
                        $conn[$item_id] = $curl;
                    }
                }
            }
            
            $running = null;
            do {
                $mrc = curl_multi_exec($mh, $running);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM || $running);
            
            while ($running && $mrc == CURLM_OK) {
                if (curl_multi_select($mh) != -1) {
                    do {
                        $mrc = curl_multi_exec($mh, $running);
                    } while ($mrc == CURLM_CALL_MULTI_PERFORM);
                }
            }
            
            foreach ($items as $item_id) {
                $resp = curl_multi_getcontent($conn[$item_id]);
                $dom = str_get_html($resp);
                $is_lowest = $copies_count = 0;
                if ( !(empty($dom) || !is_object($dom)) ) {
                    if (preg_match('#class="rcnt"(.*?)</span>#', $resp, $match)) {
                        $copies_count = (int)trim(str_replace([',', '>'], '', $match[1]));
                    }
                    if ($copies_count) {
                        if ($els = $dom->find('.lvresult')) {
                            $prices = array();
                            $current_price = -1;
                            $item_find_count = 0;
                            foreach ($els as $el) {
                                if ($priceEl = $el->find('.lvprice', 0)) {
                                    $price = trim($priceEl->plaintext);
                                    if (preg_match('#([\d\.\,]+)#', $price, $match)) {
                                        $prices[] = (float)str_replace(',', '', $match[1]);
                                        if (strpos($el . '', $item_id) !== false) {
                                            $current_price = (float)$match[1];
                                        }
                                    }
                                }
                                if ($elLink = $el->find('.lvtitle a', 0)) {
                                    $competitor_item_url = $elLink->href;
                                    $competitor_item_id = 0;
                                    if (preg_match('#\/([\d]+)\?#', $competitor_item_url, $match)) {
                                        $competitor_item_id = $match[1];
                                    }
                                    if (!$competitor_item_id) {
                                        if (preg_match("/\/([\d]+)$/", $competitor_item_url, $match)) {
                                            $competitor_item_id = $match[1];
                                        }
                                    }
                                    if ($competitor_item_id && $competitor_item_id > 1000) {
                                        $item_find_count = $item_find_count + 1;
                                    }
                                    if ($item_find_count == $copies_count) {
                                        break;
                                    }
                                }
                            }
                            if (count($prices)) {
                                if ($current_price == min($prices)){
                                    $is_lowest = 1;
                                }
                            }
                        }
                    }
                    
                    $now_date = date("Y-m-d H:i:s");
                    $this->db->update('ds_ebay_items',
                        array(
                            'copies_checked' => 1,
                            'copies_completed' => 1,
                            'copies_update' => $now_date,
                            'is_lowest' => $is_lowest,
                            'copies_count' => $copies_count
                        ), array('item_id' => $item_id));
                } else {
                    $this->db->update('ds_ebay_items',
                        array(
                            'copies_checked' => 1,
                        ), array('item_id' => $item_id));
                }
                curl_multi_remove_handle($mh, $conn[$item_id]);
            }
            curl_multi_close($mh);
        }
    }

    private function get_items_copies($items) {
        if (!$items) { return; }
        
        $item_count = 0;
        $curl_items = null;
        foreach ($items as $item_id) {
            $item_count = $item_count + 1;
            $curl_items[] = $item_id;
            if ($item_count == EBAY_COPIES_ITEM_ONE_TIME) {
                $this->get_items_copies_url($curl_items);
                $item_count = 0;
                $curl_items = null;
            }
        }
        if ($item_count) {            
            $items_result = $this->get_items_copies_url($curl_items);
        }
    }

    private function get_items_info_curl($items) {
        if (!$items) { return null; }
        
        $result = array(
            'trans' => null,
            'sellers' => null,
            'seller_country' => null,
        );
        if ($mh = curl_multi_init()) {
            foreach ($items as $item_id => $item_infos) {
                if ($curl[$item_id] = curl_init()) {
                    $item_url = get_item_info_url_by_id($item_id, $this->get_api_call_app_no("GetSingleItem"));
                    curl_setopt($curl[$item_id], CURLOPT_URL, $item_url);
                    curl_setopt($curl[$item_id], CURLOPT_HEADER, 0);
                    curl_setopt($curl[$item_id], CURLOPT_TIMEOUT, 30);
                    curl_setopt($curl[$item_id], CURLOPT_RETURNTRANSFER, 1);
                    
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
                                    
                                    //$image = $title = $description = $category_name = $location = $currency = $seller_name = $item_country = $listing = $upload_date = $end_date = $condition_name = "";
                                    $image = $title = $category_name = $location = $currency = $seller_name = $item_country = $listing = $upload_date = $end_date = $condition_name = "";
                                    $category_id = $seller_feddback_score = $is_multi = $is_subtitle = $quantity = $total_sold = $price = $condition_id = 0;
                                    
                                    if (!$items[$item_id]['completed']) {
                                        $url = (isset($item_info['ViewItemURLForNaturalSearch'])) ? str_replace("\n", "", str_replace("'", "", $item_info['ViewItemURLForNaturalSearch'])) : "";
                                        if (isset($item_info['PictureURL'])) {
                                            if (is_array($item_info['PictureURL']) && count($item_info['PictureURL'])) {
                                                $image = $item_info['PictureURL'][0];
                                            } else {
                                                $image = $item_info['PictureURL'];
                                            }
                                        }
                                        if (!$image) {
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
                                        }
                                        if (!$image) {
                                            $image = "https://ir.ebaystatic.com/pictures/aw/pics/nextGenVit/imgNoImg.gif";
                                        }
                                        $title =  (isset($item_info['Title'])) ? str_replace("\n", "", str_replace("'", "", $item_info['Title'])) : "";
                                        
                                        //$description_str = (isset($item_info['Description'])) ?  $item_info['Description'] : "";
                                        //$description = "";
                                        /*
                                            <text> .body_container </text>
                                            2 3  4
                                                  1
                                                   0
                                        */
                                        // if ($description_str) {
                                        //     $str_flag = $style_flag = 0;
                                        //     $sub_str = "";
                                        //     for ($str_no = 0; $str_no < strlen($description_str); $str_no++) {
                                        //         if ($description_str[$str_no] == '<') {
                                        //             $sub_str = "";
                                        //             $str_flag = 2;
                                        //         } else if ($description_str[$str_no] == '>') {
                                        //             if ($sub_str == '/style') {
                                        //                 $style_flag = 0;
                                        //             }
                                        //             $sub_str = "";
                                        //             $str_flag = 4;
                                        //         } else {
                                        //             if ($str_flag == 2) {
                                        //                 $str_flag = 3;
                                        //             } else if ($str_flag == 4) {
                                        //                 $str_flag = 1;
                                        //             } else if ($str_flag == 1) {
                                        //                 $str_flag = 0;
                                        //             }
                                        //         }
                                        //         if ($str_flag == 3) {
                                        //             $sub_str = $sub_str . $description_str[$str_no];
                                        //             if ($sub_str == 'style') {
                                        //                 $style_flag = 1;
                                        //             }
                                        //         }
                                        //         if ($style_flag == 0) {
                                        //             if ($str_flag == 0) {
                                        //                 $description = $description . $description_str[$str_no];
                                        //             } else if ($str_flag == 1) {
                                        //                 $description = $description . ' ' . $description_str[$str_no];
                                        //             }
                                        //         }
                                        //     }
                                        // }
                                        // $description = str_replace(array("\t", "\r", "\n", "'"), " ", $description);
                                        // $description = preg_replace('/\s{2,}/', ' ', $description);
                                        // $description = trim($description);
                                        
                                        $category_id = (isset($item_info['PrimaryCategoryID'])) ? $item_info['PrimaryCategoryID'] : 0;
                                        $category_name = (isset($item_info['PrimaryCategoryName'])) ? str_replace("'", "", trim($item_info['PrimaryCategoryName'])) : "";
                                        $location = (isset($item_info['Location'])) ? str_replace("\n", "", str_replace("'", "", trim($item_info['Location']))) : "";
                                        $currency = (isset($item_info['CurrentPrice']['CurrencyID'])) ? utf8_decode($item_info['CurrentPrice']['CurrencyID']) : "";
                                        $seller_feddback_score = (isset($item_info['Seller']['FeedbackScore'])) ? $item_info['Seller']['FeedbackScore'] : 0;
                                        $seller_name = (isset($item_info['Seller']['UserID'])) ?  $item_info['Seller']['UserID'] : "";
                                        $listing = (isset($item_info['ListingType'])) ? trim($item_info['ListingType']) : "";
                                        $upload_date = (isset($item_info['StartTime'])) ? date("Y-m-d H:i:s", strtotime($item_info['StartTime']) + (60 * 60)) : "0000-00-00 00:00:00";
                                        $is_multi = (isset($item_info['Variations'])) ? 1 : 0;
                                        $is_subtitle = (isset($item_info['Subtitle'])) ? 1 : 0;
                                        $item_country = (isset($item_info['Country'])) ? $item_info['Country'] : "";
                                        if ($item_country == "GB") {
                                            $url = str_replace("ebay.com", "ebay.co.uk", $url);
                                        }
                                    }
                                    $quantity = (isset($item_info['Quantity'])) ? str_replace("'", "", trim($item_info['Quantity'])) : 0;
                                    $total_sold = (isset($item_info['QuantitySold'])) ? trim($item_info['QuantitySold']) : 0;
                                    
                                    $price = (isset($item_info['CurrentPrice']['Value'])) ? $item_info['CurrentPrice']['Value'] : 0;
                                    $condition_id = (isset($item_info['ConditionID'])) ? $item_info['ConditionID'] : 0;
                                    $condition_name = (isset($item_info['ConditionDisplayName'])) ? str_replace("\n", "", str_replace("'", "", trim($item_info['ConditionDisplayName']))) : "";
                                    
                                    $now_date = date("Y-m-d H:i:s");
                                    $end_date = (isset($item_info['EndTime'])) ? date("Y-m-d H:i:s", strtotime($item_info['EndTime']) + (60 * 60)) : "0000-00-00 00:00:00";
                                    $status = 0;
                                    if ($end_date >= $now_date) {
                                        $status = 1;
                                    }
                                    
                                    $trans_completed = 1;
                                    if ($total_sold) {
                                        if ($total_sold > $items[$item_id]['total_sold']) {
                                            $trans_completed = 0;
                                            if ($status == 1) {
                                                // $period = (int)((strtotime($now_date) - strtotime($items[$item_id]['trans_update'])  + (24 * 60 * 60) - 1) / (24 * 60 * 60));
                                                // $period = ($period > 30) ? 30 : $period;
                                                //$result[] = array('item_id' => $item_id, 'period' => $period);
                                                $result['trans'][] = $item_id;
                                            }
                                        }
                                    }
                                    if ($items[$item_id]['completed'] === 1) {
                                        if ($trans_completed === 1) {
                                            $this->db->update('ds_ebay_items',
                                                    array(
                                                        'info_checked' => 1,
                                                        'quantity' => $quantity,
                                                        'total_sold' => $total_sold,
                                                        'price' => $price,
                                                        'condition_id' => $condition_id,
                                                        'condition_name' => $condition_name,
                                                        'info_update' => $now_date,
                                                        'info_completed' => 1,
                                                        'trans_checked' => 1,
                                                        'trans_update' => $now_date,
                                                        'trans_completed' => 1,
                                                        'item_status' => $status
                                                    ), array('item_id' => $item_id));
                                        } else {
                                            $this->db->update('ds_ebay_items',
                                                    array(
                                                        'info_checked' => 1,
                                                        'quantity' => $quantity,
                                                        'total_sold' => $total_sold,
                                                        'price' => $price,
                                                        'condition_id' => $condition_id,
                                                        'condition_name' => $condition_name,
                                                        'info_update' => $now_date,
                                                        'info_completed' => 1,
                                                        'trans_checked' => 1,
                                                        'item_status' => $status
                                                    ), array('item_id' => $item_id));
                                        }
                                    } else {
                                        if ($trans_completed === 1) {
                                            $this->db->update('ds_ebay_items',
                                                array(
                                                    'info_checked' => 1,
                                                    'url' => $url,
                                                    'image' => $image,
                                                    'title' => $title,
                                                    //'description' => $description,
                                                    'category_id' => $category_id,
                                                    'category_name' => $category_name,
                                                    'quantity' => $quantity,
                                                    'location' => $location,
                                                    'total_sold' => $total_sold,
                                                    'currency' => $currency,
                                                    'seller_name' => $seller_name,
                                                    'seller_feedback' => $seller_feddback_score,
                                                    'price' => $price,
                                                    'dirty_price' => $price,
                                                    'listing' => $listing,
                                                    'condition_id' => $condition_id,
                                                    'condition_name' => $condition_name,
                                                    'upload_date' => $upload_date,
                                                    'end_date' => $end_date,
                                                    'is_multi' => $is_multi,
                                                    'is_subtitle' => $is_subtitle,
                                                    'info_update' => $now_date,
                                                    'info_completed' => 1,
                                                    'trans_checked' => 1,
                                                    'trans_update' => $now_date,
                                                    'trans_completed' => 1,
                                                    'item_status' => $status
                                                ), array('item_id' => $item_id));
                                        } else {
                                            $this->db->update('ds_ebay_items',
                                                array(
                                                    'info_checked' => 1,
                                                    'url' => $url,
                                                    'image' => $image,
                                                    'title' => $title,
                                                    //'description' => $description,
                                                    'category_id' => $category_id,
                                                    'category_name' => $category_name,
                                                    'quantity' => $quantity,
                                                    'location' => $location,
                                                    'total_sold' => $total_sold,
                                                    'currency' => $currency,
                                                    'seller_name' => $seller_name,
                                                    'seller_feedback' => $seller_feddback_score,
                                                    'dirty_price' => $price,
                                                    'price' => $price,
                                                    'listing' => $listing,
                                                    'condition_id' => $condition_id,
                                                    'condition_name' => $condition_name,
                                                    'upload_date' => $upload_date,
                                                    'end_date' => $end_date,
                                                    'is_multi' => $is_multi,
                                                    'is_subtitle' => $is_subtitle,
                                                    'info_update' => $now_date,
                                                    'info_completed' => 1,
                                                    'trans_checked' => 1,
                                                    'item_status' => $status
                                                ), array('item_id' => $item_id));
                                        }
                                        if ($seller_row = $this->db->get_row("SELECT ID, country FROM ds_ebay_sellers WHERE name = '{$seller_name}'")) {
                                            $seller_id = $seller_row[0];
                                            $seller_country = $seller_row[1];
                                            $this->db->update('ds_ebay_items',
                                                array(
                                                    'seller_id' => $seller_id,
                                                    'seller_country' => $seller_country,
                                                ), array('item_id' => $item_id));
                                        } else {
                                            if ($result['sellers']) {
                                                if (!in_array($seller_name, $result['sellers'])) {
                                                    $result['sellers'][] = $seller_name;
                                                }
                                            } else {
                                                $result['sellers'][] = $seller_name;
                                            }
                                            $result['seller_country'][] = array('item_id' => $item_id, 'seller_name' => $seller_name);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($running && curl_multi_select($mh) === -1) usleep(EBAY_CURL_MULTI_SLEEP);
                    curl_multi_remove_handle($mh, $handle);
                    //curl_close($handle);
                }
            } while($running);
            
            curl_multi_close($mh);
        }
        
        foreach ($items as $item_id => $item_infos) {
            $this->db->update('ds_ebay_items',
                    array(
                        'info_checked' => 1
                    ), array('item_id' => $item_id));
        }
        
        return $result;
    }

    private function getSellerCountry($sellers) {
        if ($sellers) {
            foreach ($sellers as $seller) {
                $this->check_seller_exist($seller);
            }
        }                
    }

    private function getItemSellerCountry($items) {
        if ($items) {
            foreach ($items as $item_info) {
                $seller_name = $item_info['seller_name'];
                if ($row = $this->db->get_row("SELECT ID, name, country FROM ds_ebay_sellers WHERE name = '{$seller_name}'")) {
                    $this->db->update('ds_ebay_items',
                        array(
                            'seller_id' => $row[0],
                            'seller_name' => $row[1],
                            'seller_country' => $row[2]
                        ), array('item_id' => $item_info['item_id']));
                }
            }
        }
    }

    private function get_items_info($items) {
        if (!$items) { return null; }
        
        $item_count = 0;
        $curl_items = $tans_items = $sellers = $seller_country = null;
        foreach ($items as $item_info) {
            $item_count = $item_count + 1;
            $curl_items[$item_info['item_id']] = array('completed' => $item_info['completed'], 'total_sold' => $item_info['total_sold'], 'trans_update' => $item_info['trans_update']);
            if ($item_count == EBAY_ITEM_INFO_ONE_TIME_COUNT) {
                $items_result = $this->get_items_info_curl($curl_items);
                if ($items_result['trans']) {
                    if ($tans_items) {
                        $tans_items = array_merge($tans_items, $items_result['trans']);
                    } else {
                        $tans_items = $items_result['trans'];
                    }
                }
                if ($items_result['sellers']) {
                    if ($sellers) {
                        $sellers = array_merge($sellers, $items_result['sellers']);
                    } else {
                        $sellers = $items_result['sellers'];
                    }
                }
                if ($items_result['seller_country']) {
                    if ($seller_country) {
                        $seller_country = array_merge($seller_country, $items_result['seller_country']);
                    } else {
                        $seller_country = $items_result['seller_country'];
                    }
                }
                $item_count = 0;
                $curl_items = null;
            }
        }
        if ($item_count) {
            $items_result = $this->get_items_info_curl($curl_items);
            if ($items_result['trans']) {
                if ($tans_items) {
                    $tans_items = array_merge($tans_items, $items_result['trans']);
                } else {
                    $tans_items = $items_result['trans'];
                }
            }
            if ($items_result['sellers']) {
                if ($sellers) {
                    $sellers = array_merge($sellers, $items_result['sellers']);
                } else {
                    $sellers = $items_result['sellers'];
                }
            }
            if ($items_result['seller_country']) {
                if ($seller_country) {
                    $seller_country = array_merge($seller_country, $items_result['seller_country']);
                } else {
                    $seller_country = $items_result['seller_country'];
                }
            }
        }
        
        $this->getSellerCountry($sellers);
        
        $this->getItemSellerCountry($seller_country);
        
        return $tans_items;
    }

    private function get_items_trans_curl($items, $country) {
        if (!$items) { return; }
        
        $item_trans_extra_info = array();
        if ($mh1 = curl_multi_init()) {
            $item_trans_item_ids = array();
            foreach ($items as $item_id => $item_info) {
                if ($curl[$item_id] = curl_init()) {
                    $app_no = $this->get_api_call_app_no("GetItemTransactions");
                    $trans_url_head = get_item_trans_req_head($country, $app_no);
                    $trans_url_body = get_item_trans_req_body($item_id, $item_info['period'], 1, $app_no);
                    curl_setopt($curl[$item_id], CURLOPT_URL, EBAY_API_END_POINT);
                    curl_setopt($curl[$item_id], CURLOPT_HEADER, 1);
                    curl_setopt($curl[$item_id], CURLOPT_HTTPHEADER, $trans_url_head);
                    curl_setopt($curl[$item_id], CURLOPT_POSTFIELDS, $trans_url_body);
                    curl_setopt($curl[$item_id], CURLOPT_TIMEOUT, 60);
                    curl_setopt($curl[$item_id], CURLOPT_RETURNTRANSFER, 1);
                    
                    curl_setopt($curl[$item_id], CURLOPT_FOLLOWLOCATION, 1);
                    
                    curl_multi_add_handle($mh1, $curl[$item_id]);
                    $item_trans_item_ids[(int)$curl[$item_id]] = $item_id;
                }
            }
            
            $running = null;
            do {
                while (($status = curl_multi_exec($mh1, $running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status != CURLM_OK) break;
                while ($info = curl_multi_info_read($mh1)) {
                    $handle = $info['handle'];
                    $item_trans_data = curl_multi_getcontent($handle);
                    if ($item_trans_data) {
                        $item_id = $item_trans_item_ids[(int)$handle];
                        if (strpos($item_trans_data, "<ShortMessage>Call usage limit has been reached.</ShortMessage>")) {
                            $item_trans_extra_info[$item_id]['page_count'] = -1;
                        } else {
                            $item_trans_xml = simplexml_load_string(strstr($item_trans_data, '<?xml'));
                            if (!$item_trans_xml->Errors) {
                                $item_trans_extra_info[$item_id]['page_count'] = $item_trans_xml->PaginationResult[0]->TotalNumberOfPages;
                                if (isset($item_trans_xml->TransactionArray)) {
                                    foreach ($item_trans_xml->TransactionArray[0]->children() as $trans) {
                                        $quantity = (int)$trans[0]->QuantityPurchased;                            
                                        $trans_date = date("Y-m-d H:i:s", strtotime($trans[0]->CreatedDate));
                                        $converted_price = (float)$trans[0]->ConvertedTransactionPrice;
                                        $converted_currency = utf8_decode($trans[0]->ConvertedTransactionPrice['currencyID']);
                                        $trans_price = (float)$trans[0]->TransactionPrice;
                                        $trans_currency = utf8_decode($trans[0]->TransactionPrice['currencyID']);
                                        //$buyer_id = $trans[0]->Buyer[0]->UserID;
                                        //$shipping_country = $trans[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->Country;
                                        //$shipping_postal = $trans[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->PostalCode;
                                        $item_trans_extra_info[$item_id]['dirty_price'] = $trans_price;
                                        if ($row = $this->db->get_row("SELECT ID FROM ds_ebay_item_trans WHERE item_id = '{$item_id}' AND trans_date = '{$trans_date}' AND quantity = '{$quantity}' AND converted_price = '{$converted_price}'")) {
                                        } else {
                                            $this->db->insert('ds_ebay_item_trans',
                                                array(
                                                    'item_id' => $item_id,
                                                    'seller_id' => $items[$item_id]['seller_id'],
                                                    'quantity' => $quantity,
                                                    'trans_date' => $trans_date,
                                                    'converted_price' => $converted_price,
                                                    'converted_currency' => $converted_currency,
                                                    'trans_price' => $trans_price,
                                                    'trans_currency' => $trans_currency,
                                                    'total' => (float)$converted_price * $quantity
                                                    //'buyer_id' => $buyer_id,
                                                    //'shipping_country' => $shipping_country,
                                                    //'shipping_postal' => $shipping_postal,
                                                ));
                                        }
                                    }
                                }
                                if ($item_trans_extra_info[$item_id]['page_count'] == 1) {
                                    $this->db->update('ds_ebay_items', array('dirty_price' => $item_trans_extra_info[$item_id]['dirty_price'], 'trans_checked' => 1, 'trans_update' => date("Y-m-d H:i:s"), 'trans_completed' => 1), array('item_id' => $item_id));
                                } else if ($item_trans_extra_info[$item_id]['page_count'] == 0) {
                                    $this->db->update('ds_ebay_items', array('trans_checked' => 1, 'trans_update' => date("Y-m-d H:i:s"), 'trans_completed' => 1), array('item_id' => $item_id));
                                }
                            }
                        }
                    }
                    
                    if ($running && curl_multi_select($mh1) === -1) usleep(EBAY_CURL_MULTI_SLEEP);
                    curl_multi_remove_handle($mh1, $handle);
                    //curl_close($handle);
                }
            } while($running);
            
            curl_multi_close($mh1);
        }
        
        if ($mh2 = curl_multi_init()) {
            $item_trans_item_ids = array();
            foreach ($items as $item_id => $item_info) {
                if (isset($item_trans_extra_info[$item_id]['page_count'])) {
                    if ($item_trans_extra_info[$item_id]['page_count'] > 1) {
                        for ($page_no = 2; $page_no <= $item_trans_extra_info[$item_id]['page_count']; $page_no++) {
                            $item_id_plus_page = $item_id . "page" . $page_no;
                            if ($curl[$item_id_plus_page] = curl_init()) {
                                $app_no = $this->get_api_call_app_no("GetItemTransactions");
                                $trans_url_head = get_item_trans_req_head($country, $app_no);
                                $trans_url_body = get_item_trans_req_body($item_id, $item_info['period'], $page_no, $app_no);
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_URL, EBAY_API_END_POINT);
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_HEADER, 1);
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_HTTPHEADER, $trans_url_head);
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_POSTFIELDS, $trans_url_body);
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_TIMEOUT, 60);
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_RETURNTRANSFER, 1);
                                
                                curl_setopt($curl[$item_id_plus_page], CURLOPT_FOLLOWLOCATION, 1);
                                
                                curl_multi_add_handle($mh2, $curl[$item_id_plus_page]);
                                $item_trans_item_ids[(int)$curl[$item_id_plus_page]] = $item_id;
                            }
                        }
                    }
                }
            }
            
            $running = null;
            do {
                while (($status = curl_multi_exec($mh2, $running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status != CURLM_OK) break;
                while ($info = curl_multi_info_read($mh2)) {
                    $handle = $info['handle'];
                    $item_trans_data = curl_multi_getcontent($handle);
                    if ($item_trans_data) {
                        $item_id = $item_trans_item_ids[(int)$handle];
                        if (strpos($item_trans_data, "<ShortMessage>Call usage limit has been reached.</ShortMessage>")) {
                            $item_trans_extra_info[$item_id]['page_count'] = -1;
                        } else {
                            $item_trans_xml = simplexml_load_string(strstr($item_trans_data, '<?xml'));
                            if (!$item_trans_xml->Errors) {
                                if (isset($item_trans_xml->TransactionArray)) {
                                    foreach ($item_trans_xml->TransactionArray[0]->children() as $trans) {
                                        $quantity = (int)$trans[0]->QuantityPurchased;                            
                                        $trans_date = date("Y-m-d H:i:s", strtotime($trans[0]->CreatedDate));
                                        $converted_price = (float)$trans[0]->ConvertedTransactionPrice;
                                        $converted_currency = utf8_decode($trans[0]->ConvertedTransactionPrice['currencyID']);
                                        $trans_price = (float)$trans[0]->TransactionPrice;
                                        $trans_currency = utf8_decode($trans[0]->TransactionPrice['currencyID']);
                                        //$buyer_id = $trans[0]->Buyer[0]->UserID;
                                        //$shipping_country = $trans[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->Country;
                                        //$shipping_postal = $trans[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->PostalCode;
                                        $item_trans_extra_info[$item_id]['dirty_price'] = $trans_price;
                                        if ($row = $this->db->get_row("SELECT ID FROM ds_ebay_item_trans WHERE item_id = '{$item_id}' AND trans_date = '{$trans_date}' AND quantity = '{$quantity}' AND converted_price = '{$converted_price}'")) {
                                        } else {
                                            $this->db->insert('ds_ebay_item_trans',
                                                array(
                                                    'item_id' => $item_id,
                                                    'seller_id' => $items[$item_id]['seller_id'],
                                                    'quantity' => $quantity,
                                                    'trans_date' => $trans_date,
                                                    'converted_price' => $converted_price,
                                                    'converted_currency' => $converted_currency,
                                                    'trans_price' => $trans_price,
                                                    'trans_currency' => $trans_currency,
                                                    'total' => (float)$converted_price * $quantity
                                                    //'buyer_id' => $buyer_id,
                                                    //'shipping_country' => $shipping_country,
                                                    //'shipping_postal' => $shipping_postal,
                                                ));
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($running && curl_multi_select($mh2) === -1) usleep(EBAY_CURL_MULTI_SLEEP);
                    curl_multi_remove_handle($mh2, $handle);
                    //curl_close($handle);
                }
            } while($running);
            
            curl_multi_close($mh2);
        }
        
        foreach ($items as $item_id => $item_trans_period) {
            if (isset($item_trans_extra_info[$item_id])) {
                if ($item_trans_extra_info[$item_id]['page_count'] > 1) {
                    $this->db->update('ds_ebay_items', array('dirty_price' => $item_trans_extra_info[$item_id]['dirty_price'], 'trans_update' => date("Y-m-d H:i:s"), 'trans_checked' => 1, 'trans_completed' => 1), array('item_id' => $item_id));
                }
            } else {
                $this->db->update('ds_ebay_items', array('trans_update' => date("Y-m-d H:i:s"), 'trans_checked' => 1, 'trans_completed' => 1), array('item_id' => $item_id));
            }
        }
    }

    private function get_items_trans($items) {
        if (!$items) { return; }
        
        $item_count = 0;
        $curl_items = null;
        $pending_items = null;
        
        $now_date = date("Y-m-d H:i:s");
        $trans_date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));

        foreach ($items as $item_id) {
            $update_flag = 0;
            if ($row = $this->db->get_row("SELECT trans_checked, trans_update, trans_completed, seller_id FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                if ($row[2] == 1) {
                    if ($row[1] < $trans_date_before) {
                        if ($row[0] == 1) {
                            $period = (int)((strtotime($now_date) - strtotime($row[1])  + (24 * 60 * 60) - 1) / (24 * 60 * 60));
                            $period = ($period > 30) ? 30 : $period;
                            $this->db->update('ds_ebay_items', array('trans_checked' => 0), array('item_id' => $item_id));
                            $curl_items[$item_id] = array('period' => 30, 'seller_id' => $row[3]);
                            $update_flag = 1;
                        }
                    }
                } else {
                    if ($row[0] == 1) {
                        $this->db->update('ds_ebay_items', array('trans_checked' => 0), array('item_id' => $item_id));
                        $curl_items[$item_id] = array('period' => 30, 'seller_id' => $row[3]);
                        $update_flag = 1;
                    } else {
                        $pending_items[] = $item_id;
                    }
                }
            }
            if ($update_flag == 1) {
                $item_count = $item_count + 1;
                if ($item_count == EBAY_ITEM_TRANSACTION_ONE_TIME_COUNT) {
                    $this->get_items_trans_curl($curl_items, "EBAY-US");
                    $item_count = 0;
                    $curl_items = null;
                }
            }
        }
        
        if ($item_count) {
            $this->get_items_trans_curl($curl_items, "EBAY-US");
        }

        return $pending_items;
    }

    private function get_seller_items_by_page($seller_id, $name, $country, $page_no) {
        $result = array(
            'data' => array(
                'update' => array(
                    'copies' => null,
                    'info' => null,
                    'trans' => null,
                ),
                'pending' => array(
                    'copies' => null,
                    'info' => null
                ),
            ),
            'count' => 0
        );
        
        $seller_items_url = get_find_advanced_items_by_name_url($name, $country, $page_no);
        $total_enties = $check_count = 0;
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $seller_items_url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 120);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            
            $seller_items_html_data = curl_exec($curl);
            curl_close($curl);
            
            $dom = str_get_html($seller_items_html_data);
            if( !(empty($dom) || !is_object($dom)) ) {
                if (preg_match('#class="rcnt"(.*?)</span>#', $seller_items_html_data, $match)){
                    $total_enties = (int)trim(str_replace([',','>'], '', $match[1]));
                }
                if ($total_enties) {
                    if ($item_els = $dom->find('li.sresult')) {
                        foreach ($item_els as $item_el) {
                            $item_id = 0;
                            $item_url = $item_title = $item_copies_url = $title_url = "";
                            if ($item_url_el = $item_el->find('h3.lvtitle a', 0)) {
                                $item_url = $item_url_el->href;                                
                                $item_title = str_replace("'", "", $item_url_el->plaintext);
                                if (preg_match('#itm\/(.*?)\/#', $item_url, $match)) {
                                    $title_url = $match[1];
                                }
                            }
                            if (preg_match('#\/([\d]+)\?#', $item_url, $match)) {
                                $item_id = $match[1];
                            }
                            if (!$item_id) {
                                if (preg_match("\/([\d]+)$", $item_url, $match)) {
                                    $item_id = $match[1];
                                }
                            }
                            $item_copies_url = get_item_copies_url($country, $item_id, $title_url);
                            $result['count'] = $result['count'] + 1;
                            if ($item_id && $item_id > 1000) {
                                if ($row = $this->db->get_row("SELECT seller_id, info_checked, info_update, info_completed, copies_checked, copies_update, copies_completed, trans_checked, trans_update, trans_completed, total_sold FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                    /*
                                        seller_id :                     0
                                        info_checked :                  1
                                        info_update :                   2
                                        info_completed :                3
                                        copies_checked :                4
                                        copies_update :                 5
                                        copies_completed :              6
                                        trans_checked :                 7
                                        trans_update :                  8
                                        trans_completed :               9
                                        total_sold :                    10
                                    */
                                    $now_date = date("Y-m-d H:i:s");
                                    $copies_date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
                                    $info_date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
                                    $trans_date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
                                    
                                    if ($row[6] == 1) {
                                        if ($row[5] < $copies_date_before) {
                                            if ($row[4] == 1) {
                                                $this->db->update('ds_ebay_items', array('copies_checked' => 0), array('item_id' => $item_id));
                                                $result['data']['update']['copies'][] = $item_id;
                                            }
                                        }
                                    } else {
                                        if ($row[4] == 1) {
                                            $this->db->update('ds_ebay_items', array('copies_checked' => 0, 'copies_url' => $item_copies_url), array('item_id' => $item_id));
                                            $result['data']['update']['copies'][] = $item_id;
                                        } else {
                                            $result['data']['pending']['copies'][] = $item_id;
                                        }
                                    }
                                    
                                    $update_info_flag = $info_completed = 0;
                                    if ($row[3] == 1) {
                                        if ($row[2] < $info_date_before) {
                                            if ($row[1] == 1) {
                                                $update_info_flag = 1;
                                                $info_completed = 1;
                                            }
                                        }
                                    } else {
                                        if ($row[1] == 1) {
                                            $update_info_flag = 1;
                                            $info_completed = 0;
                                        } else {
                                            $result['data']['pending']['info'][] = $item_id;
                                        }
                                    }
                                    
                                    if ($update_info_flag) {
                                        $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $item_id));
                                        $result['data']['update']['info'][] = array('item_id' => $item_id, 'completed' => $info_completed, 'total_sold' => $row[10], 'trans_update' => $row[8]);
                                    } else {
                                        if ($row[3] == 1) {                                            
                                            $this->db->update('ds_ebay_items', array('item_status' => 1), array('item_id' => $item_id));
                                        }
                                    }
                                    
                                    if ($row[9] == 1) {
                                        if ($row[8] < $trans_date_before) {
                                            if ($row[7] == 1) {
                                                $result['data']['update']['trans'][] = $item_id;
                                            }
                                        }
                                    } else {
                                        if ($row[7] == 1) {
                                            $result['data']['update']['trans'][] = $item_id;
                                        }
                                    }
                                } else {
                                    $this->db->insert('ds_ebay_items',
                                          array(
                                                'item_id' => $item_id,
                                                'seller_id' => $seller_id,
                                                'title' => $item_title,
                                                'url' => $item_url,
                                                'copies_url' => $item_copies_url,
                                                'info_checked' => 0,
                                                'copies_checked' => 0,
                                                'trans_checked' => 1,
                                            ));
                                    $this->db->update('ds_ebay_item_trans', array('seller_id' => $seller_id), array('seller_id' => 0, 'item_id' => $item_id));
                                    $result['data']['update']['info'][] = array('item_id' => $item_id, 'completed' => 0, 'total_sold' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                    $result['data']['update']['copies'][] = $item_id;
                                    $result['data']['update']['trans'][] = $item_id;
                                }
                            }
                        }
                    }
                }
            }
            
            unset($curl);
        }
        
        return $result;
    }

    public function track_seller_info($name) {
        if ($row = $this->db->get_row("SELECT ID, completed, checked, update_date FROM ds_ebay_sellers WHERE name = '{$name}'")) {
            if (!$row[1]) {
                $seller_id = $row[0];
                $this->db->update('ds_ebay_items', array('seller_id' => $seller_id), array('seller_id' => 0, 'seller_name' => $name));
                $this->db->update('ds_ebay_items', array('item_status' => 0), array('seller_name' => $name));
                
                $items_data = array(
                    'update' => array(
                        'copies' => null,
                        'info' => null,
                        'trans' => null
                    ),
                    'pending' => array(
                        'copies' => null,
                        'info' => null,
                        'trans' => null
                    )
                );
                $page_no = 1;
                do {
                    $items_result = $this->get_seller_items_by_page($seller_id, $name, 'EBAY-US', $page_no);
                    $page_no = $page_no + 1;
                    if ($items_result['data']['update']['info']) {
                        if ($items_data['update']['info']) {
                            $items_data['update']['info'] = array_merge($items_data['update']['info'], $items_result['data']['update']['info']);
                        } else {
                            $items_data['update']['info'] = $items_result['data']['update']['info'];
                        }
                    }
                    if ($items_result['data']['update']['copies']) {
                        if ($items_data['update']['copies']) {
                            $items_data['update']['copies'] = array_merge($items_data['update']['copies'], $items_result['data']['update']['copies']);
                        } else {
                            $items_data['update']['copies'] = $items_result['data']['update']['copies'];
                        }
                    }
                    if ($items_result['data']['update']['trans']) {
                        if ($items_data['update']['trans']) {
                            $items_data['update']['trans'] = array_merge($items_data['update']['trans'], $items_result['data']['update']['trans']);
                        } else {
                            $items_data['update']['trans'] = $items_result['data']['update']['trans'];
                        }
                    }
                    if ($items_result['data']['pending']['info']) {
                        if ($items_data['pending']['info']) {
                            $items_data['pending']['info'] = array_merge($items_data['pending']['info'], $items_result['data']['pending']['info']);
                        } else {
                            $items_data['pending']['info'] = $items_result['data']['pending']['info'];
                        }
                    }
                    if ($items_result['data']['pending']['copies']) {
                        if ($items_data['pending']['copies']) {
                            $items_data['pending']['copies'] = array_merge($items_data['pending']['copies'], $items_result['data']['pending']['copies']);
                        } else {
                            $items_data['pending']['copies'] = $items_result['data']['pending']['copies'];
                        }
                    }
                } while($items_result['count'] == EBAY_SELLER_ITEM_PER_PAGE);
                
                $page_no = 1;
                do {
                    $items_result = $this->get_seller_items_by_page($seller_id, $name, 'EBAY-GB', $page_no);
                    $page_no = $page_no + 1;
                    if ($items_result['data']['update']['info']) {
                        if ($items_data['update']['info']) {
                            $items_data['update']['info'] = array_merge($items_data['update']['info'], $items_result['data']['update']['info']);
                        } else {
                            $items_data['update']['info'] = $items_result['data']['update']['info'];
                        }
                    }
                    if ($items_result['data']['update']['copies']) {
                        if ($items_data['update']['copies']) {
                            $items_data['update']['copies'] = array_merge($items_data['update']['copies'], $items_result['data']['update']['copies']);
                        } else {
                            $items_data['update']['copies'] = $items_result['data']['update']['copies'];
                        }
                    }
                    if ($items_result['data']['update']['trans']) {
                        if ($items_data['update']['trans']) {
                            $items_data['update']['trans'] = array_merge($items_data['update']['trans'], $items_result['data']['update']['trans']);
                        } else {
                            $items_data['update']['trans'] = $items_result['data']['update']['trans'];
                        }
                    }
                    if ($items_result['data']['pending']['info']) {
                        if ($items_data['pending']['info']) {
                            $items_data['pending']['info'] = array_merge($items_data['pending']['info'], $items_result['data']['pending']['info']);
                        } else {
                            $items_data['pending']['info'] = $items_result['data']['pending']['info'];
                        }
                    }
                    if ($items_result['data']['pending']['copies']) {
                        if ($items_data['pending']['copies']) {
                            $items_data['pending']['copies'] = array_merge($items_data['pending']['copies'], $items_result['data']['pending']['copies']);
                        } else {
                            $items_data['pending']['copies'] = $items_result['data']['pending']['copies'];
                        }
                    }
                } while($items_result['count'] == EBAY_SELLER_ITEM_PER_PAGE);
                
                $trans_items = null;
                if ($items_data['update']['info']) {
                    $trans_items = $this->get_items_info($items_data['update']['info']);
                }
                if ($items_data['update']['copies']) {
                    $this->get_items_copies($items_data['update']['copies']);
                }
                
                $update_trans_items = $trans_temp_items = $trans_sub_items = $trans_add_items = null;
                if ($items_data['update']['info']) {
                    foreach ($items_data['update']['info'] as $item) {
                        if ($trans_items) {
                            if (in_array($item['item_id'], $trans_items)) {
                                if ($items_data['update']['trans']) {
                                    if (!in_array($item['item_id'], $items_data['update']['trans'])) {
                                        $trans_add_items[] = $item['item_id'];
                                    }
                                } else {
                                    $trans_add_items[] = $item['item_id'];
                                }
                            } else {
                                if ($items_data['update']['trans']) {
                                    if (in_array($item['item_id'], $items_data['update']['trans'])) {
                                        $trans_sub_items[] = $item['item_id'];
                                    }
                                }
                            }
                        }
                    }
                }
                if ($items_data['update']['trans']) {
                    if ($trans_sub_items) {
                        $trans_temp_items = array_diff($items_data['update']['trans'], $trans_sub_items);
                    } else {
                        $trans_temp_items = $items_data['update']['trans'];
                    }
                }
                if ($trans_temp_items) {
                    if ($trans_add_items) {
                        $update_trans_items = array_merge($trans_temp_items, $trans_add_items);
                    } else {
                        $update_trans_items = $trans_temp_items;
                    }                    
                } else {
                    $update_trans_items = $trans_add_items;
                }
                
                if ($update_trans_items) {
                    $items_data['pending']['trans'] = $this->get_items_trans($update_trans_items);
                }                
                
                if ($items_data['pending']['info']) {
                    foreach ($items_data['pending']['info'] as $item_id) {
                        $item_while_condition = 1;
                        do {
                            if ($item_row = $this->db->get_row("SELECT info_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                if ($item_row[0]) {
                                    $item_while_condition = 0;
                                } else {
                                    usleep(EBAY_PENDING_SLEEP);
                                }
                            }
                        } while($item_while_condition);
                    }
                }
                if ($items_data['pending']['copies']) {
                    foreach ($items_data['pending']['copies'] as $item_id) {
                        $item_while_condition = 1;
                        do {
                            if ($item_row = $this->db->get_row("SELECT copies_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                if ($item_row[0]) {
                                    $item_while_condition = 0;
                                } else {
                                    usleep(EBAY_PENDING_SLEEP);
                                }
                            }
                        } while($item_while_condition);
                    }
                }
                if ($items_data['pending']['trans']) {
                    foreach ($items_data['pending']['trans'] as $item_id) {
                        $item_while_condition = 1;
                        do {
                            if ($item_row = $this->db->get_row("SELECT trans_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                if ($item_row[0]) {
                                    $item_while_condition = 0;
                                } else {
                                    usleep(EBAY_PENDING_SLEEP);
                                }
                            }
                        } while($item_while_condition);
                    }
                }
                
                $items_count = 0;
                if ($sellers_rows = $this->db->get_row("SELECT COUNT(ID) FROM ds_ebay_items WHERE item_status = 1 AND seller_id = '{$seller_id}'")) {
                    $items_count = $sellers_rows[0];
                }
                $now_date = date("Y-m-d H:i:s");
                $this->db->update('ds_ebay_sellers', array('items_count' => $items_count, 'checked' => 1, 'completed' => 1, 'update_date' => $now_date), array('ID' => $seller_id));
            }
        }
    }

    private function check_seller_exist($name) {
        $result = false;
        $url = EBAY_SELLER_URL . $name;
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            
            $seller_html_data = curl_exec($curl);
            curl_close($curl);
            
            $dom = str_get_html($seller_html_data);
            if ( !(empty($dom) || !is_object($dom)) ) {
                
                $seller_shop = $feedback_ratio = $country = "";
                if ($dom->find('.idx_crd')) {
                    $feedback_score = 0;
                    if (preg_match('#class="perctg">([\s]+)([\d\.]+)%#', $seller_html_data, $match)) {
                        $feedback_ratio = trim($match[2]);
                    }
                    if (preg_match('#eedback score is ([\d]+)#', $seller_html_data, $match)) {
                        $feedback_score = (int)trim($match[1]);
                    }
                    if (preg_match('#<span class="mem_loc">(.*?)</span>#', $seller_html_data, $match)) {
                        $country = trim($match[1]);
                    }
                    if ($el = $dom->find('.store_lk a', 0)) {
                        $seller_shop = basename($el->href);
                    }
                    if ($row = $this->db->get_row("SELECT ID FROM ds_ebay_sellers WHERE name = '{$name}'")) {
                    } else {
                        $this->db->insert('ds_ebay_sellers', array('feedback_ratio' => $feedback_ratio, 'feedback_score' => $feedback_score, 'country' => $country, 'shop' => $seller_shop, 'name' => $name));
                    }
                    $result = true; 
                }
            }

            unset($curl);            
        }
        return $result;
    }

    public function check_seller($name) {
        $result = 'pending';
        $seller_id = 0;
        if ( $row = $this->db->get_row("SELECT ID, checked, completed, update_date FROM ds_ebay_sellers WHERE name = '{$name}'") ) {
            $seller_id = $row[0];
            if ($row[2]) {
                $now_date = date("Y-m-d H:i:s");
                $date_before = date("Y-m-d H:i:s", strtotime("$now_date -2 days"));
                $result = 'success';
                if ($date_before > $row[3] && $row[1]) {
                    $this->db->update('ds_ebay_sellers', array('checked' => 0, 'completed' => 0), array('ID' => $seller_id));
                    $result = 'pending_init';
                }
            } else {
                if ($row[1]) {
                    $this->db->update('ds_ebay_sellers', array('checked' => 0), array('ID' => $seller_id));
                    $result = 'pending_init';
                }
            }
        } else {
            if ($this->check_seller_exist($name)) {
                $result = 'pending_init';
            } else {
                $result = 'failure';
            }
        }
        return $result;
    }

    public function get_seller_info($name, $period, $completed) {
        $result = array(
            'seller_id' => 0,
            'country' => '',
            'items_count' => 0,
            'feedback_score' => 0,
            'feedback_ratio' => 0,
            'sale_earnings' => '',
            'sold_items' => 0,
            'sell_rate' => '0%',
            'items' => array()
        );
        
        if ( $row = $this->db->get_row("SELECT ID, country, items_count, feedback_score, feedback_ratio FROM ds_ebay_sellers WHERE name = '{$name}'") ) {
            if ($row != null) {
                $seller_id = $row[0];
                $result['seller_id'] = $seller_id;
                $result['country'] = $row[1];
                $result['items_count'] = $row[2];
                $result['feedback_score'] = $row[3];
                $result['feedback_ratio'] = $row[4];
                
                $now_date = date("Y-m-d H:i:s");
                $before_date = date("Y-m-d H:i:s", strtotime("$now_date -$period days" ));
                
                $total_sold_count = 0;
                $total_sold_money = 0;
                if ($results = $this->db->get_results("SELECT item_id, url, image, title, currency, price, total_sold, upload_date, copies_count FROM ds_ebay_items WHERE seller_id = '{$seller_id}' AND item_status = 1")) {
                    if ($results != null) {
                        if (is_array($results)) {
                            $result['items_count'] = count($results);
                            foreach ($results as $item) {
                                $period_sold = 0;
                                $item_id = $item['item_id'];
                                if ($solds = $this->db->get_row("SELECT SUM(quantity), SUM(total) FROM ds_ebay_item_trans WHERE trans_date >= '{$before_date}' AND item_id = '{$item_id}'")) {
                                    $period_sold = (int)$solds[0];
                                    $total_sold_count = $total_sold_count +  $period_sold;
                                    $total_sold_money = $total_sold_money +  (float)$solds[1];
                                }
                                $currency_string = "$";
                                if ($item['currency']) {
                                    $currency_string = get_currency_string($item['currency']);
                                }
                                if ($completed) {
                                    if ($period_sold) {
                                        $result['items'][] = array(
                                            'item_id' => $item['item_id'],
                                            'url' => $item['url'],
                                            'image' => $item['image'],
                                            'title' => $item['title'],
                                            'price' => $currency_string . $item['price'],
                                            'item_id' => $item['item_id'],
                                            'total_sold' => $item['total_sold'],
                                            'upload_date' => $item['upload_date'],
                                            'copies_count' => $item['copies_count'],
                                            'period_sold' => $period_sold
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
                
                $result['sold_items'] = $total_sold_count;
                $result['sale_earnings'] = "$" . $total_sold_money;
                
                if ($result['items_count'] == 0) {
                    $result['sell_rate'] = '0%';
                } else {
                    $result['sell_rate'] = round($total_sold_count / $result['items_count'] * 100, 2) . '%';
                }
            }
        }
        
        return $result;
    }

    public function get_competition_urls($item_id) {
        $result = null;
        if ( $row = $this->db->get_row("SELECT copies_url, seller_id FROM ds_ebay_items WHERE item_id = '{$item_id}'") ) {
            if ($row[0]) {
                $seller_id = $row[1];
                $update_info_items = $update_trans_items = $update_trans0_items = $update_trans1_items = $pending_info_items = $pending_trans_items = $items = $result = null;
                if ($curl = curl_init()) {
                    curl_setopt($curl, CURLOPT_URL, $row[0]);
                    curl_setopt($curl, CURLOPT_HEADER, 0);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
                    
                    $html = curl_exec($curl);
                    curl_close($curl);
                    
                    $dom = str_get_html($html);
                    if ( !(empty($dom) || !is_object($dom)) ) {
                        $copies_count = 0;
                        if (preg_match('#class="rcnt"(.*?)</span>#', $html, $match)) {
                            $copies_count = (int)trim(str_replace([',', '>'], '', $match[1]));
                        }
                        
                        if ($copies_count) {
                            if ($els = $dom->find('.lvresult')) {
                                $item_find_count = 0;
                                $now_date = date("Y-m-d H:i:s");
                                $date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
                                foreach ($els as $el) {
                                    $competitor_item_id = $competitor_item_price = 0;
                                    $competitor_item_url = $competitor_item_title = "";
                                    $competitor_item_img = "";
                                    if ($elLink = $el->find('.lvtitle a', 0)) {
                                        $competitor_item_url = $elLink->href;
                                        $competitor_item_title = $elLink->title;
                                    }
                                    if (preg_match('#\/([\d]+)\?#', $competitor_item_url, $match)) {
                                        $competitor_item_id = $match[1];
                                    }
                                    if (!$competitor_item_id) {
                                        if (preg_match("/\/([\d]+)$/", $competitor_item_url, $match)) {
                                            $competitor_item_id = $match[1];
                                        }
                                    }
                                    if ($elPrice = $el->find('.lvprice span', 0)) {
                                        if (preg_match('#([\d\.]+)#', $elPrice->plaintext, $match)){
                                            $competitor_item_price = $match[1];
                                        }
                                    }
                                    
                                    if ($competitor_item_id && $competitor_item_id > 1000) {
                                        $item_find_count = $item_find_count + 1;
                                        $items[] = $competitor_item_id;
                                        if ($row = $this->db->get_row("SELECT info_checked, info_update, info_completed, trans_checked, trans_update, trans_completed FROM ds_ebay_items WHERE item_id = '{$competitor_item_id}'")) {
                                            /*
                                                info_checked :                  0
                                                info_update :                   1
                                                info_completed :                2
                                                trans_checked :                 3
                                                trans_update :                  4
                                                trans_completed :               5
                                            */
                                            if ($row[2] == 1){
                                                if ($row[1] < $date_before) {
                                                    if ($row[0] == 1) {
                                                        $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $competitor_item_id));
                                                        $update_info_items[] = array('item_id' => $competitor_item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                                    }
                                                }
                                            } else {
                                                if ($row[0] == 1) {
                                                    $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $competitor_item_id));
                                                    $update_info_items[] = array('item_id' => $competitor_item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                                } else {
                                                    $pending_info_items[] = $competitor_item_id;
                                                }
                                            }
                                            if ($row[5] == 1) {
                                                if ($row[4] < $date_before) {
                                                    if ($row[3] == 1) {
                                                        $update_trans0_items[] = $competitor_item_id;
                                                    }
                                                }
                                            } else {
                                                $update_trans0_items[] = $competitor_item_id;
                                            }
                                        } else {
                                            $this->db->insert('ds_ebay_items', array('info_checked' => 0, 'item_id' => $competitor_item_id));
                                            $update_info_items[] = array('item_id' => $competitor_item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                            $update_trans0_items[] = $competitor_item_id;
                                        }
                                    }
                                    if ($item_find_count == $copies_count) {
                                        break;
                                    }
                                }
                            }
                        }
                        
                        unset($curl);
                        
                        $trans_temp_items = $trans_sub_items = $trans_add_items = null;
                        $update_trans1_items = $this->get_items_info($update_info_items);
                        if ($update_info_items) {
                            foreach ($update_info_items as $item) {
                                if ($update_trans1_items) {
                                    if (in_array($item['item_id'], $update_trans1_items)) {
                                        if ($update_trans0_items) {
                                            if (!in_array($item['item_id'], $update_trans0_items)) {
                                                $trans_add_items[] = $item['item_id'];
                                            }
                                        } else {
                                            $trans_add_items[] = $item['item_id'];
                                        }
                                    } else {
                                        if ($update_trans0_items) {
                                            if (in_array($item['item_id'], $update_trans0_items)) {
                                                $trans_sub_items[] = $item['item_id'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if ($update_trans0_items) {
                            if ($trans_sub_items) {
                                $trans_temp_items = array_diff($update_trans0_items, $trans_sub_items);
                            } else {
                                $trans_temp_items = $update_trans0_items;
                            }
                        }
                        if ($trans_temp_items) {
                            if ($trans_add_items) {
                                $update_trans_items = array_merge($trans_temp_items, $trans_add_items);
                            } else {
                                $update_trans_items = $trans_temp_items;
                            }
                        } else {
                            $update_trans_items = $trans_add_items;
                        }
                        
                        $pending_trans_items = $this->get_items_trans($update_trans_items);
                        
                        if ($pending_info_items) {
                            foreach ($pending_info_items as $item_id) {
                                $pending_status = 1;
                                do {
                                    if ($row = $this->db->get_row("SELECT info_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                        if ($row[0] == 1) {
                                            $pending_status = 0;
                                        }
                                    }
                                    if ($pending_status) {
                                        usleep(1000 * 1000 * 10);
                                    }
                                } while($pending_status);
                            }
                        }
                        if ($pending_trans_items) {
                            foreach ($pending_trans_items as $item_id) {
                                $pending_status = 1;
                                do {
                                    if ($row = $this->db->get_row("SELECT trans_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                        if ($row[0] == 1) {
                                            $pending_status = 0;
                                        }
                                    }
                                    if ($pending_status) {
                                        usleep(1000 * 1000 * 10);
                                    }
                                } while($pending_status);                                
                            }
                        }
                        
                        if ($items) {
                            foreach ($items as $item_id) {
                                if ($row = $this->db->get_row("SELECT image, url, title, total_sold, price, dirty_price, seller_name FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                    $result[] = array(
                                        'image' => $row[0],
                                        'url' => $row[1],
                                        'title' => $row[2],
                                        'total_sold' => $row[3],
                                        'price' => $row[4],
                                        'dirty_price' => $row[5],
                                        'seller_name' => $row[6]
                                    );
                                }
                            }
                        }
                    } else {
                        unset($curl);
                    }
                }
            }
        }
        return $result;
    }

    private function validate_title_dropship($title, $keyword) {
        $title_split = explode(" ", strtolower($title));
        $keyword_split = explode(" ", strtolower($keyword));
        foreach ( $keyword_split as $key_word ) {
            if (!in_array($key_word, $title_split)) {
                return false;
            }
        }
        return true;
    }

    private function find_hot_items_by_pages($find_conditions_array, $page_start, $page_end) {
        $result = null;
        $update_info_items = $update_trans_items = $pending_info_items = $pending_trans_items = $find_items = null;
        
        $now_date = $trans_before = date("Y-m-d H:i:s");
        $date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
        
        if ($find_conditions_array['sold_period']) {
            $trans_str = $now_date . " -" . $find_conditions_array['sold_period'] . " day";
            if ($find_conditions_array['sold_period'] > 1) {
                $trans_str = $trans_str . "s";
            }
            $trans_before = date("Y-m-d H:i:s", strtotime($trans_str));
        }
        
        if ($mh = curl_multi_init()) {
            for ($page_no = $page_start; $page_no <= $page_end; $page_no++) {
                $find_conditions_array['page_num'] = $page_no;
                $find_url = get_find_items_url($find_conditions_array, $this->get_api_call_app_no("FindItemsAdvanced"));
                if ($curl[$page_no] = curl_init()) {
                    curl_setopt($curl[$page_no], CURLOPT_URL, $find_url);
                    curl_setopt($curl[$page_no], CURLOPT_HEADER, 0);
                    curl_setopt($curl[$page_no], CURLOPT_TIMEOUT, 1500);
                    curl_setopt($curl[$page_no], CURLOPT_RETURNTRANSFER, 1);
                    
                    curl_setopt($curl[$page_no], CURLOPT_FOLLOWLOCATION, 1);
                    
                    curl_multi_add_handle($mh, $curl[$page_no]);
                }
            }
            
            $running = null;
            do {
                while (($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status != CURLM_OK) break;
                while ($info = curl_multi_info_read($mh)) {
                    $handle = $info['handle'];
                    $find_json_data = curl_multi_getcontent($handle);
                    if( $find_json_data ) {
                        $result_json = json_decode($find_json_data, true);
                        
                        if (isset($result_json['errorMessage'])) {
                            unset($handle);
                            return $result['status'] = "failure";
                        }
                        
                        $items_info = $result_json['findItemsAdvancedResponse'][0];
                        
                        if ($items_info['ack'][0] == 'Success') {
                            if (isset($items_info['searchResult'])) {
                                $item_count = $items_info['searchResult'][0]['@count'];
                                if ($item_count) {
                                    $items = $items_info['searchResult'][0]['item'];
                                    foreach ($items as $item) {
                                        if ($this->validate_title_dropship($item['title'][0], $find_conditions_array['keyword'])) {
                                            $item_id = $item['itemId'][0];
                                            $find_items[] = $item_id;
                                            if ($row = $this->db->get_row("SELECT info_checked, info_update, info_completed, total_sold, trans_update FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                /*
                                                    info_checked :                  0
                                                    info_update :                   1
                                                    info_completed :                2
                                                    total_sold :                    3
                                                    trans_update :                  4
                                                */
                                                if ($row[2] == 1) {
                                                    if ($row[1] < $date_before) {
                                                        if ($row[0] == 1) {
                                                            $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $item_id));
                                                            $update_info_items[] = array('item_id' => $item_id, 'total_sold' => $row[3], 'completed' => 1, 'trans_update' => $row[4]);
                                                        }
                                                    }
                                                } else {
                                                    if ($row[0] == 1) {
                                                        $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $item_id));
                                                        $update_info_items[] = array('item_id' => $item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                                    } else {
                                                        $pending_info_items[] = $item_id;
                                                    }
                                                }
                                            } else {
                                                $this->db->insert('ds_ebay_items', array('info_checked' => 0, 'item_id' => $item_id));
                                                $update_info_items[] = array('item_id' => $item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if ($running && curl_multi_select($mh) === -1) usleep(EBAY_CURL_MULTI_SLEEP);
                    curl_multi_remove_handle($mh, $handle);
                    //curl_close($handle);
                }
            } while($running);
            
            curl_multi_close($mh);
            
            $this->get_items_info($update_info_items);
            if ($pending_info_items) {
                foreach ($pending_info_items as $item_id) {
                    $pending_status = 1;
                    do {
                        if ($row = $this->db->get_row("SELECT info_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                            if ($row[0] == 1) {
                                $pending_status = 0;
                            }
                        }
                        if ($pending_status) {
                            usleep(1000 * 1000 * 10);
                        }
                    } while($pending_status);
                }
            }
            
            if ($find_items) {
                foreach ($find_items as $item_id) {
                    //if ($row = $this->db->get_row("SELECT title, description, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id, quantity FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                    if ($row = $this->db->get_row("SELECT title, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id, quantity, is_subtitle  FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                        /*
                            title :                         0
                            //description :                 1
                            is_multi :                      1
                            seller_country :                2
                            image :                         3
                            url :                           4
                            category_name :                 5
                            price :                         6
                            currency :                      7
                            seller_name :                   8
                            seller_feedback :               9
                            total_sold :                    10
                            trans_checked :                 11
                            trans_update :                  12
                            trans_completed :               13
                            seller_id :                     14
                            quantity :                      15
                            is_subtitle :                   16
                        */
                        if ($row[1] == 0) {
                            if ((int)SELLER_CHINA_AVAILABLE == 0) {
                                if (strtolower($row[2]) == 'china') {
                                    continue;
                                }
                            }
                            if (ITEM_SUBTITLE_AVAILABLE) {
                                if ($row[16]) {
                                    continue;
                                }
                            }
                            $quantity = (int)$row[15] - (int)$row[10];
                            if ($quantity < (int)$find_conditions_array['quantity_min'] || $quantity > (int)$find_conditions_array['quantity_max']) {
                                continue;
                            }
                            if ((int)DESCRIPTION_CHECK_AVAILABLE == 1) {
                                // $title_split = explode(" ", $row[0]);
                                // $description = $row[1];
                                // $title_no = 0;
                                // $three_words = "";
                                // foreach ($title_split as $title_word) {
                                //     if ($title_no == 0) {
                                //         $three_words = $title_word;
                                //     } else {
                                //         if ($title_no < 3) {
                                //             $three_words = $three_words . " " . $title_word;
                                //         }
                                //     }
                                //     $title_no = $title_no + 1;
                                // }
                                // if (strpos(strtolower($description), strtolower($three_words)) == false) {
                                //     continue;
                                // }
                            }
                            if ($find_conditions_array['sold_period']) {
                                $update_flag = 0;
                                if ($row[13]) {
                                    if ($row[12] < $date_before) {
                                        if ($row[11] == 1) {
                                            $update_trans_items[] = $item_id;
                                            $update_flag = 1;
                                        }
                                    }
                                } else {
                                    $update_trans_items[] = $item_id;
                                    $update_flag = 1;
                                }
                                if (!$update_flag) {
                                    if ($solds = $this->db->get_row("SELECT SUM(quantity) FROM ds_ebay_item_trans WHERE trans_date >= '{$trans_before}' AND item_id = '{$item_id}'")) {
                                        $period_sold = (int)$solds[0];
                                        if ($period_sold >= $find_conditions_array['sold_min'] && $period_sold <= $find_conditions_array['sold_max']) {
                                            $duplicator_flag = 0;
                                            if ($result) {
                                                foreach ($result as $result_item) {
                                                    if ($result_item['item_id'] == $item_id) {
                                                        $duplicator_flag = 1;
                                                        break;
                                                    }
                                                }
                                            }
                                            if (!$duplicator_flag) {
                                                $result[] = array(
                                                    'item_id'           => $item_id,
                                                    'title'             => $row[0],
                                                    'image'             => $row[3],
                                                    'url'               => $row[4],
                                                    'category'          => $row[5],
                                                    'price'             => $row[6],
                                                    'currency'          => $row[7],
                                                    'seller'            => $row[8],
                                                    'seller_feedback'   => $row[9],
                                                    'sold'              => $period_sold
                                                );
                                            }
                                        }
                                    }
                                }
                            } else {
                                if ($row[10] >= $find_conditions_array['sold_min'] && $row[10] <= $find_conditions_array['sold_max']) {
                                    $duplicator_flag = 0;
                                    if ($result) {
                                        foreach ($result as $result_item) {
                                            if ($result_item['item_id'] == $item_id) {
                                                $duplicator_flag = 1;
                                                break;
                                            }
                                        }
                                    }
                                    if (!$duplicator_flag) {
                                        $result[] = array(
                                            'item_id'           => $item_id,
                                            'title'             => $row[0],
                                            'image'             => $row[3],
                                            'url'               => $row[4],
                                            'category'          => $row[5],
                                            'price'             => $row[6],
                                            'currency'          => $row[7],
                                            'seller'            => $row[8],
                                            'seller_feedback'   => $row[9],
                                            'sold'              => $row[10]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            if ($find_conditions_array['sold_period']) {
                $pending_trans_items = $this->get_items_trans($update_trans_items);
                if ($pending_trans_items) {
                    foreach ($pending_trans_items as $item_id) {
                        $pending_status = 1;
                        do {
                            if ($row = $this->db->get_row("SELECT trans_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                if ($row[0] == 1) {
                                    $pending_status = 0;
                                }
                            }
                            if ($pending_status) {
                                usleep(1000 * 1000 * 10);
                            }
                        } while($pending_status);
                    }
                }
                
                if ($update_trans_items) {
                    foreach ($update_trans_items as $item_id) {
                        if ($solds = $this->db->get_row("SELECT SUM(quantity) FROM ds_ebay_item_trans WHERE trans_date >= '{$trans_before}' AND item_id = '{$item_id}'")) {
                            $period_sold = (int)$solds[0];
                            if ($period_sold >= $find_conditions_array['sold_min'] && $period_sold <= $find_conditions_array['sold_max']) {
                                $duplicator_flag = 0;
                                if ($result) {
                                    foreach ($result as $result_item) {
                                        if ($result_item['item_id'] == $item_id) {
                                            $duplicator_flag = 1;
                                            break;
                                        }
                                    }
                                }
                                if (!$duplicator_flag) {
                                    //if ($row = $this->db->get_row("SELECT title, description, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                    if ($row = $this->db->get_row("SELECT title, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                        if ($solds = $this->db->get_row("SELECT SUM(quantity) FROM ds_ebay_item_trans WHERE trans_date >= '{$trans_before}' AND item_id = '{$item_id}'")) {
                                            $period_sold = (int)$solds[0];
                                            $result[] = array(
                                                'item_id'           => $item_id,
                                                'title'             => $row[0],
                                                'image'             => $row[3],
                                                'url'               => $row[4],
                                                'category'          => $row[5],
                                                'price'             => $row[6],
                                                'currency'          => $row[7],
                                                'seller'            => $row[8],
                                                'seller_feedback'   => $row[9],
                                                'sold'              => $period_sold
                                            );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        return $result;
    }

    public function find_hot_items($find_conditions_array) {
        if (!$find_conditions_array) { return null; }
        
        $result = null;
        
        $now_date = $trans_before = date("Y-m-d H:i:s");
        $date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
        
        if ($find_conditions_array['sold_period']) {
            $trans_str = $now_date . " -" . $find_conditions_array['sold_period'] . " day";
            if ($find_conditions_array['sold_period'] > 1) {
                $trans_str = $trans_str . "s";
            }
            $trans_before = date("Y-m-d H:i:s", strtotime($trans_str));
        }
        
        if (isset($find_conditions_array)) {
            if (is_array($find_conditions_array)) {
                $items_count = empty($find_conditions_array['item_count']) ? 0 : $find_conditions_array['item_count'];
                $item_no = 0;
                $page_no = 1;
                $total_pages = -1;
                if ($find_conditions_array['item_count'] > 0) {
                    $find_conditions_array['page_num'] = $page_no;
                    $close_flag = 0;
                    $find_url = get_find_items_url($find_conditions_array, $this->get_api_call_app_no("FindItemsAdvanced"));
                    if ($find_curl = curl_init()) {
                        curl_setopt($find_curl, CURLOPT_URL, $find_url);
                        curl_setopt($find_curl, CURLOPT_HEADER, 0);
                        curl_setopt($find_curl, CURLOPT_TIMEOUT, 1500);
                        curl_setopt($find_curl, CURLOPT_RETURNTRANSFER, 1);
                        
                        curl_setopt($find_curl, CURLOPT_FOLLOWLOCATION, 1);
                        
                        $find_json_data = curl_exec($find_curl);
                        curl_close($find_curl);
                        
                        if ($find_json_data) {
                            $result_json = json_decode($find_json_data, true);
                            
                            if (isset($result_json['errorMessage'])) {
                                $result = "Scrap Error";
                                unset($find_curl);
                                return $result;
                            }
                            
                            $items_info = $result_json['findItemsAdvancedResponse'][0];
                            
                            if ($items_info['ack'][0] == 'Success') {
                                $total_pages = (int)$items_info['paginationOutput'][0]['totalPages'][0];
                                if (isset($items_info['searchResult'])) {
                                    $item_count = $items_info['searchResult'][0]['@count'];
                                    if ($item_count) {
                                        $items = $items_info['searchResult'][0]['item'];
                                        $update_info_items = $update_trans_items = $pending_info_items = $pending_trans_items = $find_items = null;
                                        foreach ($items as $item) {
                                            if ($this->validate_title_dropship($item['title'][0], $find_conditions_array['keyword'])) {
                                                $item_id = $item['itemId'][0];
                                                $find_items[] = $item_id;
                                                if ($row = $this->db->get_row("SELECT info_checked, info_update, info_completed, total_sold, trans_update FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                    /*
                                                        info_checked :                  0
                                                        info_update :                   1
                                                        info_completed :                2
                                                        total_sold :                    3
                                                        trans_update :                  4
                                                    */
                                                    if ($row[2] == 1) {
                                                        if ($row[1] < $date_before) {
                                                            if ($row[0] == 1) {
                                                                $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $item_id));
                                                                $update_info_items[] = array('item_id' => $item_id, 'total_sold' => $row[3], 'completed' => 1, 'trans_update' => $row[4]);
                                                            }
                                                        }
                                                    } else {
                                                        if ($row[0] == 1) {
                                                            $this->db->update('ds_ebay_items', array('info_checked' => 0), array('item_id' => $item_id));
                                                            $update_info_items[] = array('item_id' => $item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                                        } else {
                                                            $pending_info_items[] = $item_id;
                                                        }
                                                    }
                                                } else {
                                                    $this->db->insert('ds_ebay_items', array('info_checked' => 0, 'item_id' => $item_id));
                                                    $update_info_items[] = array('item_id' => $item_id, 'total_sold' => 0, 'completed' => 0, 'trans_update' => '0000-00-00 00:00:00');
                                                }
                                            }
                                        }
                                        
                                        $close_flag = 1;
                                        unset($find_curl);
                                        
                                        $this->get_items_info($update_info_items);
                                        if ($pending_info_items) {
                                            foreach ($pending_info_items as $item_id) {
                                                $pending_status = 1;
                                                do {
                                                    if ($row = $this->db->get_row("SELECT info_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                        if ($row[0] == 1) {
                                                            $pending_status = 0;
                                                        }
                                                    }
                                                    if ($pending_status) {
                                                        usleep(1000 * 1000 * 10);
                                                    }
                                                } while($pending_status);
                                            }
                                        }
                                        
                                        if ($find_items) {
                                            foreach ($find_items as $item_id) {
                                                if ($row = $this->db->get_row("SELECT title, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id, quantity, is_subtitle FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                //if ($row = $this->db->get_row("SELECT title, description, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id, quantity FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                    /*
                                                        title :                         0
                                                        //description :                   1
                                                        is_multi :                      1
                                                        seller_country :                2
                                                        image :                         3
                                                        url :                           4
                                                        category_name :                 5
                                                        price :                         6
                                                        currency :                      7
                                                        seller_name :                   8
                                                        seller_feedback :               9
                                                        total_sold :                    10
                                                        trans_checked :                 11
                                                        trans_update :                  12
                                                        trans_completed :               13
                                                        seller_id :                     14
                                                        quantity :                      15
                                                        is_subtitle :                   16
                                                    */
                                                    if ($row[1] == 0) {
                                                        if ((int)SELLER_CHINA_AVAILABLE == 0) {
                                                            if (strtolower($row[2]) == 'china') {
                                                                continue;
                                                            }
                                                        }
                                                        if (ITEM_SUBTITLE_AVAILABLE) {
                                                            if ($row[16]) {
                                                                continue;
                                                            }
                                                        }
                                                        $quantity = (int)$row[15] - (int)$row[10];
                                                        if ($quantity < (int)$find_conditions_array['quantity_min'] || $quantity > (int)$find_conditions_array['quantity_max']) {
                                                            continue;
                                                        }
                                                        if ((int)DESCRIPTION_CHECK_AVAILABLE == 1) {
                                                            // $title_split = explode(" ", $row[0]);
                                                            //$description = $row[1];
                                                            // $title_no = 0;
                                                            // $three_words = "";
                                                            // foreach ($title_split as $title_word) {
                                                            //     if ($title_no == 0) {
                                                            //         $three_words = $title_word;
                                                            //     } else {
                                                            //         if ($title_no < 3) {
                                                            //             $three_words = $three_words . " " . $title_word;
                                                            //         }
                                                            //     }
                                                            //     $title_no = $title_no + 1;
                                                            // }
                                                            // if (strpos(strtolower($description), strtolower($three_words)) == false) {
                                                            //     continue;
                                                            // }
                                                        }
                                                        if ($find_conditions_array['sold_period']) {
                                                            $update_flag = 0;
                                                            if ($row[13]) {
                                                                if ($row[12] < $date_before) {
                                                                    if ($row[11] == 1) {
                                                                        $update_trans_items[] = $item_id;
                                                                        $update_flag = 1;
                                                                    }
                                                                }
                                                            } else {
                                                                $update_trans_items[] = $item_id;
                                                                $update_flag = 1;
                                                            }
                                                            if (!$update_flag) {
                                                                if ($solds = $this->db->get_row("SELECT SUM(quantity) FROM ds_ebay_item_trans WHERE trans_date >= '{$trans_before}' AND item_id = '{$item_id}'")) {
                                                                    $period_sold = (int)$solds[0];
                                                                    if ($period_sold >= $find_conditions_array['sold_min'] && $period_sold <= $find_conditions_array['sold_max']) {
                                                                        $duplicator_flag = 0;
                                                                        if ($result) {
                                                                            foreach ($result as $result_item) {
                                                                                if ($result_item['item_id'] == $item_id) {
                                                                                    $duplicator_flag = 1;
                                                                                    break;
                                                                                }
                                                                            }
                                                                        }
                                                                        if (!$duplicator_flag) {
                                                                            $item_no = $item_no + 1;
                                                                            if ($item_no <= $items_count) {
                                                                                $result[] = array(
                                                                                    'item_id'           => $item_id,
                                                                                    'title'             => $row[0],
                                                                                    'image'             => $row[3],
                                                                                    'url'               => $row[4],
                                                                                    'category'          => $row[5],
                                                                                    'price'             => $row[6],
                                                                                    'currency'          => $row[7],
                                                                                    'seller'            => $row[8],
                                                                                    'seller_feedback'   => $row[9],
                                                                                    'sold'              => $period_sold
                                                                                );    
                                                                            } 
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        } else {
                                                            if ($row[10] >= $find_conditions_array['sold_min'] && $row[10] <= $find_conditions_array['sold_max']) {
                                                                $duplicator_flag = 0;
                                                                if ($result) {
                                                                    foreach ($result as $result_item) {
                                                                        if ($result_item['item_id'] == $item_id) {
                                                                            $duplicator_flag = 1;
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                if (!$duplicator_flag) {
                                                                    $item_no = $item_no + 1;
                                                                    if ($item_no <= $items_count) {
                                                                        $result[] = array(
                                                                            'item_id'           => $item_id,
                                                                            'title'             => $row[0],
                                                                            'image'             => $row[3],
                                                                            'url'               => $row[4],
                                                                            'category'          => $row[5],
                                                                            'price'             => $row[6],
                                                                            'currency'          => $row[7],
                                                                            'seller'            => $row[8],
                                                                            'seller_feedback'   => $row[9],
                                                                            'sold'              => $row[10]
                                                                        );
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        
                                        if ($find_conditions_array['sold_period']) {
                                            $pending_trans_items = $this->get_items_trans($update_trans_items);
                                            if ($pending_trans_items) {
                                                foreach ($pending_trans_items as $item_id) {
                                                    $pending_status = 1;
                                                    do {
                                                        if ($row = $this->db->get_row("SELECT trans_checked FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                            if ($row[0] == 1) {
                                                                $pending_status = 0;
                                                            }
                                                        }
                                                        if ($pending_status) {
                                                            usleep(1000 * 1000 * 10);
                                                        }
                                                    } while($pending_status);
                                                }
                                            }
                                            
                                            if ($update_trans_items) {
                                                foreach ($update_trans_items as $item_id) {
                                                    if ($solds = $this->db->get_row("SELECT SUM(quantity) FROM ds_ebay_item_trans WHERE trans_date >= '{$trans_before}' AND item_id = '{$item_id}'")) {
                                                        $period_sold = (int)$solds[0];
                                                        if ($period_sold >= $find_conditions_array['sold_min'] && $period_sold <= $find_conditions_array['sold_max']) {
                                                            $duplicator_flag = 0;
                                                            if ($result) {
                                                                foreach ($result as $result_item) {
                                                                    if ($result_item['item_id'] == $item_id) {
                                                                        $duplicator_flag = 1;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            if (!$duplicator_flag) {
                                                                //if ($row = $this->db->get_row("SELECT title, description, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                                if ($row = $this->db->get_row("SELECT title, is_multi, seller_country, image, url, category_name, price, currency, seller_name, seller_feedback, total_sold, trans_checked, trans_update, trans_completed, seller_id FROM ds_ebay_items WHERE item_id = '{$item_id}'")) {
                                                                    $item_no = $item_no + 1;
                                                                    if ($item_no <= $items_count) {
                                                                        if ($solds = $this->db->get_row("SELECT SUM(quantity) FROM ds_ebay_item_trans WHERE trans_date >= '{$trans_before}' AND item_id = '{$item_id}'")) {
                                                                            $period_sold = (int)$solds[0];
                                                                            $result[] = array(
                                                                                'item_id'           => $item_id,
                                                                                'title'             => $row[0],
                                                                                'image'             => $row[3],
                                                                                'url'               => $row[4],
                                                                                'category'          => $row[5],
                                                                                'price'             => $row[6],
                                                                                'currency'          => $row[7],
                                                                                'seller'            => $row[8],
                                                                                'seller_feedback'   => $row[9],
                                                                                'sold'              => $period_sold
                                                                            );
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        if (!$close_flag) {
                            unset($find_curl);
                        }
                        
                        $call_count = (int)(($total_pages - 1 + EBAY_FIND_ITEMS_ONE_TIME_COUNT - 1) / EBAY_FIND_ITEMS_ONE_TIME_COUNT);
                        for ($call_no = 0; $call_no < $call_count; $call_no++) {
                            $page_start = $call_no * EBAY_FIND_ITEMS_ONE_TIME_COUNT + 2;
                            $page_end = ($call_no + 1) * EBAY_FIND_ITEMS_ONE_TIME_COUNT + 1;
                            $page_end = ($page_end > $total_pages) ? $total_pages : $page_end;
                            $result_items = $this->find_hot_items_by_pages($find_conditions_array, $page_start, $page_end);
                            if ($result_items) {
                                if (!isset($result['status'])) {
                                    foreach ($result_items as $item) {
                                        $duplicator_flag = 0;
                                        if ($result) {
                                            foreach ($result as $result_item) {
                                                if ($result_item['item_id'] == $item['item_id']) {
                                                    $duplicator_flag = 1;
                                                    break;
                                                }
                                            }
                                        }
                                        if (!$duplicator_flag) {
                                            $item_no = $item_no + 1;
                                            if ($item_no <= $items_count) {
                                                $result[] = $item;
                                            }
                                        }
                                        if ($item_no >= $items_count) {
                                            break;
                                        }
                                    }
                                } else {
                                    $result = "Scrap Error";
                                    return $result;
                                }                                
                            }
                        }
                    } 
                }
            }
        }
        
        return $result;
    }
    
    public function test_func() {
    }
}