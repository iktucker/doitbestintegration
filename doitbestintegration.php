<?php
class DoItBestIntegration extends Module
{
    public function __construct ()
    {
        $this->name = 'doitbestintegration';
        $this->tab = 'shipping_logistics';
        $this->version = '0.1';
        $this->author = 'Ian Tucker';
        $this->displayName = 'Do it Best Integrations - Integrate with DataXchange';
        $this->description = 'This module allows you to tie into some of the vital Do-it-Best dataXchange products for things like pricing a stock availability.';
        $this->bootstrap = true;
        parent::__construct();

        $this->dependencies  =  array ('cronjobs') ;
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }
         
         if (!parent::install()
            ) {
            return false;
        }
        
        // Create a random token for later use in establishing a cronjob agent
        if (!$token = random_bytes(8)) {
            error_log('Failed generating random token in doitbestintegration module');
            return false;
        }
        Configuration::updateValue('DOITBEST_CRON_TOKEN', bin2hex($token));

        return true;
    }

    const CONFIG_KEYS =
    [
        array(
            'form_key' => 'dib_api_key',
            'config_key' => 'DOITBEST_API_KEY',
            'type' => 'text',
            'depends' => null,
            'required' => TRUE
        ),
        array(
            'form_key' => 'dib_store_number',
            'config_key' => 'DOITBEST_STORE_NUMBER',
            'type' => 'text',
            'depends' => null,
            'required' => TRUE
        ),
        array(
            'form_key' => 'dib_warehouse_number',
            'config_key' => 'DOITBEST_WAREHOUSE_NUMBER',
            'type' => 'select',
            'depends' => null,
            'required' => TRUE
        ),
        array(
            'form_key' => 'dib_ftp_host',
            'config_key' => 'DOITBEST_FTP_HOST',
            'type' => 'select',
            'depends' => null,
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_ftp_username',
            'config_key' => 'DOITBEST_FTP_USERNAME',
            'type' => 'select',
            'depends' => null,
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_ftp_password',
            'config_key' => 'DOITBEST_FTP_PASSWORD',
            'type' => 'select',
            'depends' => null,
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_stock_refresh',
            'config_key' => 'DOITBEST_STOCK_REFRESH',
            'type' => 'radio',
            'depends' => null,
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_stock_refresh_interval',
            'config_key' => 'DOITBEST_STOCK_REFRESH_INTERVAL',
            'type' => 'select',
            'depends' => 'dib_stock_refresh',
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_pricing_refresh',
            'config_key' => 'DOITBEST_STOCK_REFRESH',
            'type' => 'radio',
            'depends' => null,
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_pricing_refresh_interval',
            'config_key' => 'DOITBEST_PRICING_REFRESH_INTERVAL',
            'type' => 'select',
            'depends' => 'dib_pricing_refresh',
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_stock_add_edi',
            'config_key' => 'DOITBEST_STOCK_ADD_EDI',
            'type' => 'radio',
            'depends' => null,
            'required' => FALSE
        ),
        array(
            'form_key' => 'dib_stock_add_interval',
            'config_key' => 'DOITBEST_STOCK_ADD_INTERVAL',
            'type' => 'select',
            'depends' => 'dib_stock_add_edi',
            'required' => FALSE
        )
    ];

    public function getContent() {

        // Process configuration changes on form submit
        if (Tools::isSubmit('submitDoItBestConfig')) {
            $this -> processConfiguration();
        }

        // TODO: Allow fetching the current config
        $currentConfig = $this -> getCurrentConfiguration();
        $this->context->smarty->assign('doItBestConfig', $currentConfig);
        $this->context->smarty->assign('cronPath', '* * * * * php ' . dirname(__FILE__) . '/doitbestintegration-cron.php ' . Configuration::get('DOITBEST_CRON_TOKEN'));

        if (Tools::getValue('integrationTest')) {
            switch (Tools::getValue('integrationTest')) {
                case 'stockRefresh':
                    $this->context->smarty->assign('testData', $this->do_stock_refresh());
                    break;
                
                default:
                    break;
            }
        }
        
        return $this->display(__FILE__, 'getContent.tpl');
    }

    public function getCurrentConfiguration() {
        $makeConfigMap = function ($singleRecord) {
            return array($singleRecord['form_key'] => Configuration::get($singleRecord['config_key']));
        };

        $currentConfig = array_map($makeConfigMap, self::CONFIG_KEYS);
        $currentConfigMerged = array();

        foreach($currentConfig as $key => $value) {
            $currentConfigMerged = array_merge($currentConfigMerged, $currentConfig[$key]);
        }
        
        return $currentConfigMerged;
    }

    public function processConfiguration ()
    {        
        if (Tools::isSubmit('submitDoItBestConfig'))
        {
            $configValues = [];

            foreach(self::CONFIG_KEYS as $fieldDefinition) {
                // Check if field is required and unset (error if this is the case)
                if ($fieldDefinition['required'] && ($value = Tools::getValue($fieldDefinition['form_key'])) === FALSE) {
                    die('Required field not set ' . $fieldDefinition['form_key']);
                }

                // Check if field is optional and depends another field. Error if the other field is unset
                if (ISSET($fieldDefinition['depends'])) {
                    if (Tools::getValue($fieldDefinition['depends']) === FALSE) {
                        die('Required dependant value not set<br>Field: ' . $fieldDefinition['form_key'] . '<br>Dependant: ' . $fieldDefinition['depends']);
                    }
                }

                if (($value = Tools::getValue($fieldDefinition['form_key'])) === FALSE) {
                    continue;
                }

                Configuration::updateValue($fieldDefinition['config_key'], $value);
                $configValues[$fieldDefinition['config_key']] = $value;
            }

            return ($configValues);
        }
    }

    public function do_stock_refresh() {
        $apiKey = Configuration::get('DOITBEST_API_KEY');
        $storeNumber = Configuration::get('DOITBEST_STORE_NUMBER');
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


        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
        json_encode(array("refreshDate" => $stockRefreshTime, "store" => $storeNumber, "result" => json_decode($result)), JSON_PRETTY_PRINT);
    }
}
?>