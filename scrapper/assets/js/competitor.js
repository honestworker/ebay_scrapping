jQuery(document).ready(function() {

    var STATE = {
		days: 0
    }
    
	var ERRORS = {
		'seller_not_found': 'One or more of the seller User IDs you entered was not found.',
		'items_out_limit': 'The seller has more than 7000 items.',
		'sales_error': 'An error occurred during the analysis. We are already working on its correction. Please try again later.'
	}

	function infoCR(message) {
		$('.alerts').html('<div class="callout callout-info"><p>' + message + '</p></div>');
	}

	function errorCR(message) { 
		$('.alerts').html('<div class="callout callout-danger"><p>' + message+  '</p></div>');
    }
    
    var SCRAPER_API = BASE_URL + 'scraper/init.php';
    
	function sendAPI(data, callback) {
		$.post(SCRAPER_API, data, function(resp) {
			var json = JSON.parse(resp);
			callback(json);
		});
    }

    var seller_name = '';

	if ($('.search-competitor-username').length > 0 && document.location.hash) {
        seller_name = document.location.hash.replace('#', '');
        $('.search-competitor-username').val(seller_name);
        
	}

    $('.competitor-filter input').change(function() {
        if($.fn.dataTable.isDataTable( '#items' )) {
            $('#items').DataTable().draw();
        }
    });
    
    var table = null;
    $('body').on('click', '.show-competition', function(){
		$('#modal-competition').modal('show');
		$('.competition-spinner').show();
		$('.competition-table').hide();
		sendAPI({action: 'get_competition_urls', item_id: $(this).attr('data-item-id')}, function(resp) {
		    if (table != null) {
				$('.competition-table').hide();
                $('.items-competition-table tr').remove();
                table.clear();
		    } else {
    			table = $('#competition-items')
    			.on('init.dt', function() {
                })
                .DataTable({
                    'destroy'       : true,
    			    'paging'        : true,
    			    'ordering'      : true,
    			    'order'         : [[ 2, "desc" ]],
    			    'sDom'          : '<"row"<"col-md-6"l><"col-md-6"f>><"row"i>rtp'
    			})
    			.on('draw', function(e) {
    				var comp_items = $('.items-competition-table tr'), item_index = 0;
        		    for (item_index = 0; item_index < comp_items.length; item_index++) {
        		        if (comp_items[item_index].childNodes[5].innerHTML == seller_name) {
        		            comp_items[item_index].setAttribute('class', comp_items[item_index].className + ' selected');
        		        }
        		    }
                });
            }
			
			var t = [];
			
			for( var i = 0; i < resp['data'].length; i++ ) {
				var item = resp['data'][i];
                t.push(['<img src="' + item['image'] +'">', '<a href="'+ item['url'] + '" target="_blank">' + item['title'] + '</a>', item['total_sold'], item['price'], item['dirty_price'], item['seller_name']])
			}
			
			table.rows.add(t).draw();
			$('.competition-spinner').hide();
			$('.competition-table').show();
		});
	});
    
	function checkWatching( seller_id ) {
		$.getJSON(BASE_URL + 'index.php/api/watchlist_check/' + seller_id, function(resp) {
			if( resp['watching'] ) {
				$('.btn-add-watchlist').hide();
				$('.alert-watchlist-added').show();
			}
		});
    }
    
    $.fn.dataTable.ext.search.push(
        function( settings, data, dataIndex ) {
            if (data.length < 7){
                return true;
            }
            
            var minSales = parseInt($('.competitor-filter-sales-min').val()) || 0;
            if (minSales > data[2]){
                return false;
            }
            
            var maxSales = parseInt($('.competitor-filter-sales-max').val()) || false;
            if (maxSales && maxSales < data[2]){
                return false;
            }
            
            var minTotal = parseInt($('.competitor-filter-total-min').val()) || 0;
            if (minTotal > data[3]) {
                return false;
            }
            
            var maxTotal = parseInt($('.competitor-filter-total-max').val()) || false;
            if (maxTotal && maxTotal < data[3]) {
                return false;
            }
            var price = data[5].match(/[\d\.\,]+/)[0];
            
            var minComp = parseInt($('.competitor-filter-price-min').val()) || 0;
            if (minComp > price) {
                return false;
            }
            
            var maxComp = parseInt($('.competitor-filter-price-max').val()) || false;
            if (maxComp && maxComp < price) {
                return false;
            }
            
            var parts = data[4].split('/');
            var cell_date = new Date(parts[2], parts[1]-1, parts[0]);
            var today = new Date();
            var diffTime = Math.abs( today.getTime() - cell_date.getTime());
            var diffDays = Math.floor( diffTime / (1000 * 3600 * 24));

            var minUpload = parseInt($('.competitor-filter-upload-min').val()) || 0;
            if (minUpload > diffDays) {
                return false;
            }
            
            var maxUpload = parseInt($('.competitor-filter-upload-max').val()) || false;
            if (maxUpload && maxUpload < diffDays) {
                return false;
            }
            
            return true;
        }
    );
    
	function view_seller_info(seller_data) {
        $('[data-key="sell_rate"]').html(seller_data['sell_rate']);
        $('[data-key="sold_items"]').html(seller_data['sold_items']);
        $('[data-key="items_count"]').html(seller_data['items_count']);
        $('[data-key="sale_earnings"]').html(seller_data['sale_earnings']);
        $('[data-key="feedback_score"]').html(seller_data['feedback_score']);
        $('[data-key="feedback_ratio"]').html(seller_data['feedback_ratio']);
        $('[data-key="country"]').html(seller_data['country']);
        $('.seller-badges').show();
        
        $('.header-sales').html(STATE.days + ' Days Sales');
        
        checkWatching(seller_data['seller_id']);

        if ($.fn.dataTable.isDataTable( '#items' )) {
            $('.results').hide();
            $('#items').DataTable().destroy();
            $('.items-table tr').remove();
        }
        
        $('.watchlist-seller-id').val(seller_data['seller_id']);
        
        var table = $('#items').DataTable({
            'paging'      : true,
            'ordering'    : true,
            "order": [[ 2, "desc" ]],
            sDom:'<"row"<"col-md-6"l><"col-md-6"f>><"row"i>rtp',
            "columnDefs": [
                { "type": "date-eu", targets: 4 }
            ]
        });
        
        var t = [];
        
        for (var i = 0; i < seller_data['items'].length; i++) {
            var item = seller_data['items'][i];
            //t.push(['<img src="' + item['image'] + '">', '<a href="' + item['url'] + '" target="_blank">' + item['title'] + '</a>', item['period_sold'], item['total_sold'], item['upload_date'].split('-').reverse().join('/'), '<span>' + item['price'] + '</span>', '<a href="#" class="btn btn-xs btn-info show-competition" data-item-id="' + item['item_id'] + '"><i class="glyphicon glyphicon-eye-open"></i> Competition ' + item['copies_count'] + '</a>'])
            t.push(['<img src="' + item['image'] + '">', '<a href="' + item['url'] + '" target="_blank">' + item['title'] + '</a>', item['period_sold'], item['total_sold'], item['upload_date'], '<span>' + item['price'] + '</span>', '<a href="#" class="btn btn-xs btn-info show-competition" data-item-id="' + item['item_id'] + '"><i class="glyphicon glyphicon-eye-open"></i> Competition ' + item['copies_count'] + '</a>'])
        }
        
        table.rows.add(t).draw();
        
        $('.results').show();
        $('.btn-search-competitor').removeAttr('disabled');
        $('.alerts .callout').remove();
    }

    function track_seller_info(name) {
        sendAPI({action: 'track_seller_info', name: name}, function(resp) {

        });
    }

    function get_seller_info(name, range) {
		setTimeout(function() {
			sendAPI({action: 'get_seller_info', name: name, period: range}, function(resp) {
				if (resp['status'] == 'success') {
                    view_seller_info(resp['data']);
                } else if (resp['status'] == 'pending') {
                    $('[data-key="sell_rate"]').html(resp['data']['sell_rate']);
                    $('[data-key="sold_items"]').html(resp['data']['sold_items']);
                    $('[data-key="items_count"]').html(resp['data']['items_count']);
                    $('[data-key="sale_earnings"]').html(resp['data']['sale_earnings']);
                    $('[data-key="feedback_score"]').html(resp['data']['feedback_score']);
                    $('[data-key="feedback_ratio"]').html(resp['data']['feedback_ratio']);
                    $('[data-key="country"]').html(resp['data']['country']);
                    $('.seller-badges').show();
                    get_seller_info(name, range);
				} else if (resp['status'] == 'pending_init') {
                    get_seller_info(name, range);
                } else {
                    errorCR(ERRORS['sales_error']);
                    $('.seller-badges').hide();
                    $('.btn-search-competitor').removeAttr('disabled');
				}
			})
		}, 5000);
    }
    
	$('.btn-search-competitor').click(function() {
		$(this).attr('disabled', 'disabled');
		$('.results').hide();  // datatable
		$('.alerts .callout').remove();
		$('.alert-watchlist-added').hide();  // view watchlist button
		$('.btn-add-watchlist').show(); // add watchlist button
		$('.seller-badges').hide(); // seller information result
		
		$('[data-key]').html('---');
		if( $.fn.dataTable.isDataTable( '#items' ) ) {
			$('#items').DataTable().destroy();
			$('.items-table tr').remove();
		}
		
		seller_name = $('.search-competitor-username').val();
		var range = $('.range').val();
		
		STATE.days = range;
		$('.header-sales').html(STATE.days + ' Days Sales');
		
        infoCR('Your request for analysis has been saved.<br>Do not close the tab to monitor the progress of the analysis.');
        
        sendAPI({'action': 'competitor_research', 'name': seller_name, 'period': range}, function(resp) {
			if (resp['status'] == 'failure') {
                errorCR(ERRORS['seller_not_found']);
			} else if (resp['status'] == 'success') {
                var seller_data = resp['data'];
                view_seller_info(seller_data);
                $(this).removeAttr('disabled');
			} else if (resp['status'] == 'pending_init') {
                track_seller_info(seller_name);
                get_seller_info(seller_name, range);
            } else if (resp['status'] == 'pending') {
                get_seller_info(seller_name, range);
            }
        })
    });
});