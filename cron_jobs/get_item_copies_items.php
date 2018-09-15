<?php

    define( 'CURRENT_DIR', dirname(__FILE__).'/' );

    require_once( CURRENT_DIR . './functions.php' );

    $db = new DB();

    
    function update_item_copies_info($items, $db) {
        /*
            'https://{{host}}/sch/i.html?_nkw={{name}}&_clu=2&_fcid={{countryId}}&_localstpos&_stpos&gbr=1&LH_BIN=1'
        */

        $insert_items = 0;
        $item_ids = array();
        if ($mh = curl_multi_init()) {
            foreach ($items as $item_id => $item_url) {
                if ($curl[$item_id] = curl_init()) {
                    curl_setopt($curl[$item_id], CURLOPT_URL, $item_url);
                    curl_setopt($curl[$item_id], CURLOPT_HEADER, 0);
                    curl_setopt($curl[$item_id], CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl[$item_id], CURLOPT_TIMEOUT, 30);
                    
                    curl_setopt($curl[$item_id], CURLOPT_FOLLOWLOCATION, 1);
                    $item_ids[(int)$curl[$item_id]] = $item_id;
                    curl_multi_add_handle($mh, $curl[$item_id]);
                }
            }
        }

        $running = null;
        do {
            while (($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
            if ($status != CURLM_OK) break;
            while ($info = curl_multi_info_read($mh)) {
                $handle = $info['handle'];
                $item_id = $item_ids[(int)$handle];
                $copie_items_html_data = curl_multi_getcontent($handle);
                $dom = str_get_html($copie_items_html_data);
                $is_lowest = $copies_count = 0;
                if ( !(empty($dom) || !is_object($dom)) ) {
                    if (preg_match('#class="rcnt"(.*?)</span>#', $copie_items_html_data, $match)) {
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
                                        if ($row = $db->get_row("SELECT ID FROM ds_ebay_items_test WHERE item_id = '{$competitor_item_id}'")) {
                                        } else {
                                            $insert_items = $insert_items + 1;
                                            $db->insert('ds_ebay_items_test', array('item_id' => $competitor_item_id));
                                        }
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
                }
                
                $now_date = date("Y-m-d H:i:s");
                $db->update('ds_ebay_items_test',
                    array(
                        'copies_checked' => 1,
                        'copies_completed' => 1,
                        'copies_update' => $now_date,
                        'is_lowest' => $is_lowest,
                        'copies_count' => $copies_count
                    ), array('item_id' => $item_id));

                if ($running && curl_multi_select($mh) === -1) usleep(EBAY_CURL_MULTI_SLEEP);
                curl_multi_remove_handle($mh, $handle);
                curl_close($handle);
            }
        }  while($running);

        curl_multi_close($mh);

        return $insert_items;
    }

    $copies_call_count = $insert_items = 0;
    do {
        $continue_flag = 1;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE seller_id != 0 AND copies_completed = 0 AND info_completed = 1 AND transaction_completed = 1 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 AND seller_id != 0 ORDER BY copies_update ASC LIMIT " . EBAY_COPIES_ITEM_ONE_TIME)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('copies_checked' => 0), array('item_id' => $item['item_id']));
                        $items[$item['item_id']] = $item['copies_url'];
                    }
                    $insert_items = $insert_items + update_item_copies_info($items, $db);
                    if ($insert_items >= EBAY_ITEM_INSERT_ONE_TIME_COUNT) {
                        return;
                    }
                    $copies_call_count = $copies_call_count + 1;
                    if ($copies_call_count > EBAY_ITEM_INFO_ONE_TIME_COUNT) {
                        return;
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);