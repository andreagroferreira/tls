<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateInitialConfigurationDataPaymentConfigurationsAndAccountsTable extends Migration
{
    private $env_gateway = ['fawry'];
    private $configs     = ['sandbox', 'prod'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() == 'deploy_payment_pgsql') {
            $clients = ['be', 'ch', 'de', 'pl', 'fr', 'hmpo_uk', 'leg_be', 'leg_de'];
            DB::beginTransaction();
            try {
                foreach ($clients as $client) {
                    $configs = config('payment_gateway')[$client];
                    $issuers = array_keys($configs);
                    foreach ($issuers as $issuer) {
                        $country  = ($issuer != 'allAll2all') ? substr($issuer, 0, 2) : 'all';
                        $city     = ($issuer != 'allAll2all') ? substr($issuer, 2, 3) : 'all';
                        $gateways = config('payment_gateway')[$client][$issuer];
                        foreach ($gateways as $gateway_key => $gateway_val) {
                            $psp_info = DB::table('payment_service_providers')->where(['psp_code' => $gateway_key])->first();
                            $accounts_data  = ['pa_xref_psp_id' => $psp_info->psp_id];
                            $configurations = [
                                'pc_project' => $client,
                                'pc_country' => $country,
                                'pc_city'    => $city
                            ];
                            if (in_array($gateway_key, $this->env_gateway)) { $gateway_val = $this->getEnvpayContent($gateway_val); }
                            if ($gateway_key != 'pay_later') {
                                foreach ($this->configs as $config) {
                                    if (isset($gateway_val[$config]) && !empty($gateway_val[$config])) {
                                        $accounts_data['pa_type'] = $config;
                                        $accounts_data['pa_info'] = json_encode($gateway_val[$config]);
                                        $accounts_data['pa_name'] = $gateway_key . ' ' . $client . '-' . $issuer . ' ' . $config;
                                        $this->createPayment($accounts_data, $configurations);
                                    }
                                }
                            } else {
                                $accounts_data['pa_name'] = $gateway_key . ' ' . $client . '-' . $issuer;
                                $accounts_data['pa_type'] = 'pay_later';
                                $accounts_data['pa_info'] = '';
                                $this->createPayment($accounts_data, $configurations);
                            }
                        }
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw new LogicException($e->getMessage());
            }
        }
    }

    /*
     * Add initial configuration
     * */
    private function createPayment($accounts_data, $configurations) {
        // create accounts
        DB::table('payment_accounts')->insert($accounts_data);
        // get pc_xref_pa_id
        unset($accounts_data['pa_info']);
        $account = DB::table('payment_accounts')->where($accounts_data)->first();
        // create configurations
        $configurations['pc_xref_pa_id'] = $account->pa_id;
        return DB::table('payment_configurations')->insert($configurations);
    }

    /*
     * get environment variables
     * */
    private function getEnvpayContent($gateway) {
        foreach ($this->configs as $config) {
            if (isset($gateway[$config]) && !empty($gateway[$config])) {
                foreach ($gateway[$config] as $key => $val) {
                    if ($key == 'host') { continue; }
                    $gateway[$config][$key] = env(str_replace('ENVPAY_', '', $val));
                }
            }
        }
        return $gateway;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}