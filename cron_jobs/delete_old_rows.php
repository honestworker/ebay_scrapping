<?php

    define( 'CURRENT_DIR', dirname(__FILE__).'/' );

    require_once( CURRENT_DIR . './functions.php' );

    $db = new DB();
    
    $now_date = date("Y-m-d H:i:s");
    $date_item_before = date("Y-m-d H:i:s", strtotime("$now_date  -5 days"));
    $date_trans_before = date("Y-m-d H:i:s", strtotime("$now_date  -30 days"));

    $db->query("DELETE FROM `ds_ebay_items` WHERE info_checked = 1 AND info_update < '{$date_item_before}' AND info_update != '0000-00-00 00:00:00'");
    $db->query("DELETE FROM `ds_ebay_item_trans` WHERE trans_date < '{$date_trans_before}' AND trans_date != '0000-00-00 00:00:00'");

    $db->query("REPAIR TABLE ds_ebay_items");
    $db->query("REPAIR TABLE ds_ebay_item_trans");
    $db->query("REPAIR TABLE ds_ebay_sellers");