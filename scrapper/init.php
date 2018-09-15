<?php

    error_reporting(E_ALL);

	define( 'CURRENT_DIR', dirname(__FILE__) . '/' );

    require_once( CURRENT_DIR . './functions.php' );
    require_once( CURRENT_DIR . './simple_html_dom.php' );
    require_once( CURRENT_DIR . './class.db.php' );
    require_once( CURRENT_DIR . './class.scrapper.php' );

	$action = empty($_REQUEST['action']) ? false : trim($_REQUEST['action']);

    $response = array(
        'status' => 'failure',
        'data' => null
    );

    $scrapper = new Scrapper();
	
	switch ($action) {
	    case 'competitor_research':
			$name = trim( $_REQUEST['name'] );
			$period = trim( $_REQUEST['period'] );
            
            $response['status'] = $scrapper->check_seller($name);
            if ($response['status'] == 'success') {
                $response['data'] = $scrapper->get_seller_info($name, $period, 1);
            }
        break;
        
        case 'track_seller_info':
            $name = trim( $_REQUEST['name'] );
            $new_scrapper = new Scrapper();
            $new_scrapper->track_seller_info($name);
            unset($new_scrapper);
        break;
        
	    case 'get_seller_info':
            $name = trim( $_REQUEST['name'] );
            $period = trim( $_REQUEST['period'] );
            
            $response['status'] = $scrapper->check_seller($name);
            if ($response['status'] == 'success') {
                $response['data'] = $scrapper->get_seller_info($name, $period, 1);
            } else if ($response['status'] == 'pending') {
                $response['data'] = $scrapper->get_seller_info($name, $period, 0);
            }
        break;
        
        case 'find_hot_items' :
            $keyword = empty($_REQUEST['keyword']) ? "" : $_REQUEST['keyword'];
            $category = empty($_REQUEST['category']) ? "0" : $_REQUEST['category'];
            $fb_min = empty($_REQUEST['fb_min']) ? 1 : $_REQUEST['fb_min'];
            $fb_max = empty($_REQUEST['fb_max']) ? 9999999 : $_REQUEST['fb_max'];
            $price_min = empty($_REQUEST['price_min']) ? 1 : $_REQUEST['price_min'];
            $price_max = empty($_REQUEST['price_max']) ? 9999999 : $_REQUEST['price_max'];
            $quantity_min = empty($_REQUEST['quantity_min']) ? 1 : $_REQUEST['quantity_min'];
            $quantity_max = empty($_REQUEST['quantity_max']) ? 9999999 : $_REQUEST['quantity_max'];
            $sold_min = empty($_REQUEST['sold_min']) ? 0 : $_REQUEST['sold_min'];
            $sold_max = empty($_REQUEST['sold_max']) ? 9999999 : $_REQUEST['sold_max'];
            $cond_type = empty($_REQUEST['cond_type']) ? "0" : $_REQUEST['cond_type'];
            $country = empty($_REQUEST['country']) ? "EBAY-US" : $_REQUEST['country'];
            $sold_period = empty($_REQUEST['sold_period']) ? 0 : $_REQUEST['sold_period'];
            $item_count = empty($_REQUEST['item_count']) ? 0 : $_REQUEST['item_count'];
            $find_conditions_array = array('country' => $country, 'keyword' => $keyword, 'category' => $category, 'fb_min' => $fb_min, 'fb_max' => $fb_max, 'price_min' => $price_min, 'price_max' => $price_max,
                                'quantity_min' => $quantity_min, 'quantity_max' => $quantity_max, 'fb_max' => $fb_max, 'sold_min' => $sold_min, 'sold_max' => $sold_max,
                                'cond_type' => $cond_type, 'sold_period' => $sold_period, 'item_count' => $item_count, 'per_page' => 100 );
            
            $new_scrapper = new Scrapper();
            $response['status'] = 'success';
            $response['data'] = $new_scrapper->find_hot_items($find_conditions_array);
            unset($new_scrapper);
        break;
        
        case 'get_competition_urls' :
            $item_id = empty($_REQUEST['item_id']) ? 0 : $_REQUEST['item_id'];
            if ($item_id) {
                $new_scrapper = new Scrapper();
                $response["data"] = $new_scrapper->get_competition_urls($item_id);
                $response["status"] = "success";
                unset($new_scrapper);
            }
        break;
		
		case 'test_func' :
		    $response['data'] = $scrapper->test_func();
		break;
	}
	
	die(json_encode($response));
?>