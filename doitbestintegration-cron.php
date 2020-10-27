<?php
/*
* This file is called by cron.
* Functions are run based on user configured settings.
*/

// Prevent dying when execution time is high (downloading and updating via FTP EDI usually)
ini_set('memory_limit','1024M');
ini_set('max_execution_time', 0);

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include(dirname(__FILE__).'/doitbestintegration.php');

$doItBestIntegration = new DoItBestIntegration();
$currentConfig = $doItBestIntegration->getCurrentConfiguration();

// Check token value
if ((ISSET($argv) && !Configuration::get('DOITBEST_CRON_TOKEN') == $argv[1]) && (Configuration::get('DOITBEST_CRON_TOKEN') != Tools::getValue('token') || !Module::isInstalled('doitbestintegration'))) 
{
    die('Bad token');
}

function is_time_to_run($interval) {
    $currentMinute = date('i');
    $currentHour = date('h');
    $currentWeekDay = date('w');
    $currentMonthDay = date('d');

    // Only minutes are numeric
    if (is_numeric($interval)) {
        if ($currentMinute%$interval == 0) {
            return TRUE;
        }
    }

    // Otherwise run the task at the set schedule time
    switch ($interval){
        case 'hourly':
            if ((int) $currentMinute == 0) {
                return TRUE;
            }
            break;
        case 'daily':
            if ((int) $currentHour == 0 && (int) $currentMinute == 0) {
                return TRUE;
            }
            break;
        case 'weekly':
            if ((int) $currentWeekDay == 0 && (int) $currentHour == 0 && (int) $currentMinute == 0) {
                return TRUE;
            }
            break;
        case 'monthly':
            if ((int) $currentMonthDay == 1 && $currentWeekDay == 0 && (int) $currentHour == 0 && $currentMinute == 0) {
                return TRUE;
            }
            break;
        default:
            break;
    }

    return FALSE;
}

function execute_scheduled_tasks() 
{    
    global $doItBestIntegration;

    foreach ($doItBestIntegration::CONFIG_KEYS as $fieldDefinition) {
        if (explode('_', $fieldDefinition['config_key'])[count( explode('_', $fieldDefinition['config_key'])) - 1] == 'INTERVAL') {
            switch ($fieldDefinition['config_key']) {
                case 'DOITBEST_STOCK_REFRESH_INTERVAL':
                    if (is_time_to_run(Configuration::get('DOITBEST_STOCK_REFRESH_INTERVAL')) && Configuration::get('DOITBEST_STOCK_REFRESH') == 1) {
                        if (!$result = do_stock_refresh()) {
                            error_log('Failed performing stock refresh in doitbestintegration module');
                            header('Content-Type: application/json');
                            $message = ['message' => 'Failed performing stock refresh in doitbestintegration module'];
                            echo json_encode($message);
                        }
                        else {
                            error_log('Success. ' . $result->count . ' products were refreshed.');
                            header('Content-Type: application/json');
                            $message = ['message' => 'Success. ' . $result->count . ' products were refreshed.', 'data' => ($result->data)];
                            echo json_encode($message);
                        }
                    }
                    break;     

                case 'DOITBEST_STOCK_ADD_INTERVAL':
                    if (is_time_to_run(Configuration::get('DOITBEST_STOCK_ADD_INTERVAL')) && Configuration::get('DOITBEST_STOCK_ADD_EDI') == 1) {
                        if (!$result = do_edi_add_and_update()) {
                            error_log('Failed performing stock EDI add in doitbestintegration module');
                            header('Content-Type: application/json');
                            $message = ['message' => 'Failed performing stock EDI add in doitbestintegration module'];
                            echo json_encode($message);
                        } else {
                            error_log('Success. ' . $result->count . ' products were readded.');
                            header('Content-Type: application/json');
                            $message = ['message' => 'Success. ' . $result->count . ' products were refreshed.'];
                            echo json_encode($result);
                        }
                    }
                    break;

                default:
                    break;
            }
        }
    }
}

function do_stock_refresh() {
    $apiKey = Configuration::get('DOITBEST_API_KEY');
    $storeNumber = Configuration::get('DOITBEST_STORE_NUMBER');
    $warehouseNumber = Configuration::get('DOITBEST_WAREHOUSE_NUMBER');
    $stockRefreshTime = date("Y-m-d H:i:s.000", (strtotime(gmdate("Y-m-d H:i:s")) - (Configuration::get('DOITBEST_STOCK_REFRESH_INTERVAL') * 60)));

    $endpointUrl = 'https://api.doitbestdataxchange.com/InventoryBySKU/inventorychanges?' . http_build_query(array(
            'memberNumber' => $storeNumber,
            'changesSince' => $stockRefreshTime
        )
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $endpointUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Ocp-Apim-Subscription-Key: $apiKey"
    ));

    if (($result = curl_exec($curl)) === false) {
        curl_close($curl);
        return FALSE;
    }

    curl_close($curl);

    // Parse response
    $result = json_decode($result);

    // If nothing is updated, a message "There are no changes since the provided date" is returned which won't be parsed by json decode
    if (is_null($result)) {
        return (object) ['data' => null, 'count' => 0];
    }

    // If this function returns results, update quantities by modifying SQL
    $db = \Db::getInstance();
    foreach ($result as $index => $product) {
        if ($product -> RSCNumeric == $warehouseNumber) {
            $query = 'UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity = ' . $product -> Quantity . ' WHERE id_product = (SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference="' . $product->SKU . '" LIMIT 1)';
            $db->execute($query);
            continue;
        }

        // Remove items not stock in your warehouse form showing the results of this script in browser/curl
        unset($result[$index]);
    }

    $result = (object) ['data' => $result, 'count' => count($result)];

    return $result;
}

function do_edi_add_and_update () {
    $ftpHost = explode(':', Configuration::get('DOITBEST_FTP_HOST'))[0];
    $ftpPort = explode(':', Configuration::get('DOITBEST_FTP_HOST'))[1];
    $ftpUsername = Configuration::get('DOITBEST_FTP_USERNAME');
    $ftpPassword = Configuration::get('DOITBEST_FTP_PASSWORD');

    // EDI files store warehouses as codes, transform the selected warehouse number to the corresponding code
    $warehouseNumber = Configuration::get('DOITBEST_WAREHOUSE_NUMBER');
    switch ((int) $warehouseNumber) {
        case 1:
            $warehouseID = "C";
            break;
        case 2:
            $warehouseID = "D";
            break;
        case 3:
            $warehouseID = "M";
            break;
        case 4:
            $warehouseID = "W";
            break;
        case 5:
            $warehouseID = "L";
            break;
        case 6:
            $warehouseID = "P";
            break;
        case 7:
            $warehouseID = "N";
            break;
        case 8:
            $warehouseID = "S";
            break;
        default:
            return FALSE;
    }

    if (!$ftpHost || !$ftpPort || !$ftpUsername || !$ftpPassword) {
        error_log('Couldn\'t get EDI files for update. FTP missing configuration values');
        return FALSE;
    }

    if(!$ftp = ftp_ssl_connect($ftpHost, $ftpPort)) {
        error_log('Couldn\'t establish connection to FTP EDI Host. Check host and port');
        return FALSE;
    }

    if (!ftp_login($ftp, $ftpUsername, $ftpPassword)) {
        error_log('Couldn\'t connect to Do It Best EDI. Check username and password.');
        return FALSE;
    }

    // Switch to passive mode
    ftp_pasv($ftp, true);

    // Download the RSCInventory file from FTP
    $handle = fopen(dirname(__FILE__).'/ediFiles/RSCInventory.json', 'w+');
    ftp_fget($ftp, $handle, '/dataxchange/V1/RSCInventory.json', FTP_ASCII, 0);
    fclose($handle);

    // Parse the file
    $rscFileContent = json_decode(file_get_contents(dirname(__FILE__).'/ediFiles/RSCInventory.json'));

    // Get the index of the selected warehouse or false if not in file
    function get_selected_warehouse($inventoryRecord, $warehouseID) {
        foreach ($inventoryRecord->WarehouseInventory as $index => $warehouse) {
            if ($warehouse -> WarehouseCode == $warehouseID) {
                return $index;
            }
        }

        return FALSE;
    }

    $changedRecords = 0;
    $rscFileItemCount = count($rscFileContent);
    $db = \Db::getInstance();
    foreach ($rscFileContent as $index => $itemRecord) {
        if ($index%50 == 0) {
            error_log("Doing item updates from RSCInventory file: ($index/$rscFileItemCount)");
        }

        $sku = $itemRecord -> SKU;

        if (($selectedWarehouse = get_selected_warehouse($itemRecord, $warehouseID)) === FALSE) {
            $quantity = 0;
        } else {
            $quantity = $itemRecord->WarehouseInventory[$selectedWarehouse] -> QuantityOnHand;
        }

        $query = 'UPDATE ' . _DB_PREFIX_ . 'stock_available SET quantity = ' . $quantity . ' WHERE id_product = (SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference="' . $itemRecord->SKU . '" LIMIT 1)';
        $db->execute($query);
        $changedRecords++;
    }

    $result = (object) ['count' => $changedRecords];

    return $result;
}

// Make use of the DataXChange Item Cost Updates endpoint to get pricing updates
function do_pricing_update() {
    $apiKey = Configuration::get('DOITBEST_API_KEY');
    $storeNumber = Configuration::get('DOITBEST_STORE_NUMBER');
    $warehouseNumber = Configuration::get('DOITBEST_WAREHOUSE_NUMBER');
    $stockRefreshTime = date("Y-m-d H:i:s.000", (strtotime(gmdate("Y-m-d H:i:s")) - (Configuration::get('DOITBEST_STOCK_REFRESH_INTERVAL') * 60)));

    $endpointUrl = 'https://api.doitbestdataxchange.com/cost/itemcostchanges?' . http_build_query(array(
            'memberNumber' => $storeNumber,
            'changesSince' => $stockRefreshTime
        )
    );

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $endpointUrl);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Ocp-Apim-Subscription-Key: $apiKey"
    ));

    if (($result = curl_exec($curl)) === false) {
        curl_close($curl);
        return FALSE;
    }

    curl_close($curl);

    // Parse response
    $result = json_decode($result);

    // If nothing is updated, a message "There are no changes since the provided date" is returned which won't be parsed by json decode
    if (is_null($result)) {
        return (object) ['data' => null, 'count' => 0];
    }

    // If this function returns results, update quantities by modifying SQL
    $db = \Db::getInstance();
    foreach ($result as $index => $product) {
        if ($product -> RSCNumeric == $warehouseNumber) {
            $query = 'UPDATE ' . _DB_PREFIX_ . 'product SET price = ' . $product -> suggestedRetailPrice . ' WHERE id_product = (SELECT id_product FROM ' . _DB_PREFIX_ . 'product WHERE reference="' . $product->sku . '" LIMIT 1)';
            $db->execute($query);
            continue;
        }

        // Remove items not stock in your warehouse form showing the results of this script in browser/curl
        unset($result[$index]);
    }

    $result = (object) ['data' => $result, 'count' => count($result)];

    return $result;
}

execute_scheduled_tasks();

// Clean up console output
if (ISSET($argv)) {
    echo PHP_EOL;
}
?>