<script>
    function change_location(integration) {
        window.location = window.location + '&integrationTest=' + integration;
    }
</script>

<fieldset>
 <h2>Do it Best Integration Configuration</h2>
 <div class="panel">
    <div class="panel-heading">
        <legend><img src="../img/admin/cog.gif" alt="" width="16"/>
            Configuration
        </legend>
    </div>
    <div class="h4">
        All available Do-it-Best integrations are available below. Insert your API key and store number below and well as your primary warehouse, then select the integrations you'd like to enable.
    </div>
    <br>
    <form action="" method="post">
        <div class="form-inline clearfix" style="margin-bottom: 10px; display: flex; align-items: center;">
            <label class="col-xs-3 col-md-1">API Key:</label>
            <input class="form-control col-xs-6 col-md-6" style="width: 25vw;" name="dib_api_key" type="text" placeholder="1234455676" {if $doItBestConfig.dib_api_key} value="{$doItBestConfig.dib_api_key}" {/if} required>
        </div>
        <div class="form-inline clearfix" style="margin-bottom: 10px; display: flex; align-items: center;">
            <label class="col-xs-3 col-md-1">Store Number:</label>
            <input class="form-control col-xs-6 col-md-6" style="width: 25vw;" name="dib_store_number" type="text" placeholder="6000" {if $doItBestConfig.dib_store_number} value="{$doItBestConfig.dib_store_number}" {/if} required>
        </div>
        <div class="form-inline clearfix" style="margin-bottom: 10px; display: flex; align-items: center;">
            <label class="col-xs-3 col-md-1">DIB Warehouse:</label>
            <select id="dib_warehouse_number" name="dib_warehouse_number">
                <option {if !$doItBestConfig.dib_warehouse_number}selected{/if} disabled>Please select a warehouse</option>
                <option value="01" {if $doItBestConfig.dib_warehouse_number == '01'} selected {/if}>Sikeston</option>
                <option value="02" {if $doItBestConfig.dib_warehouse_number == '02'} selected {/if}>Dixon</option>
                <option value="03" {if $doItBestConfig.dib_warehouse_number == '03'} selected {/if}>Medina</option>
                <option value="04" {if $doItBestConfig.dib_warehouse_number == '04'} selected {/if}>Waco</option>
                <option value="05" {if $doItBestConfig.dib_warehouse_number == '05'} selected {/if}>Lexington</option>
                <option value="06" {if $doItBestConfig.dib_warehouse_number == '06'} selected {/if}>Woodburn</option>
                <option value="07" {if $doItBestConfig.dib_warehouse_number == '07'} selected {/if}>Montgomery</option>
                <option value="08" {if $doItBestConfig.dib_warehouse_number == '08'} selected {/if}>Mesquite</option>
            </select>
        </div>
        <div class="form-inline clearfix" style="margin-bottom: 10px; display: flex; align-items: center;">
            <label class="col-xs-3 col-md-1">EDI FTP Host:</label>
            <input class="form-control col-xs-6 col-md-6" style="width: 25vw;" name="dib_ftp_host" type="text" placeholder="ftp.edi.doitbestcorp.com:10021" {if $doItBestConfig.dib_ftp_host} value="{$doItBestConfig.dib_ftp_host}" {/if} required>
        </div>
        <div class="form-inline clearfix" style="margin-bottom: 10px; display: flex; align-items: center;">
            <label class="col-xs-3 col-md-1">EDI FTP Username:</label>
            <input class="form-control col-xs-6 col-md-6" style="width: 25vw;" name="dib_ftp_username" type="text" placeholder="MBR1234" {if $doItBestConfig.dib_ftp_username} value="{$doItBestConfig.dib_ftp_username}" {/if} required>
        </div>
        <div class="form-inline clearfix" style="margin-bottom: 10px; display: flex; align-items: center;">
            <label class="col-xs-3 col-md-1">EDI FTP Password:</label>
            <input class="form-control col-xs-6 col-md-6" style="width: 25vw;" name="dib_ftp_password" type="password" placeholder="Enter password" {if $doItBestConfig.dib_ftp_password} value="{$doItBestConfig.dib_ftp_password}" {/if} required>
        </div>
        <hr>
        <div class="form-group clearfix" style="border: 1px solid grey; display: flex; align-items: center; padding: 10px; border-radius: 10px;">
            <label class="col-lg-3 col-xs-5">API Stock Refresh:<br><small>Updates the quantity values at the selected interval. Requires the cron module be installed, enabled, and properly configured.</small></label>
            <div class="col-lg-5 col-xs-7 text-center">
                <div class="col-lg-8">
                    <input type="radio" id="dib_stock_refresh_disable" name="dib_stock_refresh" value="0" {if $doItBestConfig.dib_stock_refresh === '0'} checked {/if}>
                    <label for="dib_stock_refresh_disable">Disable</label>
                    <input type="radio" id="dib_stock_refresh_enable" name="dib_stock_refresh" value="1" style="margin-left: 15px;" {if $doItBestConfig.dib_stock_refresh === '1'} checked {/if}>
                    <label for="dib_stock_refresh_enable">Enable</label>
                </div>
                <div class="col-lg-8" style="align-content: center;">
                    <select name="dib_stock_refresh_interval" class="col-xs-12 col-lg-6 col-lg-offset-3">
                        <option value="5" {if $doItBestConfig.dib_stock_refresh_interval == 5} selected {/if}>Interval: 5 Minutes</option>
                        <option value="hourly" {if $doItBestConfig.dib_stock_refresh_interval == 'hourly'} selected {/if}>Interval: 60 Minutes</option>
                        <option value="daily" {if $doItBestConfig.dib_stock_refresh_interval == 'daily'} selected {/if}>Interval: 1 Day</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-info" onclick="change_location('stockRefresh')">Test this Method</button>
                </div>
            </div>
        </div>
        <div class="form-group clearfix" style="border: 1px solid grey; display: flex; align-items: center; padding: 10px; border-radius: 10px;">
            <label class="col-lg-3 col-xs-5">API Pricing Refresh:<br><small>Updates the quantity values at the selected interval. Requires the cron job be properly configured.</small></label>
            <div class="col-lg-5 col-xs-7 text-center">
                <div class="col-lg-8">
                    <input type="radio" id="dib_pricing_refresh_disable" name="dib_pricing_refresh" value="0" {if $doItBestConfig.dib_pricing_refresh === '0'} checked {/if}>
                    <label for="dib_pricing_refresh_disable">Disable</label>
                    <input type="radio" id="dib_pricing_refresh_enable" name="dib_pricing_refresh" value="1" style="margin-left: 15px;" {if $doItBestConfig.dib_pricing_refresh === '1'} checked {/if}>
                    <label for="dib_pricing_refresh_enable">Enable</label>
                </div>
                <div class="col-lg-8" style="align-content: center;">
                    <select name="dib_pricing_refresh_interval" class="col-xs-12 col-lg-6 col-lg-offset-3">
                        <option value="5" {if $doItBestConfig.dib_pricing_refresh_interval == 5} selected {/if}>Interval: 5 Minutes</option>
                        <option value="hourly" {if $doItBestConfig.dib_pricing_refresh_interval == 'hourly'} selected {/if}>Interval: 60 Minutes</option>
                        <option value="daily" {if $doItBestConfig.dib_pricing_refresh_interval == 'daily'} selected {/if}>Interval: 1 Day</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-info" onclick="change_location('pricingRefresh')">Test this Method</button>
                </div>
            </div>
        </div>
        <div class="form-group clearfix" style="border: 1px solid grey; display: flex; align-items: center; padding: 10px; border-radius: 10px;">
            <label class="col-lg-3 col-xs-5">EDI Product Adds:<br><small>Uses the TLS FTP server to get product info, images, quantities, etc. EDI files are often >300MB. Be sure you have enough space on the server.</small></label>
            <div class="col-lg-5 col-xs-7 text-center">
                <div class="col-lg-8">
                    <input type="radio" id="dib_stock_add_disable" name="dib_stock_add_edi" value="0" {if $doItBestConfig.dib_stock_add_edi === '0'} checked {/if}>
                    <label for="dib_stock_add_disable">Disable</label>
                    <input type="radio" id="dib_stock_add_enable" name="dib_stock_add_edi" value="1" style="margin-left: 15px;" {if $doItBestConfig.dib_stock_add_edi === '1'} checked {/if}>
                    <label for="dib_stock_add_enable">Enable</label>
                </div>
                <div class="col-lg-8" style="align-content: center;">
                    <select name="dib_stock_add_interval" class="col-xs-12 col-lg-6 col-lg-offset-3">
                        <option value="daily" {if $doItBestConfig.dib_stock_add_interval == 'daily'} selected {/if}>Interval: 1 Day</option>
                        <option value="weekly" {if $doItBestConfig.dib_stock_add_interval == 'weekly'} selected {/if}>Interval: 1 Week</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn btn-info" onclick="change_location('ediProductsAdd')">Test this Method</button>
                </div>
            </div>
        </div>
        <div>
            <p>
                To finish installation. Please copy the following into your apache/php-fpm user's cron file:
            </p>
            <code>
                {$cronPath}
            </code>
        </div>
        <div class="panel-footer">
			<button type="submit" value="1" id="module_form_submit_btn" name="submitDoItBestConfig" class="btn btn-default pull-right">
			    <i class="process-icon-save"></i> Save
			</button>
		</div>
    </form>
 </div>
 {if ISSET($testData)}
 <div class="panel">
    <div class="panel-heading">
        <legend><img src="../img/admin/cog.gif" alt="" width="16"/>
            Test Data
        </legend>
    </div>
    <div class="h4">
        This is test data for the chosen Do-it-Best Method
    </div>
    <br>
    <pre>
        {$testData}
    </pre>
</div>
 {/if}
</fieldset>