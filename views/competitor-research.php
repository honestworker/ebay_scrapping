<div class="modal fade" id="watchlist-add">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Add to watchlist</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" class="watchlist-seller-id" value="">
                <table class="table table-watchlist">
                    <tr>
                        <td>Note:</td>
                        <td>
                            <textarea class="form-control watchlist-message"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>Rating:</td>
                        <td>
                            <input type="hidden" value="0" class="watchlist-rate">
                            <div class="watchlist-rate-stars">
                                <i class="glyphicon glyphicon-star-empty" data-value="1"></i>
                                <i class="glyphicon glyphicon-star-empty" data-value="2"></i>
                                <i class="glyphicon glyphicon-star-empty" data-value="3"></i>
                                <i class="glyphicon glyphicon-star-empty" data-value="4"></i>
                                <i class="glyphicon glyphicon-star-empty" data-value="5"></i>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-watchlist-save">Save</button>
                <button type="button" class="btn btn-danger btn-watchlist-cancel pull-left" data-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
    <!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->

<div class="modal fade" id="modal-competition" style="display: none;">
    <div class="modal-dialog" style="width: 1000px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">脳</span></button>
                <h4 class="modal-title">Competition Details</h4>
            </div>
            <div class="modal-body">
                <div class="competition-spinner" style="text-align: center; padding: 20px;">Loading...</div>
                <div class="competition-table" style="display: none;">
                    <table id="competition-items" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                            <th>Image</th>
                            <th>Item title</th>
                            <th>Total sold</th>
                            <th>Current Price</th>
                            <th>Sold Price</th>
                            <th>Seller ID</th>
                            </tr>
                        </thead>
                        <tbody class="items-competition-table"></tbody>
                    </table>
                </div>
            </div>
        </div>
    <!-- /.modal-content -->
    </div>
<!-- /.modal-dialog -->
</div>

<div class="loader hidden">
    <img src="<?php echo base_url();?>/assets/images/page-loader.gif"/>
</div>

<div class="row row-competitor-research">
    <div class="input-group userSearchPart">
        <!--Left search icon-->
        <div class="leftSearchIcon">
        </div><!--End left search icon-->
        <!--Input search part-->
        <input type="search" class="form-control txtSearch search-competitor-username" placeholder="Search for seller username..." style="width:82%" id="username">
        <!--Selection search part-->
        <select class="form-control range" style="width: 15%;float: left;">
            <option value="30" selected>30 Days</option>
            <option value="21">21 Days</option>
            <option value="14">14 Days</option>
            <option value="7">7 Days</option>
        </select>
        <!--Search button-->
        <span class="input-group-btn">
            <button id="btnSearch" class="btn btn-default btn-search-competitor" type="button">Search</button>
        </span><!--End search button-->
    </div>
</div>

<div class="alerts"></div>

<div class="row seller-badges" style="display: none;">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="fa fa-bar-chart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Sell Through Rate</span>
                <span class="info-box-number" data-key="sell_rate">---</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="glyphicon glyphicon-certificate"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Sold Items</span>
                <span class="info-box-number" data-key="sold_items">---</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="glyphicon glyphicon-search"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Listings</span>
                <span class="info-box-number" data-key="items_count">---</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="glyphicon glyphicon-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Sale Earnings</span>
                <span class="info-box-number" data-key="sale_earnings">---</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="glyphicon glyphicon-star"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Feedback Score</span>
                <span class="info-box-number" data-key="feedback_score">---</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-aqua"><i class="glyphicon glyphicon-dashboard"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Feedback Ratio</span>
                <span class="info-box-number" data-key="feedback_ratio">---</span>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="alert alert-info" style="text-align: center;">Country: <span data-key="country"></span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="alert alert-info btn-add-watchlist" style="text-align: center;">
            <i class="glyphicon glyphicon-heart-empty"></i> Add to watchlist
        </div>
        <div class="alert alert-info alert-watchlist-added" style="text-align: center;display: none;">
            <i class="glyphicon glyphicon-ok"></i> Watching
        </div>
    </div>
    <div class="col-md-3">
        <div class="alert alert-info competitor-filter">
            <div class="competitor-filter-header">
                <i class="glyphicon glyphicon-shopping-cart"></i><span class="header-sales">30 Days Sales</span>
            </div>
            <div class="competitor-filter-body">
                <table>
                    <tr>
                        <td class="td-label">Min</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-sales-min" value=""></td>
                        <td class="td-label">Max</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-sales-max" value=""></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="alert alert-info competitor-filter">
            <div class="competitor-filter-header">
                <i class="glyphicon glyphicon-star"></i> Total Sold
            </div>
            <div class="competitor-filter-body">
                <table>
                    <tr>
                        <td class="td-label">Min</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-total-min" value=""></td>
                        <td class="td-label">Max</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-total-max" value=""></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="alert alert-info competitor-filter">
            <div class="competitor-filter-header">
                <i class="glyphicon glyphicon-calendar"></i> Upload date ( Days )
            </div>
            <div class="competitor-filter-body">
                <table>
                    <tr>
                        <td class="td-label">Min</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-upload-min" value=""></td>
                        <td class="td-label">Max</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-upload-max" value=""></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="alert alert-info competitor-filter">
            <div class="competitor-filter-header">
                <i class="glyphicon glyphicon-usd"></i> Price
            </div>
            <div class="competitor-filter-body">
                <table>
                    <tr>
                        <td class="td-label">Min</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-price-min" value=""></td>
                        <td class="td-label">Max</td>
                        <td class="td-value"><input type="text" class="form-control competitor-filter-price-max" value=""></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row" style="margin-left:15px; margin-right:15px;    overflow-x: auto;">
    <div class="results" style="display: none;">
        <table id="items" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Item title</th>
                    <th class="header-sales">Sales</th>
                    <th>Total sold</th>
                    <th>Upload date</th>
                    <th>Price</th>
                    <th>小ompetition</th>
                </tr>
            </thead>
            <tbody class="items-table"></tbody>
        </table>
    </div>
</div> 

<style>
    .dataTables_info {
        margin-left: 15px !important;
    }

    .items-competition-table img {
        height: 70px !important;
        width: 70px !important;
    }
    
    #items.dataTable img {
        height: 100px !important;
        width: 100px !important;
        margin-left: 30px;
    }
    .table-striped>tbody>tr.selected {
        background-color: #00c0ef;
    }
    .row-competitor-research {
        margin-top: 30px;
        margin-left:15px;
        margin-right: 15px;
    }

    .results, .alerts, .seller-badges {
        margin-top: 10px;
        margin-left: 15px;
        margin-right:15px;
    }

    .results img {
        width: 100px;
    }

    .results .table {
        background-color: #fff;
        width: 100%;
        margin-top: 30px;
    }

    .results .table .number {
        text-align:center;
    }

    .table-bordered>thead>tr>th, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>tbody>tr>td, .table-bordered>tfoot>tr>td {
        border: 1px solid #d2d0d0 !important;
    }
    
    table.table-bordered.dataTable th, table.table-bordered.dataTable td {
        border-left-width: 0 !important;
    }
    
    .table-bordered>thead>tr>th, .table-bordered>thead>tr>td {
        border-bottom-width: 0px;
</style>