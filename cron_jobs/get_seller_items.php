<?php

    define( 'CURRENT_DIR', dirname(__FILE__).'/' );

    require_once( CURRENT_DIR . './functions.php' );

    $db = new DB();
    
    function get_seller_items_by_page($seller_id, $name, $country, $db, $page_no) {
        $seller_items_url = get_find_advanced_items_by_name_url($name, $country, $page_no);
        
        $total_enties = $check_count = 0;
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $seller_items_url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            
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
                            if ($item_url_el = $item_el->find('h3.lvtitle a', 0)) {
                                $item_url = $item_url_el->href;
                                $item_title = str_replace("'", "", $item_url_el->plaintext);
                            }
                            if (preg_match('#\/([\d]+)\?#', $item_url, $match)) {
                                $item_id = $match[1];
                            }
                            if (!$item_id) {
                                if (preg_match("\/([\d]+)$", $item_url, $match)) {
                                    $item_id = $match[1];
                                }
                            }
                            
                            $check_count = $check_count + 1;
                            if ($item_id && $item_id > 1000) {
                                $copies_url = "";
                                if ($item_title) {
                                    $copies_url = get_item_copies_url($country, $item_id, $item_title);
                                }
                                if ($row = $db->get_row("SELECT seller_id, recommend_info, recommend_transaction, info_completed, transaction_completed, info_checked, transaction_checked, copies_checked FROM ds_ebay_items_test WHERE item_id = '{$item_id}'")) {
                                    $recommend_info = $row[1];
                                    $recommend_transaction = $row[2];
                                    if ($row[5] == 1 && $row[6] == 1 && $row[7] == 1) {
                                        if (!$row[3]) {
                                            $recommend_info =  $recommend_info + 10;
                                        }
                                        if (!$row[4]) {
                                            $recommend_transaction =  $recommend_transaction + 10;
                                        }
                                        $db->update('ds_ebay_items_test',
                                                array(
                                                    'seller_id' => $seller_id,
                                                    'title' => $item_title,
                                                    'url' => $item_url,
                                                    'copies_url' => $copies_url,
                                                    'recommend_info' => $recommend_info,
                                                    'recommend_transaction' => $recommend_transaction,
                                                    'item_status' => 1
                                                ),
                                                array('item_id' => $item_id));
                                    }
                                } else {
                                    $db->insert('ds_ebay_items_test',
                                            array(
                                                'item_id' => $item_id,
                                                'seller_id' => $seller_id,
                                                'title' => $item_title, 
                                                'url' => $item_url,
                                                'copies_url' => $copies_url,
                                                'recommend_info' => 10,
                                                'recommend_transaction' => 10
                                            ));
                                    $db->update('ds_ebay_item_transaction_test', array('seller_id' => $seller_id), array('seller_id' => 0, 'item_id' => $item_id));
                                }
                            }
                        }
                    }
                }
            }
        }
        return $check_count;
    }

    function get_seller_items($seller_id, $name, $db) {
        $page_no = $total_pages = 1;
        $total_entries = 0;
        do {
            $check_count = get_seller_items_by_page($seller_id, $name, 'EBAY-US', $db, $page_no);
            $page_no = $page_no + 1;
        } while($check_count == EBAY_SELLER_ITEM_PER_PAGE);
        $total_pages = $page_no;

        $page_no = 1;
        do {
            $check_count = get_seller_items_by_page($seller_id, $name, 'EBAY-GB', $db, $page_no);
            $page_no = $page_no + 1;
        } while($check_count == EBAY_SELLER_ITEM_PER_PAGE);
        $total_pages = $total_pages + $page_no;

        return $total_pages;
    }

    function update_seller_info($name, $seller_id, $db) {
        $total_page_no = 0;
        $db->update('ds_ebay_sellers_test', array('checked' => 0), array('ID' => $seller_id));
        
        $db->update('ds_ebay_items_test', array('seller_id' => $seller_id), array('seller_id' => 0, 'seller_name' => $name));
        
        $url = EBAY_SELLER_URL . $name;
        $count_items = 0;
        
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);
            
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            
            $html = curl_exec($curl);
            curl_close($curl);
            
            $dom = str_get_html($html);
            if( !(empty($dom) || !is_object($dom)) ) {
                $seller_shop = $feedback_ratio = $country = "";
                $feedback_score = 0;
                if (preg_match('#class="perctg">([\s]+)([\d\.]+)%#', $html, $match)) {
                    $feedback_ratio = trim($match[2]);
                }
                if (preg_match('#eedback score is ([\d]+)#', $html, $match)) {
                    $feedback_score = (int)trim($match[1]);
                }
                if (preg_match('#<span class="mem_loc">(.*?)</span>#', $html, $match)) {
                    $country = trim($match[1]);
                }
                if ($el = $dom->find('.store_lk a', 0)) {
                    $seller_shop = basename($el->href);
                }                
                $db->update('ds_ebay_sellers_test', array('feedback_ratio' => $feedback_ratio, 'feedback_score' => $feedback_score, 'country' => $country, 'shop' => $seller_shop), array('ID' => $seller_id));
                $total_page_no = get_seller_items($seller_id, $name, $db);
                if ($rows = $db->get_row("SELECT COUNT(ID) FROM ds_ebay_items_test WHERE item_status = 1 AND seller_id = '{$seller_id}'")) {
                    $count_items = $rows[0];
                }
            }
        }
        
        $now_date = date("Y-m-d H:i:s");
        $db->update('ds_ebay_sellers_test', array('count_items' => $count_items, 'checked' => 1, 'recommend' => 0, 'update_date' => $now_date), array('ID' => $seller_id));
        
        return $total_page_no;
    }

    $seller_call_count = $total_page_no = 0;
    do {
        $continue_flag = 0;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_sellers_test WHERE update_date = '0000-00-00 00:00:00' AND checked = 1 ORDER BY recommend DESC LIMIT 1")) {
            if ($results != null) {
                if (is_array($results)) {
                    foreach ($results as $seller) {
                        $total_page_no = $total_page_no + update_seller_info($seller['name'], $seller['ID'], $db);
                        $seller_call_count = $seller_call_count + 1;
                        $continue_flag = 1;
                        if ($seller_call_count > EBAY_SELLER_ITEM_ONE_TIME) {
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
        $now_date = date("Y-m-d H:i:s");
        $date_before = date("Y-m-d H:i:s", strtotime("$now_date -2 days" ));
        $continue_flag = 0;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_sellers_test WHERE update_date < '{$date_before}' AND recommend > 0 AND checked = 1 ORDER BY recommend DESC, update_date ASC LIMIT 1")) {
            if ($results != null) {
                if (is_array($results)) {
                    foreach ($results as $seller) {
                        $total_page_no = $total_page_no + update_seller_info($seller['name'], $seller['ID'], $db);
                        $seller_call_count = $seller_call_count + 1;
                        $continue_flag = 1;
                        if ($seller_call_count > EBAY_SELLER_ITEM_ONE_TIME) {
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
        $now_date = date("Y-m-d H:i:s");
        $date_before = date("Y-m-d H:i:s", strtotime("$now_date -2 days" ));
        $continue_flag = 0;
        if ($results = $db->get_results("SELECT * FROM ds_ebay_sellers_test WHERE update_date < '{$date_before}' AND checked = 1 ORDER BY recommend DESC, update_date ASC LIMIT 1")) {
            if ($results != null) {
                if (is_array($results)) {
                    foreach ($results as $seller) {
                        $continue_flag = 1;
                        $total_page_no = $total_page_no + update_seller_info($seller['name'], $seller['ID'], $db);
                        $seller_call_count = $seller_call_count + 1;
                        if ($seller_call_count > EBAY_SELLER_ITEM_ONE_TIME) {
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