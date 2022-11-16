<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateInitialConfigDataPaymentConfigurationsAndAccountsTable extends Migration
{
    /**
     * @var array
     */
    private $envVariableGateways = ['fawry'];

    /**
     * @var array
     */
    private $configs = ['sandbox', 'production'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDefaultConnection() === 'deploy_payment_pgsql') {
            DB::beginTransaction();

            try {
                DB::unprepared('TRUNCATE TABLE payment_configurations RESTART IDENTITY CASCADE');
                DB::unprepared('TRUNCATE TABLE payment_accounts RESTART IDENTITY CASCADE');

                $this->createPayLaterPaymentAccount();
                $client = $this->getProjectId();
                $paymentConfigurations = config('payment_gateway')[$client];
                $appName = explode('-', env('PROJECT'));
                $appEnv = end($appName);
                $envName = ($appEnv === 'stg' || $appEnv === 'uat' || $appEnv === 'dev') ? 'sandbox' : 'production';

                foreach ($paymentConfigurations as $issuer => $gateways) {
                    $country = $issuer !== 'allAll2all' ? substr($issuer, 0, 2) : 'all';
                    $city = $issuer !== 'allAll2all' ? substr($issuer, 2, 3) : 'All';

                    foreach ($gateways as $gatewayKey => $gatewayValues) {
                        $pspInfo = DB::table('payment_service_providers')->where(['psp_code' => $gatewayKey])->first();
                        $paymentAccountData = ['pa_xref_psp_id' => $pspInfo->psp_id];
                        $configurations = [
                            'pc_project' => $client,
                            'pc_country' => $country,
                            'pc_city' => $city,
                            'pc_is_active' => false,
                        ];

                        if (in_array($gatewayKey, $this->envVariableGateways, true)) {
                            $gatewayValues = $this->getEnvpayContent($gatewayValues);
                        }

                        $isPayLater = $gatewayKey === 'pay_later';
                        if ($isPayLater) {
                            $this->addPayLaterPayment($configurations);

                            continue;
                        }

                        foreach ($this->configs as $config) {
                            if (!empty($gatewayValues[$config])) {
                                $paymentAccountData['pa_type'] = $config;
                                $paymentAccountData['pa_info'] = json_encode($this->getPaymentAccountInfo($gatewayValues[$config]));
                                $paymentAccountData['pa_name'] = ucfirst($gatewayKey).' '.$issuer;
                                $configurations['pc_is_active'] = ($envName === $config);
                                $this->createPayment($paymentAccountData, $configurations);
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }

    // Get project
    private function getProjectId()
    {
        $project = getenv('CLIENT');
        $projectCode = null;

        switch ($project) {
            case 'gss-us':
                $projectCode = 'us';

                break;

            case 'srf-fr':
                $projectCode = 'srf_fr';

                break;

            case 'hmpo-uk':
                $projectCode = 'hmpo_uk';

                break;

            case 'leg-be':
                $projectCode = 'leg_be';

                break;

            case 'leg-de':
                $projectCode = 'leg_de';

                break;

            case 'biolab-ma':
                $projectCode = 'biolab_ma';

                break;

            default:
                $projectCode = substr($project, -2);
        }

        return $projectCode;
    }

    // get environment variables
    private function getEnvpayContent($gateway)
    {
        foreach ($this->configs as $config) {
            if (isset($gateway[$config]) && !empty($gateway[$config])) {
                foreach ($gateway[$config] as $key => $val) {
                    if ($key === 'host') {
                        continue;
                    }
                    $gateway[$config][$key] = env(str_replace('ENVPAY_', '', $val));
                }
            }
        }

        return $gateway;
    }

    // get payment account info
    private function getPaymentAccountInfo($info)
    {
        $account_info = [];
        foreach ($info as $info_key => $info_val) {
            if (strpos($info_key, 'host') !== false) {
                continue;
            }
            if (strpos($info_key, 'sandbox_') !== false) {
                $info_key = str_replace('sandbox_', '', $info_key);
            }
            $account_info[$info_key] = $info_val;
        }

        return $account_info;
    }

    /**
     * @param array $paymentAccountData
     * @param array $configurations
     *
     * @return bool
     */
    private function createPayment(array $paymentAccountData, array $configurations): void
    {
        // Create Payment Account
        DB::table('payment_accounts')->insert($paymentAccountData);

        // Get pc_xref_pa_id
        unset($paymentAccountData['pa_info']);
        $account = DB::table('payment_accounts')->where($paymentAccountData)->first();

        // Create Payment Configuration
        $configurations['pc_xref_pa_id'] = $account->pa_id;
        DB::table('payment_configurations')->insert($configurations);
    }

    /**
     * @param array $configurations
     *
     * @return void
     */
    private function addPayLaterPayment(array $configurations): void
    {
        $configurations['pc_xref_pa_id'] = 1;
        $configurations['pc_is_active'] = true;
        DB::table('payment_configurations')->insert($configurations);
    }

    /**
     * @return void
     */
    private function createPayLaterPaymentAccount(): void
    {
        $payLater = [
            'pa_name' => 'Pay Later',
            'pa_type' => 'pay_later',
            'pa_info' => '',
            'pa_xref_psp_id' => 1,
        ];
        DB::table('payment_accounts')->insert($payLater);
    }
}
