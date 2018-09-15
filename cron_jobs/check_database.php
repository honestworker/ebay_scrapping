<?php

    define( 'CURRENT_DIR', dirname(__FILE__).'/' );

    require_once( CURRENT_DIR . './functions.php' );

    $db = new DB();
    
    $db->query("REPAIR TABLE ds_ebay_items_test");
    
    $now_date = date("Y-m-d H:i:s");
    $date_before = date("Y-m-d H:i:s", strtotime("$now_date  -1 days"));
    $date_seller_before = date("Y-m-d H:i:s", strtotime("$now_date  -3 days"));
    $date_trans_before = date("Y-m-d H:i:s", strtotime("$now_date  -31 days"));

    $db->query("UPDATE ds_ebay_items_test SET info_checked = 1 WHERE info_completed = 0 AND info_checked = 0 AND info_update < '{$date_before}' AND info_update != '0000-00-00 00:00:00'");
    $db->query("UPDATE ds_ebay_items_test SET transaction_checked = 1 WHERE transaction_completed = 0 AND transaction_checked = 0 AND transaction_update < '{$date_before}' AND transaction_update != '0000-00-00 00:00:00'");
    $db->query("UPDATE ds_ebay_sellers_test SET checked = 1 WHERE checked = 0 AND update_date < '{$date_seller_before}' AND update_date != '0000-00-00 00:00:00'");
    //$db->query("UPDATE ds_ebay_items_test SET copies_checked = 1 WHERE copies_checked = 0 AND copies_update < '{$date_before}' AND copies_update != '0000-00-00 00:00:00'");

    $db->query("DELETE FROM ds_ebay_item_transaction_test WHERE transaction_date < '{$date_trans_before}'");