<?php
    $user_info = $this->users->getById($user_id);
    $group_id = $user_info->groupId;
    $now_date = $trans_before = date("Y-m-d H:i:s");
    $date_before = date("Y-m-d H:i:s", strtotime("$now_date -1 day"));
    $group_name = $this->usergroups->getNameById($group_id);
    $hot_items_update = $user_info->hot_items_update;
    $hot_items_count = $user_info->hot_items_count;
    $hot_items_coutn_label_flag = 1;
    if ($group_name === 'admin') {
        $hot_items_coutn_label_flag = 0;
    } else if ($group_name === 'premium') {
        $limit_count = 10;
    } else if ($group_name === 'professional') {
        $limit_count = 20;
    }
    if ($hot_items_update < $date_before) {
        $hot_items_count = $limit_count;
    }
?>
<div class="row" style="margin-left: 1px; margin-right:1px">
    <div class="row" style="margin-left: 3px; margin-right:3px">
        
        <div class="loader hidden">
            <img src="<?php echo base_url();?>/assets/images/page-loader.gif" />
           
        </div>
        <br />
        <div class="row">
            <div class="col-md-2">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-map-marker"></i><span class="header-sales"> Country</span>
                    </div>
                    <div class="custom-info-box-body">
                        <input type="radio" name="country" value="EBAY-GB" id="county_gb" checked="checked"> UK
                        <input type="radio" name="country" id="county_us"> US
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-search"></i><span class="header-sales"> Keywords</span>
                    </div>
                    <div class="custom-info-box">
                        <input class="form-control" type="username" name="keyword" id="keyword" value="" placeholder="Enter 3-5 keyword to search" required="required" />
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-menu-hamburger"></i><span class="header-sales"> Categories</span>
                    </div>
                    <div class="custom-info-box">
                        <select class="form-control" title="Select a category for search" name="category" id="category" required="required">
                            <option selected="selected" value="0">All Categories</option>
                            <option value="20081">Antiques</option>
                            <option value="550">Art</option>
                            <option value="2984">Baby</option>
                            <option value="267">Books</option>
                            <option value="12576">Business &amp; Industrial</option>
                            <option value="625">Cameras &amp; Photo</option>
                            <option value="15032">Cell Phones &amp; Accessories</option>
                            <option value="11450">Clothing, Shoes &amp; Accessories</option>
                            <option value="11116">Coins &amp; Paper Money</option>
                            <option value="1">Collectibles</option>
                            <option value="58058">Computers/Tablets &amp; Networking</option>
                            <option value="293">Consumer Electronics</option>
                            <option value="14339">Crafts</option>
                            <option value="237">Dolls &amp; Bears</option>
                            <option value="11232">DVDs &amp; Movies</option>
                            <option value="6000">eBay Motors</option>
                            <option value="45100">Entertainment Memorabilia</option>
                            <option value="172008">Gift Cards &amp; Coupons</option>
                            <option value="26395">Health &amp; Beauty</option>
                            <option value="11700">Home &amp; Garden</option>
                            <option value="281">Jewelry &amp; Watches</option>
                            <option value="11233">Music</option>
                            <option value="619">Musical Instruments &amp; Gear</option>
                            <option value="1281">Pet Supplies</option>
                            <option value="870">Pottery &amp; Glass</option>
                            <option value="10542">Real Estate</option>
                            <option value="316">Specialty Services</option>
                            <option value="888">Sporting Goods</option>
                            <option value="64482">Sports Mem, Cards &amp; Fan Shop</option>
                            <option value="260">Stamps</option>
                            <option value="1305">Tickets &amp; Experiences</option>
                            <option value="220">Toys &amp; Hobbies</option>
                            <option value="3252">Travel</option>
                            <option value="1249">Video Games &amp; Consoles</option>
                            <option value="99">Everything Else</option>
                </select>
                    </div>
                </div>
            </div>
            <input type="hidden" name="cat" id="cat">
            
            <div class="col-md-2">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-asterisk"></i><span class="header-sales"> Item Sold</span>
                    </div>
                    <div class="custom-info-box">
                        <select class="form-control" name="sold_period" id="sold_period" required="required">
                            <option value="0">Anyone</option>
                            <option value="7">7 Days</option>
                            <option value="14">14 Days</option>
                            <option value="21">21 Days</option>
                            <option value="30">30 Days</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="fa fa-star"></i><span class="header-sales"> Feedback</span>
                    </div>
                    <div class="custom-info-box-body">
                        <table>
                            <tr>
                                <td class="td-label">Min</td>
                                <td class="td-value"><input type="text" id="fb_min" class="form-control" value="1"></td>
                                <td class="td-label">Max</td>
                                <td class="td-value"><input type="text" id="fb_max" class="form-control" value="9000"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class=" glyphicon glyphicon-usd"></i><span class="header-sales"> Price</span>
                    </div>
                    <div class="custom-info-box-body">
                        <table>
                            <tr>
                                <td class="td-label">Min</td>
                                <td class="td-value"><input type="text" id="price_min"  class="form-control" value="1"></td>
                                <td class="td-label">Max</td>
                                <td class="td-value"><input type="text" id="price_max"  class="form-control" value="500"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-equalizer"></i><span class="header-sales"> Stock Quantity</span>
                    </div>
                    <div class="custom-info-box-body">
                        <table>
                            <tr>
                                <td class="td-label">Min</td>
                                <td class="td-value"><input type="text" id="quantity_min"  class="form-control" value="1"></td>
                                <td class="td-label">Max</td>
                                <td class="td-value"><input type="text" id="quantity_max"  class="form-control" value="3"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-shopping-cart"></i><span class="header-sales"> eBay Sales</span>
                    </div>
                    <div class="custom-info-box-body">
                        <table>
                            <tr>
                                <td class="td-label">Min</td>
                                <td class="td-value"><input type="text" id="sold_min"  class="form-control" value="1"></td>
                                <td class="td-label">Max</td>
                                <td class="td-value"><input type="text" id="sold_max"  class="form-control" value="6"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2">
                <div class="alert alert-info">
                    <div class="custom-info-box">
                        <i class="glyphicon glyphicon-list-alt"></i><span class="header-sales"> Item Count</span>
                    </div>
                    <div class="custom-info-box-body">
                        <table>
                            <tr>
                                <td class="td-value-small"><input class="form-control input-small" type="item_count" name="item_count" id="item_count" value="50" placeholder="Enter the number to search" required="required" /></td>
                                <td class="td-label">Max. 100</td>
                            </tr>
                        </table>
                        
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="center" style="margin: auto; width: 50%; text-align: center">
	    		    <button  id="find_items"class="btn btn-default btn-search-center" style="margin-top: 15%" type="button">Search</button>
                </div>
                <?php if ($hot_items_coutn_label_flag) {
                ?>
                <div class="center" style=" margin-top: 5%; width: 100%; text-align: center">
                    <span class="header-sales">You can make </span>
                    <span id="hot_items_count" class="header-sales" style="color:red;"><?php echo $hot_items_count; ?></span>
                    <span class="header-sales">Searches</span>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
        <br />
        <div class="row hidden" id="hot_items_body" style="margin-left: 3px; margin-right:3px">
            <table id="hot_items_table" class="table table-bordered table-striped" cellspacing="0" width="100%">
                <thead style="text-align: center;">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Item Title</th>
                        <th>Category</th>
                        <th>Username</th>
                        <th>Feedbacks</th>
                        <th>eBay Sales</th>
                        <th>Price Target</th>
                        <th>Currency</th>
                        <th>Link</th>
                    </tr>
                </thead>
                <tbody style="text-align: center;">
                </tbody>
        </table>
        </div>
    </div>
</div>

</div>
</section>

<style type="text/css">
	.loader {
		position: fixed;
		margin-left: 0%;
		width: 100%;
		height: 100%;
		z-index: 9999;
		display: none;
	}
	#example_wrapper
	{
		border: 1px solid grey;
		padding: 10px;
	}
	
	#hot_items_table.dataTable img {
    	height: 100px !important;
        width: 100px !important;
	}
</style>