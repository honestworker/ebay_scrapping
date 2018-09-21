<?php
    error_reporting(E_ALL);
    ini_set("display_errors",                       1);
    ini_set("memory_limit",                         "2000M");
    ini_set("display_errors",                       0);

    define( "DB_HOST",                              "Your Host" );
    define( "DB_USER",                              "Your DB User" );
    define( "DB_PASS",                              "Your DB Pass" );
    define( "DB_NAME",                              "Your DB Name" );

    require_once( CURRENT_DIR .                     "./simple_html_dom.php" );
    require_once( CURRENT_DIR .                     "./class.db.php" );

    define( "ITEM_LIMIT",                           7000 );

    define( "SELLER_CHINA_AVAILABLE",               0 );
    define( "DESCRIPTION_CHECK_AVAILABLE",          0 );
    define( "ITEM_SUBTITLE_AVAILABLE",              1 );
    define( "LIMIT_API_CALL_COUNT",                 5000 );
    define( "API_APP1_COUNT",                       23 );
    define( "API_APP2_COUNT",                       3 );

    define( "EBAY_API_VERSION",                     1029 );
    define( "EBAY_API_END_POINT",                   "https://api.ebay.com/ws/api.dll" );
    
    define( "EBAY_APP_NAME_01",                     "Your Ebay APP Name" );
    define( "EBAY_DEV_NAME_01",                     "Your Ebay DEV Name" );
    define( "EBAY_CERT_NAME_01",                    "Your Ebay CERT Name" );
    define( "EBAY_TOKEN_01",                        "Your Ebay TOKEN Name" );

    define( "EBAY_APP_NAME_02",                     "Your Ebay APP Name" );
    define( "EBAY_DEV_NAME_02",                     "Your Ebay DEV Name" );
    define( "EBAY_CERT_NAME_02",                    "Your Ebay CERT Name" );
    define( "EBAY_TOKEN_02",                        "Your Ebay TOKEN Name" );

    define( "EBAY_APP_NAME_03",                     "Your Ebay APP Name" );
    define( "EBAY_DEV_NAME_03",                     "Your Ebay DEV Name" );
    define( "EBAY_CERT_NAME_03",                    "Your Ebay CERT Name" );
    define( "EBAY_TOKEN_03",                        "Your Ebay TOKEN Name" );

    define( "EBAY_APP_NAME_04",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_05",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_06",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_07",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_08",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_09",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_10",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_11",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_12",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_13",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_14",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_15",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_16",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_17",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_18",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_19",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_20",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_21",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_22",                     "Your Ebay APP Name" );
    define( "EBAY_APP_NAME_23",                     "Your Ebay APP Name" );

    define( "EBAY_ITEM_TRANSACTION_ONE_TIME_COUNT", 300 );
    define( "EBAY_ITEM_INFO_ONE_TIME_COUNT",        300 );
    define( "EBAY_COPIES_ITEM_ONE_TIME",            100 );
    define( "EBAY_FIND_ITEMS_ONE_TIME_COUNT",       50 );
    
    define( "EBAY_FIND_URL",                        "http://svcs.ebay.com/services/search/FindingService/v1?operation-name=findItemsAdvanced&global-id=" );
    define( "EBAY_FIND_URL_US",                     "https://www.ebay.com/sch/" );
    define( "EBAY_FIND_URL_UK",                     "https://www.ebay.co.uk/sch/" );
    define( "EBAY_SHOP_URL_US",                     "https://www.ebay.com/str/" );
    define( "EBAY_SHOP_URL_UK",                     "https://www.ebay.co.uk/str/" );
    define( "EBAY_SELLER_URL",                      "https://www.ebay.com/usr/" );

    define( "EBAY_ITEM_TRANSACTION_URL1",           "https://offer.ebay.com/ws/eBayISAPI.dll?ViewBidsLogin&item=" );
    define( "EBAY_ITEM_TRANSACTION_URL2",           "&rt=nc&_trksid=p2047675.l2564" );
    
    define( "EBAY_FIND_ITEM_US_URL",                "http://www.ebay.com/sch/i.html?" );
    define( "EBAY_FIND_ITEM_UK_URL",                "http://www.ebay.co.uk/sch/i.html?" );
    define( "EBAY_FIND_ITEM_BY_SELLER_URL",         "&_saslop=1&_fss=1&LH_SpecificSeller=1&_ddo=1" );
    define( "EBAY_FIND_ITEM_PER_PAGE",              100 );

    define( "EBAY_TRANS_CURL_MULTI_COUNT",          30 );
    define( "EBAY_CURL_MULTI_COUNT",                200 );
    define( "EBAY_CURL_MULTI_SLEEP",                50 );
    define( "EBAY_PENDING_SLEEP",                   1000 * 1000 );

    define( "EBAY_US_URL",                          "https://www.ebay.com" );
    define( "EBAY_UK_URL",                          "https://www.ebay.co.uk" );
    define( "EBAY_SHOP_URL",                        "str" );

    define( "EBAY_SELLER_ITEM_US_URL",              "https://www.ebay.com/sch/m.html?_nkw=&_armrs=1&_from=&_fcid=" );
    define( "EBAY_SELLER_ITEM_UK_URL",              "https://www.ebay.co.uk/sch/m.html?_nkw=&_armrs=1&_from=&_fcid=" );
    define( "EBAY_SELLER_ITEM_URL",                 "&_sop=10&_ssn=" );
    define( "EBAY_SELLER_ITEM_PER_PAGE",            200 );
    
    define( "EBAY_SHOP_ITEM_PER_PAGE",              48 );

    define( "EBAY_ITEM_URL",                        "http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=JSON&appid=" );
    //define( "EBAY_ITEM_INCLUDE_URL",                "&IncludeSelector=Description,Variations,Details" );
    define( "EBAY_ITEM_INCLUDE_URL",                "&IncludeSelector=Subtitle,Variations,Details" );
    
    function get_find_advanced_items_by_seller_url($name, $country, $page_no = 1) {
        /*
            http://svcs.ebay.com/services/search/FindingService/v1?operation-name=findItemsAdvanced&global-id=EBAY-GB
            &itemFilter(0).name=Seller&itemFilter(0).value=alyps70&paginationInput.entriesPerPage=100&paginationInput.pageNumber=1
            &security-appname=YourAPPNAME&response-data-format=xml
        */
        $return_url = EBAY_FIND_URL . $country;
        $return_url = $return_url . "&itemFilter(0).name=Seller&itemFilter(0).value=" . $name;
        $return_url = $return_url . "&paginationInput.entriesPerPage=" . EBAY_FIND_ITEM_PER_PAGE;
        $return_url = $return_url . "&paginationInput.pageNumber=" . $page_no;
        $return_url = $return_url . "&security-appname=" . EBAY_APP_NAME_0 . "&response-data-format=json";
        
        return $return_url;
    }

    function get_full_country_name_from_to_digit($country) {
        $EBAY_COUNTRY_LIST = array(
            "AD" => "Andorra", "AE" => "United Arab Emirates", "AF" => "Afghanistan", "AG" => "Antigua and Barbuda", "AI" => "Anguilla", "AL" => "Albania", "AM" => "Armenia", "AN" => "Netherlands Antille", "AO" => "Angola", "AQ" => "Antarctica",
            "AR" => "Argentina", "AS" => "American Samoa", "AT" => "Austria", "AU" => "Australia", "AW" => "Aruba", "AZ" => "Azerbaijan", "BA" => "Bosnia and Herzegovina", "BB" => "Barbados", "BD" => "Bangladesh", "BE" => "Belgium", "BF" => "Burkina Faso",
            "BG" => "Bulgaria", "BH" => "Bahrain", "BI" => "Burundi", "BJ" => "Benin", "BM" => "Bermuda", "BN" => "Brunei Darussalam", "BO" => "Bolivia", "BR" => "Brazil", "BS" => "Bahamas", "BT" => "Bhutan", "BV" => "Bouvet Island", "BW" => "Botswana",
            "BY" => "Belarus", "BO" => "Bolivia", "BR" => "Brazil", "BS" => "Bahamas", "BT" => "Bhutan", "BV" => "Bouvet Island", "BW" => "Botswana", "BY" => "Belarus", "BZ" => "Belize", "CA" => "Canada", "CC" => "Cocos (Keeling) Islands",
            "CD" => "The Democratic Republic of the Congo", "CF" => "Central African Republic", "CG" => "Congo", "CH" => "Switzerland", "CI" => "Cote d'Ivoire", "CK" => "Cook Islands", "CL" => "Chile", "CM" => "Cameroon", "CN" => "China",
            "CO" => "Colombia", "CR" => "Costa Rica", "CU" => "Cuba", "CV" => "Cape Verde", "CX" => "Christmas Island", "CY" => "Cyprus", "CZ" => "Czech Republic", "DE" => "Germany", "DJ" => "Djibouti", "DK" => "Denmark", "DM" => "Dominica",
            "DO" => "Dominican Republic", "DZ" => "Algeria", "EC" => "Ecuador", "EE" => "Estonia", "EG" => "Egypt", "EH" => "Western Sahara","ER" => "Eritrea", "ES" => "Spain", "ET" => "Ethiopia", "FI" => "Finland", "FJ" => "Fiji", "FK" => "Falkland Islands (Malvinas)",
            "FM" => "Federated States of Micronesia", "FO" => "Faroe Islands", "FR" => "France", "GA" => "Gabon", "GB" => "United Kingdom", "GD" => "Grenada", "GE" => "Georgia", "GF" => "French Guiana", "GH" => "Ghana", "GI" => "Gibraltar",
            "GL" => "Greenland", "GM" => "Gambia", "GN" => "Guinea", "GP" => "Guadeloupe", "GQ" => "Equatorial Guinea", "GR" => "Greece", "GS" => "South Georgia and the South Sandwich Islands", "GT" => "Guatemala", "GU" => "Guam", "GW" => "Guinea-Bissau",
            "GY" => "Guyana", "HK" => "Hong Kong", "HM" => "Heard Island and McDonald Islands", "HN" => "Honduras", "HR" => "Croatia", "HT" => "Haiti", "HU" => "Hungary", "ID" => "Indonesia", "IE" => "Ireland", "IL" => "Israel", "IN" => "India",
            "IO" => "British Indian Ocean Territory", "IQ" => "Iraq", "IR" => "Islamic Republic of Iran", "IS" => "Iceland", "IT" => "Italy", "JM" => "Jamaica", "JP" => "Japan", "KE" => "Kenya", "KG" => "Kyrgyzstan", "KH" => "Cambodia", "KI" => "Kiribati",
            "KM" => "Comoros", "KN" => "Saint Kitts and Nevis", "KP" => "Democratic People's Republic of Korea", "KR" => "Republic of Korea", "KW" => "Kuwait", "KY" => "Cayman Islands", "KZ" => "Kazakhstan", "LA" => "Lao People's Democratic Republic",
            "LB" => "Lebanon", "LC" => "Saint Lucia", "LI" => "Liechtenstein", "LK" => "Sri Lanka", "LR" => "Liberia", "LS" => "Lesotho", "LT" => "Lithuania", "LU" => "Luxembourg", "LV" => "Latvia", "LY" => "Libyan Arab Jamahiriya", "MA" => "Morocco",
            "MC" => "Monaco", "MD" => "Republic of Moldova", "MG" => "Madagascar", "MH" => "Marshall Islands", "MK" => "The Former Yugoslav Republic of Macedonia", "ML" => "Mali", "MM" => "Myanmar", "MN" => "Mongolia", "MO" => "Macao", "MP" => "Northern Mariana Islands",
            "MQ" => "Martinique", "MR" => "Mauritania", "MS" => "Montserrat", "MT" => "Malta", "MU" => "Mauritius", "MV" => "Maldives", "MW" => "Malawi", "MX" => "Mexico", "MY" => "Malaysia", "MZ" => "Mozambique", "NA" => "Namibia", "NC" => "New Caledonia",
            "NE" => "Niger", "NF" => "Norfolk Island", "NG" => "Nigeria", "NI" => "Nicaragua", "NL" => "Netherlands", "NO" => "Norway", "NP" => "Nepal", "NR" => "Nauru", "NU" => "Niue", "NZ" => "New Zealand", "OM" => "Oman", "PA" => "Panama", "PE" => "Peru",
            "PF" => "French Polynesia., Includes Tahiti", "PG" => "Papua New Guinea", "PH" => "Philippines", "PK" => "Pakistan", "PL" => "Poland", "PM" => "Saint Pierre and Miquelon", "PN" => "Pitcairn", "PR" => "Puerto Rico", "PS" => "Palestinian territory, Occupied",
            "PT" => "Portugal", "PW" => "Palau", "PY" => "Paraguay", "QA" => "Qatar", "RE" => "Reunion", "RO" => "Romania", "RU" => "Russian Federation", "RW" => "Rwanda", "SA" => "Saudi Arabia", "SB" => "Solomon Islands", "SC" => "Seychelles",
            "SD" => "Sudan", "SE" => "Sweden", "SG" => "Singapore", "SH" => "Saint Helena", "SI" => "Slovenia", "SJ" => "Svalbard and Jan Mayen", "SK" => "Slovakia", "SL" => "Sierra Leone", "SM" => "San Marino", "SN" => "Senegal", "SO" => "Somalia",
            "SR" => "Suriname", "ST" => "Sao Tome and Principe", "SV" => "El Salvador", "SY" => "Syrian Arab Republic", "SZ" => "Swaziland", "TC" => "Turks and Caicos Islands", "TD" => "Chad", "TF" => "French Southern Territories", "TG" => "Togo",
            "TH" => "Thailand", "TJ" => "Tajikistan", "TK" => "Tokelau", "TM" => "Turkmenistan", "TN" => "Tunisia", "TO" => "Tonga", "TR" => "Turkey", "TT" => "Trinidad and Tobago", "TV" => "Tuvalu", "TW" => "Taiwan, Province of China", "TZ" => "Tanzania, United Republic of",
            "UA" => "Ukraine", "UG" => "Uganda", "UM" => "United States", "US" => "United States", "UY" => "Uruguay", "UZ" => "Uzbekistan", "VA" => "Holy See (Vatican City state)", "VC" => "Saint Vincent and the Grenadines", "VE" => "Venezuela",
            "VG" => "Virgin Islands, British", "VI" => "Virgin Islands, U.S", "VN" => "Vietnam", "VU" => "Vanuatu", "WF" => "Wallis and Futuna", "WS" => "Samoa", "YE" => "Yemen", "YT" => "Mayotte", "ZA" => "South Africa", "ZM" => "Zambia", "ZW" => "Zimbabwe",
        );
        
        $return_country = "United States";
        if (isset($EBAY_COUNTRY_LIST[$country])) {
            $return_country = $EBAY_COUNTRY_LIST[$country];
        }
        return $return_country;
    }
    
    function get_currency_string($currency) {
        $EBAY_CURRENCY_LIST = array(
            "USD" => "$",
            "GBP" => "£",
            "EUR" => "€"
        );

        $return_string = $currency;
        if (isset($EBAY_CURRENCY_LIST[$currency])) {
            $return_string = $EBAY_CURRENCY_LIST[$currency];
        }
        return $return_string;
    }

    function get_find_advanced_items_by_shop_url($name, $country, $page_no = 1) {
        $return_url = '';
        if ($country == "EBAY-US") {
            $return_url = EBAY_US_URL;
        } else if ($country == "EBAY-GB") {
            $return_url = EBAY_UK_URL;
        }
        $return_url = $return_url . "/" . EBAY_SHOP_URL;
        $return_url = $return_url . "/" . $name;
        $return_url = $return_url . "?&_pgn=" . $page_no . "&rt=nc";
        
        return $return_url;
    }

    function get_find_advanced_items_by_name_url($name, $country, $page_no = 1) {
        /*
            https://www.ebay.co.uk/sch/m.html?_nkw=&_armrs=1&_from=&_fcid=3&_sop=10&_ssn=jwhitetrad&_pgn=1&_ipg=200&rt=nc
        */
        $return_url = '';
        if ($country == "EBAY-US") {
            $return_url = EBAY_SELLER_ITEM_US_URL . "0";
        } else if ($country == "EBAY-GB") {
            $return_url = EBAY_SELLER_ITEM_UK_URL . "3";
        }
        $return_url = $return_url . EBAY_SELLER_ITEM_URL;
        $return_url = $return_url . $name;
        $return_url = $return_url . "&_pgn=" . $page_no;
        $return_url = $return_url . "&_ipg=" . EBAY_SELLER_ITEM_PER_PAGE . "&rt=nc";
        return $return_url;
    }

    function get_item_copies_url($country, $item_id, $title) {
        /*
            "https://{{host}}/sch/i.html?_nkw={{name}}&_clu=2&_fcid={{countryId}}&_localstpos&_stpos&gbr=1&LH_BIN=1"
        */
        $return_url = '';
        if ($country == "EBAY-US") {
            $return_url = EBAY_US_URL;
        } else if ($country == "EBAY-GB") {
            $return_url = EBAY_UK_URL;
        }
        $return_url = $return_url . "/sch/i.html?_nkw=";
        $encode_title = urlencode($title);
        $encode_title = str_replace('--', '-', $encode_title);
        $encode_title = str_replace('-', '+', $encode_title);
        $return_url = $return_url . $encode_title;
        $return_url = $return_url . "&_clu=2&_fcid=";
        if ($country == "EBAY-US") {
            $return_url = $return_url . "0";
        } else if ($country == "EBAY-GB") {
            $return_url = $return_url . "3";
        }
        $return_url = $return_url . "&_localstpos&_stpos&gbr=1&LH_BIN=1";
        $return_url = $return_url . "&_=" . $item_id;
        
        return $return_url;
    }

    /*
        INSERT INTO ds_ebay_items_test (item_id, seller_id, title, category_id, category_name, url, image, location, currency, shipping, price, listing, condition_id, condition_name, is_multi, checked) 
        VALUES ('391751487965', '2', "Hot British Men's Casual Suede Lace Ankle Boots High Top Loafers Sneakers Shoes", '24087', 'Casual', 
        'http://www.ebay.com/itm/Hot-British-Mens-Casual-Suede-Lace-Ankle-Boots-High-Top-Loafers-Sneakers-Shoes-/391751487965?var=0', 'http://thumbs1.ebaystatic.com/pict/04040_0.jpg', 
        'China', 'USD', 'Free', '59.99', 'FixedPrice', '1000', 'New with box', '1', '1')
    */

    function get_item_info_url_by_id($item_id, $app_no = 1) {
        /*
            http://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=JSON&appid=YourAPPNAME&siteid=0
            &version=967&ItemID=382160977388&IncludeSelector=TextDescription,Details
        */
        
        if ($app_no == 1) {
            $ebay_app_name = EBAY_APP_NAME_01;
        } else if ($app_no == 2) {
            $ebay_app_name = EBAY_APP_NAME_02;
        } else if ($app_no == 3) {
            $ebay_app_name = EBAY_APP_NAME_03;
        } else if ($app_no == 4) {
            $ebay_app_name = EBAY_APP_NAME_04;
        } else if ($app_no == 5) {
            $ebay_app_name = EBAY_APP_NAME_05;
        } else if ($app_no == 6) {
            $ebay_app_name = EBAY_APP_NAME_06;
        } else if ($app_no == 7) {
            $ebay_app_name = EBAY_APP_NAME_07;
        } else if ($app_no == 8) {
            $ebay_app_name = EBAY_APP_NAME_08;
        } else if ($app_no == 9) {
            $ebay_app_name = EBAY_APP_NAME_09;
        } else if ($app_no == 10) {
            $ebay_app_name = EBAY_APP_NAME_10;
        } else if ($app_no == 11) {
            $ebay_app_name = EBAY_APP_NAME_11;
        } else if ($app_no == 12) {
            $ebay_app_name = EBAY_APP_NAME_12;
        } else if ($app_no == 13) {
            $ebay_app_name = EBAY_APP_NAME_13;
        } else if ($app_no == 14) {
            $ebay_app_name = EBAY_APP_NAME_14;
        } else if ($app_no == 15) {
            $ebay_app_name = EBAY_APP_NAME_15;
        } else if ($app_no == 16) {
            $ebay_app_name = EBAY_APP_NAME_16;
        } else if ($app_no == 17) {
            $ebay_app_name = EBAY_APP_NAME_17;
        } else if ($app_no == 18) {
            $ebay_app_name = EBAY_APP_NAME_18;
        } else if ($app_no == 19) {
            $ebay_app_name = EBAY_APP_NAME_19;
        } else if ($app_no == 20) {
            $ebay_app_name = EBAY_APP_NAME_20;
        } else if ($app_no == 21) {
            $ebay_app_name = EBAY_APP_NAME_21;
        } else if ($app_no == 22) {
            $ebay_app_name = EBAY_APP_NAME_22;
        } else if ($app_no == 23) {
            $ebay_app_name = EBAY_APP_NAME_23;
        } else {
            $ebay_app_name = EBAY_APP_NAME_01;
        }
        
        $return_url = EBAY_ITEM_URL;
        $return_url = $return_url . $ebay_app_name;
        $return_url = $return_url . "&siteid=0&version=1073&ItemID=";
        $return_url = $return_url . $item_id;
        $return_url = $return_url . EBAY_ITEM_INCLUDE_URL;
        
        return $return_url;
    }

    function get_item_trans_url($item_id) {
        /*
            https://offer.ebay.com/ws/eBayISAPI.dll?ViewBidsLogin&item=163109237539&rt=nc&_trksid=p2047675.l2564
        */
        $return_url = EBAY_ITEM_TRANSACTION_URL1 . $item_id . EBAY_ITEM_TRANSACTION_URL2;
        return $return_url;
    }

	function get_item_trans_req_head($country, $app_no = 1) {
        /*
            X-EBAY-API-SITEID:3
            X-EBAY-API-COMPATIBILITY-LEVEL:1026
            X-EBAY-API-CALL-NAME:GetItemTransactions
            X-EBAY-API-APP-NAME:YOUR APP NAME
            X-EBAY-API-DEV-NAMEE:YOUR DEV NAME
            X-EBAY-API-CERT-NAME:YOUR CERT NAME
        */
        
        if ($app_no == 1) {
            $ebay_app_name = EBAY_APP_NAME_01;
            $ebay_dev_name = EBAY_DEV_NAME_01;
            $ebay_cert_name = EBAY_CERT_NAME_01;
        } else if ($app_no == 2) {
            $ebay_app_name = EBAY_APP_NAME_02;
            $ebay_dev_name = EBAY_DEV_NAME_02;
            $ebay_cert_name = EBAY_CERT_NAME_02;
        } else if ($app_no == 3) {
            $ebay_app_name = EBAY_APP_NAME_03;
            $ebay_dev_name = EBAY_DEV_NAME_03;
            $ebay_cert_name = EBAY_CERT_NAME_03;
        } else {            
            $ebay_app_name = EBAY_APP_NAME_01;
            $ebay_dev_name = EBAY_DEV_NAME_01;
            $ebay_cert_name = EBAY_CERT_NAME_01;
        }
        
        if ($country == "EBAY-GB") {
            $html_request_head = array("X-EBAY-API-SITEID:3",
                    "X-EBAY-API-COMPATIBILITY-LEVEL:" . EBAY_API_VERSION,
                    "X-EBAY-API-CALL-NAME:" . "GetItemTransactions",
                    "X-EBAY-API-APP-NAME:" . $ebay_app_name,
                    "X-EBAY-API-DEV-NAME:" . $ebay_dev_name,
                    "X-EBAY-API-CERT-NAME:". $ebay_cert_name);
        } else if ($country == "EBAY-US") {
            $html_request_head = array("X-EBAY-API-SITEID:0",
                    "X-EBAY-API-COMPATIBILITY-LEVEL:" . EBAY_API_VERSION,
                    "X-EBAY-API-CALL-NAME:" . "GetItemTransactions",
                    "X-EBAY-API-APP-NAME:" . $ebay_app_name,
                    "X-EBAY-API-DEV-NAME:" . $ebay_dev_name,
                    "X-EBAY-API-CERT-NAME:". $ebay_cert_name);
        }
        
        return $html_request_head;
	}
    
	function get_item_trans_req_body($item_id, $period, $page_no, $app_no = 1) {
        /*
            <?xml version="1.0" encoding="utf-8"?>
            <GetItemTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                <RequesterCredentials>
                    <eBayAuthToken>
                    YOUR TOKEN NAME
                    </eBayAuthToken>
                </RequesterCredentials>
                <ErrorLanguage>en_US</ErrorLanguage>
                <WarningLevel>High</WarningLevel>
                <ItemID>183093892055</ItemID>
                <DetailLevel>ReturnAll</DetailLevel>
                <NumberOfDays>7</NumberOfDays>
                <OutputSelector>ItemID</OutputSelector>
                <OutputSelector>ConvertedTransactionPrice</OutputSelector>
                <OutputSelector>CreatedDate</OutputSelector>
                <OutputSelector>QuantityPurchased</OutputSelector>
                <OutputSelector>TransactionPrice</OutputSelector>
                <OutputSelector>PaginationResult</OutputSelector>
            </GetItemTransactionsRequest>
        */
        
        if ($app_no == 1) {
            $ebay_token = EBAY_TOKEN_01;
        } else if ($app_no == 2) {
            $ebay_token = EBAY_TOKEN_02;
        } else if ($app_no == 3) {
            $ebay_token = EBAY_TOKEN_03;
        }
        
        $html_request_body = '<?xml version="1.0" encoding="utf-8"?>
            <GetItemTransactionsRequest xmlns="urn:ebay:apis:eBLBaseComponents">
                <RequesterCredentials>
                    <eBayAuthToken>' . $ebay_token . '</eBayAuthToken>
                </RequesterCredentials>
                <ErrorLanguage>en_US</ErrorLanguage>
                <WarningLevel>High</WarningLevel>
                <ItemID>' . $item_id . '</ItemID>
                <NumberOfDays>' . $period . '</NumberOfDays>
                <Pagination>
                    <EntriesPerPage>200</EntriesPerPage>
                    <PageNumber>' . $page_no . '</PageNumber>
                </Pagination>
                <OutputSelector>ItemID</OutputSelector>
                <OutputSelector>ConvertedTransactionPrice</OutputSelector>
                <OutputSelector>CreatedDate</OutputSelector>
                <OutputSelector>QuantityPurchased</OutputSelector>
                <OutputSelector>TransactionPrice</OutputSelector>
                <OutputSelector>PaginationResult</OutputSelector>
            </GetItemTransactionsRequest>';
            
        return $html_request_body;
    }
   
    function get_find_items_url($conditions, $app_no = 1) {
        $url = "";
        if (is_array($conditions)) {
            $url = EBAY_FIND_URL;
            $url .= $conditions['country'];
            $url .= '&keywords=' . urlencode($conditions['keyword']);
            if ($conditions['category'] != '0')
            {
                $url .= '&categoryId=' . $conditions['category'];
            }
            $url .= '&sortOrder=BestMatch';
            $url .= '&itemFilter(0).name=ListingType';
            $url .= '&itemFilter(0).value=FixedPrice';
            $url .= '&itemFilter(1).name=MinPrice';
            $url .= '&itemFilter(1).value=' . $conditions['price_min'];
            $url .= '&itemFilter(2).name=MaxPrice';
            $url .= '&itemFilter(2).value=' . $conditions['price_max'];
            $url .= '&itemFilter(3).name=FeedbackScoreMin';
            $url .= '&itemFilter(3).value=' . $conditions['fb_min'];
            $url .= '&itemFilter(4).name=FeedbackScoreMax';
            $url .= '&itemFilter(4).value=' . $conditions['fb_max'];
            $url .= '&itemFilter(5).name=HideDuplicateItems';
            $url .= '&itemFilter(5).value=true';
            $url .= '&itemFilter(6).name=FreeShippingOnly';
            $url .= '&itemFilter(6).value=true';
            $url .= '&itemFilter(7).name=Currency';
            if ($conditions['country'] == 'EBAY-US')
            {
                $url .= '&itemFilter(7).value=USD';
            } else {
                $url .= '&itemFilter(7).value=GBP';
            }
            $url .= '&itemFilter(8).name=Condition';
            if($conditions['cond_type'] != '0')
            {
                $url .= '&itemFilter(8).value='. $conditions['cond_type'];
            } else {
                $url .= '&itemFilter(8).value(0)=1000';
                $url .= '&itemFilter(8).value(1)=1500';
            }
            // $iFilter = 8;
            // if ($conditions['quantity_min'])
            // {
            //     $iFilter++;
            //     $url .= '&itemFilter('.$iFilter.').name=MinQuantity';
            //     $url .= '&itemFilter('.$iFilter.').value='. $conditions['quantity_min'];
            // }
            // if ($conditions['quantity_max'])
            // {
            //     $iFilter++;
            //     $url .= '&itemFilter('.$iFilter.').name=MaxQuantity';
            //     $url .= '&itemFilter('.$iFilter.').value='. $conditions['quantity_max'];
            // }
            if ($conditions['per_page'] == 0)
            {
                $url .= '&paginationInput.entriesPerPage=100';
            } else if ($conditions['per_page'] < 100) {
                $url .= '&paginationInput.entriesPerPage=' . $conditions['per_page'];
            } else {
                $url .= '&paginationInput.entriesPerPage=100';
            }
            
            if ($conditions['page_num'] > 0)
            {
                $url .= '&paginationInput.pageNumber=' . $conditions['page_num'];
            } else {
                $url .= '&paginationInput.pageNumber=1';
            }
            $url .= '&descriptionSearch=true';
            
            if ($app_no == 1) {
                $url .= '&security-appname='. EBAY_APP_NAME_01;
            } else if ($app_no == 2) {
                $url .= '&security-appname='. EBAY_APP_NAME_02;
            } else if ($app_no == 3) {
                $url .= '&security-appname='. EBAY_APP_NAME_03;
            } else if ($app_no == 4) {
                $url .= '&security-appname='. EBAY_APP_NAME_04;
            } else if ($app_no == 5) {
                $url .= '&security-appname='. EBAY_APP_NAME_05;
            } else if ($app_no == 6) {
                $url .= '&security-appname='. EBAY_APP_NAME_06;
            } else if ($app_no == 7) {
                $url .= '&security-appname='. EBAY_APP_NAME_07;
            } else if ($app_no == 8) {
                $url .= '&security-appname='. EBAY_APP_NAME_08;
            } else if ($app_no == 9) {
                $url .= '&security-appname='. EBAY_APP_NAME_09;
            } else if ($app_no == 10) {
                $url .= '&security-appname='. EBAY_APP_NAME_10;
            } else if ($app_no == 11) {
                $url .= '&security-appname='. EBAY_APP_NAME_11;
            } else if ($app_no == 12) {
                $url .= '&security-appname='. EBAY_APP_NAME_12;
            } else if ($app_no == 13) {
                $url .= '&security-appname='. EBAY_APP_NAME_13;
            } else if ($app_no == 14) {
                $url .= '&security-appname='. EBAY_APP_NAME_14;
            } else if ($app_no == 15) {
                $url .= '&security-appname='. EBAY_APP_NAME_15;
            } else if ($app_no == 16) {
                $url .= '&security-appname='. EBAY_APP_NAME_16;
            } else if ($app_no == 17) {
                $url .= '&security-appname='. EBAY_APP_NAME_17;
            } else if ($app_no == 18) {
                $url .= '&security-appname='. EBAY_APP_NAME_18;
            } else if ($app_no == 19) {
                $url .= '&security-appname='. EBAY_APP_NAME_19;
            } else if ($app_no == 20) {
                $url .= '&security-appname='. EBAY_APP_NAME_20;
            } else if ($app_no == 21) {
                $url .= '&security-appname='. EBAY_APP_NAME_21;
            } else if ($app_no == 22) {
                $url .= '&security-appname='. EBAY_APP_NAME_22;
            } else if ($app_no == 23) {
                $url .= '&security-appname='. EBAY_APP_NAME_23;
            } else {
                $url .= '&security-appname='. EBAY_APP_NAME_23;
            }
            
            if (isset($conditions['format']))
            {
                $url .= '&response-data-format=' . $conditions['format'];
            } else {
                $url .= '&response-data-format=json';
            }
        }
        return $url;
    }
?>