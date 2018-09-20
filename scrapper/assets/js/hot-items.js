jQuery(document).ready(function() {
    
    var HOTITEM_PATH = BASE_URL + "scraper/init.php";
    var country = "EBAY-GB";
    var item_count = 0;
    var item_count_threshold = 100;
    
    parent.hideloader();
    
    $('#county_gb').click(function() {
        country = "EBAY-GB";
    });
    
    $('#county_us').click(function() {
        country = "EBAY-US";
    });
    
    var data_table = $('#hot_items_table').DataTable({
        dom: 'Bfrtip',
		sDom: '<"row"<"col-md-6"l><"col-md-6"f>><"row"i>rtp',
        buttons: [
            {
                extend: 'copyHtml5',
            },
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5'
        ],
        columnDefs: [
            { width: '5%', targets: 0 },
            { width: '10%', targets: 1 },
            { width: '26%', targets: 2 },
            { width: '10%', targets: 3 },
            { width: '10%', targets: 4 },
            { width: '8%', targets: 5 },
            { width: '8%', targets: 6 },
            { width: '8%',  targets: 7 },
            { width: '5%',  targets: 8 },
            { width: '10%', targets: 9 },
        ],
	    'paging'      : true,
	    'ordering'    : true,
	    'destory'     : true,
    });
    $('#hot_items_body').hide();
    
    $('#fast_scan').click(function() {
        var keyword = $("#keyword").val();
        var category = $("#category").val();
        var fb_min = $( "#fb_min" ).val();
        var fb_max = $( "#fb_max" ).val();
        var price_min = $( "#price_min" ).val();
        var price_max = $( "#price_max" ).val();
        var quantity_min = $( "#quantity_min" ).val();
        var quantity_max = $( "#quantity_max" ).val();
        var sold_min = $( "#sold_min" ).val();
        var sold_max = $( "#sold_max" ).val();
        //var cond_type = $("#cond_type").val();
        
        //alert(keyword + ":" + category + ":" + fb_min + ":" + fb_max + ":" + price_min + ":" + price_max + ":" + quantity_min + ":" + quantity_max + ":" + sold_min + ":" + sold_max + ":" + cond_type);
        if(keyword !== "")
        {
            parent.showloader();
            data = { 'action' : 'fast_scan', 'keyword' : keyword, 'category' : category, 'fb_min' : fb_min, 'fb_max' : fb_max,
                    'price_min' : price_min, 'price_max' : price_max, 'quantity_min' : quantity_min, 'quantity_max' : quantity_max,
                    'sold_min' : sold_min, 'sold_max' : sold_max, 'cond_type' : 0, 'country' : country };
            $.post(HOTITEM_PATH, data, function( resp ) {
                var result = JSON.parse(resp);
                parent.hideloader();
                if (result.status) {
                    $('#item_count').val(result.data);
                }
            });
            $('#find_items').attr('disabled', false);
        } else {
            alert("Please insert the keyword!");
        }
    })
    
    $('#find_items').click(function() {        
        var keyword = $("#keyword").val();
        var category = $("#category").val();
        var fb_min = $( "#fb_min" ).val();
        var fb_max = $( "#fb_max" ).val();
        var price_min = $( "#price_min" ).val();
        var price_max = $( "#price_max" ).val();
        var quantity_min = $( "#quantity_min" ).val();
        var quantity_max = $( "#quantity_max" ).val();
        var sold_min = $( "#sold_min" ).val();
        var sold_max = $( "#sold_max" ).val();
        var sold_period = $("#sold_period").val();
        item_count = $("#item_count").val();
        var cond_type = 0;
        
        data = { 'action' : 'find_hot_items', 'keyword' : keyword, 'category' : category, 'fb_min' : fb_min, 'fb_max' : fb_max,
                'price_min' : price_min, 'price_max' : price_max, 'quantity_min' : quantity_min, 'quantity_max' : quantity_max,
                'sold_min' : sold_min, 'sold_max' : sold_max, 'cond_type' : cond_type, 'country' : country, 'sold_period' : sold_period, 'item_count' : item_count };
        
        //alert(keyword + ":" + category + ":" + fb_min + ":" + fb_max + ":" + price_min + ":" + price_max + ":" + quantity_min + ":" + quantity_max + ":" + sold_min + ":" + sold_max + ":" + cond_type + ":" + country + ":" + sold_period + ":" + item_count);
        if (keyword !== "")
        {
            if ($("#item_count").val() <= item_count_threshold) {
                $.getJSON(BASE_URL + 'index.php/api/check_api_available/', function(resp1) {
                    if (resp1 === -1) {
                        alert("You have already done the limited counts per one day.\nYou can do it after 24 hours.\nIf you want to increase the limited counts, please update your membership level!");
                    } else if (resp1 === -2) {
                        alert("You have already done the limited counts per one day.\nYou can do it after 24 hours.\n");
                    } else if (resp1 >= 0) {
                        parent.showloader();
                        $('#hot_items_body').addClass('hidden');
                        $('#hot_items_body').css('display', 'none');
                        $.post(HOTITEM_PATH, data, function( resp ) {
                            var json_result = JSON.parse(resp);
                            data_table.rows().remove();
                            var t = [];
                            if (json_result['status'] == 'success') {
                                if (json_result['data']) {
                                    if (json_result['data'] != "Scrap Error") {
                                        for( var i = 0; i < json_result['data'].length; i++ ) {
                                            var item = json_result['data'][i];
                                            t.push([i + 1, '<img src=' + item['image'] + '>', item['title'] + "<button data-id='" + item['title'] + "' class='item_title'>Copy Title</button>",
                                            item['category'], '<a href=\"https://ds-tools.eu/index.php/account/competitor-research#' + item['seller'] + '\" target=\"_blank\">' + item['seller'] + '</a>',
                                            item['seller_feedback'], item['sold'], item['price'], item['currency'], '<a class=\"btn btn-sm btn-primary\" href=\"' + item['url'] + '\" target=\"_blank\">View on eBay']);
                                        }
                                        data_table.rows.add(t).draw(true);
                                        $('.item_title').click(function(e) {
                                            const el = document.createElement('textarea');
                                            el.value = this.attributes['data-id'].value;
                                            document.body.appendChild(el);
                                            el.select();
                                            document.execCommand('copy');
                                            document.body.removeChild(el);
                                            alert("Copy Success! Paste it at eBay List Title.");
                                        })
                                    } else {
                                        alert("Scrap processing occure Error!")
                                    }
                                }
                            } else {
                                alert(json_result['data']);
                            }
                            $('#hot_items_body').removeClass('hidden');
                            $('#hot_items_body').css('display', 'block');
                            parent.hideloader();
                            data_table.columns.adjust().draw();
                        });
                    }    
		        });
            } else {
                alert("Please insert the Item Number less than 600!");
            }
        } else {
            alert("Please insert the keyword!");
        }
    });
    
    data_table.on( 'draw', function () {
    });
    
    // item_count = $("#item_count").val();
    // $("#item_count").keyup( function(e) {
    //     var item_count_old = item_count;
    //     if (e.which >= 96 && e.which <= 105) {
    //         if ($("#item_count").val() > item_count_threshold) {
    //             alert("Please input the number less than 600!");
    //             $("#item_count").val(item_count_old);
    //         }
    //     } else if ( e.which == 8 || e.which == 46) {
    //     } else {
    //         $("#item_count").val(item_count_old);
    //     }
    //     item_count = $("#item_count").val();
    // });
});