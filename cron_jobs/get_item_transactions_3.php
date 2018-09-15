<?php

    define( 'CURRENT_DIR', dirname(__FILE__).'/' );

    require_once( CURRENT_DIR . './functions.php' );

    $db = new DB();
    
    define( 'API_CALL_APP_NO', 3 );

    function update_item_transaction_by_country($items, $country, $db) {
        $page_count = 0;
        $items_transaction_result = array();
        if ($mh = curl_multi_init()) {
            foreach ($items as $item_id => $item_info) {
                if ($curl[$item_id] = curl_init()) {
                    $transaction_url_head = get_item_transaction_req_head($country, API_CALL_APP_NO);
                    $transaction_url_body = get_item_transaction_req_body($item_id, $item_info['period'], 1, API_CALL_APP_NO);
        
                    curl_setopt($curl[$item_id], CURLOPT_URL, EBAY_API_END_POINT);
                    curl_setopt($curl[$item_id], CURLOPT_HEADER, 1);
                    curl_setopt($curl[$item_id], CURLOPT_HTTPHEADER, $transaction_url_head);
                    curl_setopt($curl[$item_id], CURLOPT_POSTFIELDS, $transaction_url_body);
                    curl_setopt($curl[$item_id], CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl[$item_id], CURLOPT_TIMEOUT, 30);
                    
                    curl_setopt($curl[$item_id], CURLOPT_FOLLOWLOCATION, 1);
                    
                    curl_multi_add_handle($mh, $curl[$item_id]);
                }
            }
        }
        
        $tranaction_result = array();
        $running = null;
        do {
            while (($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
            if ($status != CURLM_OK) break;
            while ($info = curl_multi_info_read($mh)) {
                $handle = $info['handle'];
                $item_transaction_xml_data = curl_multi_getcontent($handle);
                if (strpos($item_transaction_xml_data, "<ShortMessage>Call usage limit has been reached.</ShortMessage>")) {
                    $page_count = -1;
                }
                if ($page_count >= 0) {
                    $transaction_item_xml = simplexml_load_string(strstr($item_transaction_xml_data, '<?xml'));
                    if ($transaction_item_xml) {
                        if (!$transaction_item_xml->Errors) {
                            if (isset($transaction_item_xml->Ack)) {
                                if ($transaction_item_xml->Ack = 'Success') {
                                    $item_id = $transaction_item_xml->Item[0]->ItemID + 0;
                                    if (isset($items[$item_id])) {
                                        $seller_id = $items[$item_id]['seller_id'];
                                        $tranaction_result[$item_id] = array('page_count' => $transaction_item_xml->PaginationResult[0]->TotalNumberOfPages, 'dirty_price' => 0);
                                        $dirty_price = 0;
                                        if (isset($transaction_item_xml->TransactionArray)) {
                                            foreach ($transaction_item_xml->TransactionArray[0]->children() as $transaction) {
                                                $quantity = (int)$transaction[0]->QuantityPurchased; 
                                                $transaction_date = date("Y-m-d H:i:s", strtotime($transaction[0]->CreatedDate));
                                                $converted_price = (float)$transaction[0]->ConvertedTransactionPrice;
                                                $converted_currency = utf8_decode($transaction[0]->ConvertedTransactionPrice['currencyID']);
                                                $transaction_price = (float)$transaction[0]->TransactionPrice;
                                                $transaction_currency = utf8_decode($transaction[0]->TransactionPrice['currencyID']);
                                                //$buyer_id = $transaction[0]->Buyer[0]->UserID;
                                                //$shipping_country = $transaction[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->Country;
                                                //$shipping_postal = $transaction[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->PostalCode;
                                                $dirty_price = $transaction_price;
            
                                                if ($row = $db->get_row("SELECT ID FROM ds_ebay_item_transaction_test WHERE item_id = '{$item_id}' AND transaction_date = '{$transaction_date}' AND quantity = '{$quantity}' AND converted_price = '{$converted_price}'")) {
                                                } else {
                                                    $db->insert('ds_ebay_item_transaction_test',
                                                        array(
                                                            'item_id' => $item_id,
                                                            'seller_id' => $seller_id,
                                                            'quantity' => $quantity,
                                                            'transaction_date' => $transaction_date,
                                                            'converted_price' => $converted_price,
                                                            'converted_currency' => $converted_currency,
                                                            'transaction_price' => $transaction_price,
                                                            'transaction_currency' => $transaction_currency,
                                                            'total' => (float)$converted_price * $quantity
                                                            //'buyer_id' => $buyer_id,
                                                            //'shipping_country' => $shipping_country,
                                                            //'shipping_postal' => $shipping_postal,
                                                        ));
                                                }
                                            }
                                            $tranaction_result[$item_id]['dirty_price'] = $dirty_price;
                                        }
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
        }  while($running);

        curl_multi_close($mh);

        if ($page_count >= 0) {
            $mh = curl_multi_init();
            foreach ($items as $item_id => $item_info) {
                if (isset($tranaction_result[$item_id])) {
                    if ($tranaction_result[$item_id]['page_count'] > 1) {
                        for ($page_no = 2; $page_no <= $tranaction_result[$item_id]['page_count']; $page_no++) {
                            if ($mh) {
                                if ($curl[$item_id] = curl_init()) {
                                    $transaction_url_head = get_item_transaction_req_head($country, API_CALL_APP_NO);
                                    $transaction_url_body = get_item_transaction_req_body($item_id, $item_info['period'], $page_no, API_CALL_APP_NO);
                        
                                    curl_setopt($curl[$item_id], CURLOPT_URL, EBAY_API_END_POINT);
                                    curl_setopt($curl[$item_id], CURLOPT_HEADER, 1);
                                    curl_setopt($curl[$item_id], CURLOPT_HTTPHEADER, $transaction_url_head);
                                    curl_setopt($curl[$item_id], CURLOPT_POSTFIELDS, $transaction_url_body);
                                    curl_setopt($curl[$item_id], CURLOPT_RETURNTRANSFER, 1);
                                    curl_setopt($curl[$item_id], CURLOPT_TIMEOUT, 30);
                                    
                                    curl_setopt($curl[$item_id], CURLOPT_FOLLOWLOCATION, 1);
                                    
                                    curl_multi_add_handle($mh, $curl[$item_id]);
                                }
                            }
                        }
                    }
                }
            }
            
            $running = null;
            do {
                while (($status = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM);
                if ($status != CURLM_OK) break;
                while ($info = curl_multi_info_read($mh)) {
                    $handle = $info['handle'];
                    $item_transaction_xml_data = curl_multi_getcontent($handle);
                    if (strpos($item_transaction_xml_data, "<ShortMessage>Call usage limit has been reached.</ShortMessage>")) {
                        $page_count = -1;
                    }
                    if ($page_count >= 0) {
                        $transaction_item_xml = simplexml_load_string(strstr($item_transaction_xml_data, '<?xml'));
                        if ($transaction_item_xml) {
                            if (!$transaction_item_xml->Errors) {
                                if (isset($transaction_item_xml->Ack)) {
                                    if ($transaction_item_xml->Ack = 'Success') {
                                        $item_id = $transaction_item_xml->Item[0]->ItemID + 0;
                                        if (isset($items[$item_id])) {
                                            $seller_id = $items[$item_id]['seller_id'];
                                            $dirty_price = 0;
                                            if (isset($transaction_item_xml->TransactionArray)) {
                                                foreach ($transaction_item_xml->TransactionArray[0]->children() as $transaction) {
                                                    $quantity = (int)$transaction[0]->QuantityPurchased; 
                                                    $transaction_date = date("Y-m-d H:i:s", strtotime($transaction[0]->CreatedDate));
                                                    $converted_price = (float)$transaction[0]->ConvertedTransactionPrice;
                                                    $converted_currency = utf8_decode($transaction[0]->ConvertedTransactionPrice['currencyID']);
                                                    $transaction_price = (float)$transaction[0]->TransactionPrice;
                                                    $transaction_currency = utf8_decode($transaction[0]->TransactionPrice['currencyID']);
                                                    $dirty_price = $transaction_price;
                                                    //$buyer_id = $transaction[0]->Buyer[0]->UserID;
                                                    //$shipping_country = $transaction[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->Country;
                                                    //$shipping_postal = $transaction[0]->Buyer[0]->BuyerInfo[0]->ShippingAddress[0]->PostalCode;
                                                    if ($row = $db->get_row("SELECT ID FROM ds_ebay_item_transaction_test WHERE item_id = '{$item_id}' AND transaction_date = '{$transaction_date}' AND quantity = '{$quantity}' AND converted_price = '{$converted_price}'")) {
                                                    } else {
                                                        $db->insert('ds_ebay_item_transaction_test',
                                                            array(
                                                                'item_id' => $item_id,
                                                                'seller_id' => $seller_id,
                                                                'quantity' => $quantity,
                                                                'transaction_date' => $transaction_date,
                                                                'converted_price' => $converted_price,
                                                                'converted_currency' => $converted_currency,
                                                                'transaction_price' => $transaction_price,
                                                                'transaction_currency' => $transaction_currency,
                                                                'total' => (float)$converted_price * $quantity
                                                                //'buyer_id' => $buyer_id,
                                                                //'shipping_country' => $shipping_country,
                                                                //'shipping_postal' => $shipping_postal,
                                                            ));
                                                    }
                                                }
                                                $tranaction_result[$item_id]['dirty_price'] = $dirty_price;
                                            }
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
            }  while($running);
            
            curl_multi_close($mh);
        }
        
        foreach ($items as $item_id => $item_info) {
            $now_date = date("Y-m-d H:i:s");
            if (isset($tranaction_result[$item_id])) {
                $db->update('ds_ebay_items_test', array('dirty_price' => $tranaction_result[$item_id]['dirty_price'], 'transaction_completed' => 1, 'recommend_transaction' => 0, 'transaction_update' => $now_date), array('item_id' => $item_id));
                $page_count = $page_count + $tranaction_result[$item_id]['page_count'];
            } else {
                $db->update('ds_ebay_items_test', array('transaction_checked' => 1), array('item_id' => $item_id));
            }
        }

        return $page_count;
    }

    function update_item_transaction($items, $db) {
        $page_count = update_item_transaction_by_country($items, 'EBAY-US', $db);
        //update_item_transaction_by_country($item_id, 'EBAY-GB', $db);
        return $page_count;
    }

    $now_date = date("Y-m-d H:i:s");
    $limit_date = date("Y-m-d H:i:s", strtotime("$now_date -2 hours" ));
    if ($limit_row = $db->get_row("SELECT update_date, call_status FROM ds_ebay_api_limit WHERE ID = " . API_CALL_APP_NO)) {
        if (!$limit_row[1]) {
            if ($limit_row[0] > $limit_date) {
                return;
            } else {
                $db->update('ds_ebay_api_limit', array('update_date' => $now_date, 'call_status' => 1), array('ID' => API_CALL_APP_NO));
            }
        }
    } else {
        $db->insert('ds_ebay_api_limit', array('update_date' => $now_date, 'ID' => API_CALL_APP_NO));
    }

    $transaction_call_count = $transaction_limit_call_count = 0;
    do {
        $continue_flag = 1;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE seller_id != 0 AND total_sold > 0 AND transaction_completed = 0 AND item_status = 1 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 ORDER BY recommend_transaction DESC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('transaction_checked' => 0), array('item_id' => $item['item_id']));
                        $items[$item['item_id']] = array('period' => 30, 'seller_id' => $item['seller_id']);
                    }
                    if (count($items)) {
                        $page_count = update_item_transaction($items, $db);
                        if ($page_count == -1) {
                            if ($limit_row = $db->get_row("SELECT * FROM ds_ebay_api_limit WHERE ID = " . API_CALL_APP_NO)) {
                                $db->update('ds_ebay_api_limit', array('update_date' => date("Y-m-d H:i:s"), 'call_status' => 0), array('ID' => API_CALL_APP_NO));
                            } else {
                                $db->insert('ds_ebay_api_limit', array('update_date' => date("Y-m-d H:i:s"), 'ID' => API_CALL_APP_NO, 'call_status' => 0));
                            }
                            return;
                        }
                        $transaction_call_count = $transaction_call_count + $page_count;
                        if ($transaction_call_count > EBAY_ITEM_TRANSACTION_ONE_TIME_COUNT) {
                            return;
                        }
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);

    do {
        $continue_flag = 1;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE recommend_transaction > 0 AND total_sold > 0 AND transaction_completed = 0 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 ORDER BY recommend_transaction DESC, transaction_update ASC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('transaction_checked' => 0), array('item_id' => $item['item_id']));
                        if ($item['transaction_update'] == '0000-00-00 00:00:00') {
                            $items[$item['item_id']] = array('period' => 30, 'seller_id' => $item['seller_id']);
                        } else {
                            $diff_day = (((date("Y-m-d H:i:s") - $item['transaction_update']) + (24 * 60 * 60) - 1)) / (24 * 60 * 60) + 1;
                            $diff_day = ($diff_day > 30) ? 30 : $diff_day;
                            $items[$item['item_id']] = array('period' => $diff_day, 'seller_id' => $item['seller_id']);
                        }
                    }
                    if (count($items)) {
                        $page_count = update_item_transaction($items, $db);
                        if ($page_count == -1) {
                            if ($limit_row = $db->get_row("SELECT * FROM ds_ebay_api_limit WHERE ID = " . API_CALL_APP_NO)) {
                                $db->update('ds_ebay_api_limit', array('update_date' => date("Y-m-d H:i:s"), 'call_status' => 0), array('ID' => API_CALL_APP_NO));
                            } else {
                                $db->insert('ds_ebay_api_limit', array('update_date' => date("Y-m-d H:i:s"), 'ID' => API_CALL_APP_NO, 'call_status' => 0));
                            }
                            return;
                        }
                        $transaction_call_count = $transaction_call_count + $page_count;
                        if ($transaction_call_count > EBAY_ITEM_TRANSACTION_ONE_TIME_COUNT) {
                            return;
                        }
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);

    do {
        $continue_flag = 1;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_items_test WHERE total_sold > 0 AND transaction_completed = 0 AND info_checked = 1 AND transaction_checked = 1 AND copies_checked = 1 AND item_status = 1 ORDER BY recommend_transaction DESC, transaction_update ASC LIMIT " . EBAY_CURL_MULTI_COUNT)) {
            if ($results != null) {
                if (is_array($results)) {
                    $items = array();
                    foreach ($results as $item) {
                        $db->update('ds_ebay_items_test', array('transaction_checked' => 0), array('item_id' => $item['item_id']));
                        if ($item['transaction_update'] == '0000-00-00 00:00:00') {
                            $items[$item['item_id']] = array('period' => 30, 'seller_id' => $item['seller_id']);
                        } else {
                            $diff_day = (((date("Y-m-d H:i:s") - $item['transaction_update']) + (24 * 60 * 60) - 1)) / (24 * 60 * 60) + 1;
                            $diff_day = ($diff_day > 30) ? 30 : $diff_day;
                            $items[$item['item_id']] = array('period' => $diff_day, 'seller_id' => $item['seller_id']);
                        }
                    }
                    if (count($items)) {
                        $page_count = update_item_transaction($items, $db);
                        if ($page_count == -1) {
                            if ($limit_row = $db->get_row("SELECT * FROM ds_ebay_api_limit WHERE ID = " . API_CALL_APP_NO)) {
                                $db->update('ds_ebay_api_limit', array('update_date' => date("Y-m-d H:i:s"), 'call_status' => 0), array('ID' => API_CALL_APP_NO));
                            } else {
                                $db->insert('ds_ebay_api_limit', array('update_date' => date("Y-m-d H:i:s"), 'ID' => API_CALL_APP_NO, 'call_status' => 0));
                            }
                            return;
                        }
                        $transaction_call_count = $transaction_call_count + $page_count;
                        if ($transaction_call_count > EBAY_ITEM_TRANSACTION_ONE_TIME_COUNT) {
                            return;
                        }
                    }
                }
            }
        } else {
            $continue_flag = 0;
        }
    } while($continue_flag);
?>