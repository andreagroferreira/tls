<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api/v1', 'namespace' => 'V1'], function () use ($router) {
    // transaction api
    $router->get('transaction/{fg_id}', 'TransactionController@fetch');
    $router->get('form_transaction/{f_id}', 'TransactionController@fetchByForm');
    $router->post('transaction', 'TransactionController@create');
    $router->get('transactions', 'TransactionController@fetchAll');

    // form group api
    $router->get('form_group/{fg_id}', 'FormGroupController@fetch');

    // invoice api
    $router->get('invoice/{transaction_id}', 'InvoiceController@fetch');

    // recommendation rule engine api
    $router->post('rcd_rule', 'RecommendationRuleEngineController@fetch');

    // recommendation avs api
    $router->get('avs_recommendation/{f_id}', 'AvsRecommendationController@fetch');

    //recommendation result api
    $router->post('rcd_result', 'RecommendationResultController@create');
    $router->get('rcd_result/{f_id}', 'RecommendationResultController@fetchAll');
    $router->delete('rcd_result/{rcd_id}', 'RecommendationResultController@delete');

    $router->get('checkout/{t_id}', 'CheckoutController@checkout');

    $router->post('pay_later/redirto', 'PayLaterController@redirto');
    $router->post('pay_later/return', 'PayLaterController@return');

    $router->post('cmi/redirto', 'CmiController@redirto');
    $router->post('cmi/return', 'CmiController@return');
    $router->post('cmi/notify', 'CmiController@notify');

    $router->post('paygate/redirto', 'PaygateController@redirto');
    $router->post('paygate/return', 'PaygateController@return');
    $router->post('paygate/notify', 'PaygateController@notify');

    $router->post('tingg/redirto', 'TinggController@redirto');
    $router->post('tingg/return', 'TinggController@return');
    $router->post('tingg/notify', 'TinggController@notify');

    $router->post('fawry/redirto', 'FawryController@redirto');
    $router->post('fawry/return', 'FawryController@return');
    $router->post('fawry/notify', 'FawryController@notify');

    $router->post('paypal/redirto', 'PayPalController@redirto');
    $router->post('paypal/return', 'PayPalController@return');
    $router->post('paypal/notify', 'PayPalController@notify');
    $router->get('paypal/wait', 'PayPalController@wait');

    $router->post('globaliris/return', 'GlobalirisController@return');
    $router->get('globaliris/redirect', 'GlobalirisController@redirect');
    $router->post('globaliris/redirto', 'GlobalirisController@redirto');

    $router->post('payu/redirto', 'PayuController@redirto');
    $router->post('payu/return', 'PayuController@return');

    $router->post('pay_bank/redirto', 'PayBankController@redirto');
    $router->post('pay_bank/return', 'PayBankController@return');
    $router->post('pay_bank/notify', 'PayBankController@notify');

    $router->post('k-bank/redirto', 'KBankController@redirto');
    $router->post('k-bank/return', 'KBankController@return');
    $router->post('k-bank/notify', 'KBankController@notify');
    $router->post('k-bank/config', 'KBankController@fetchConfig');

    $router->post('payfort/redirto', 'PayfortController@redirto');
    $router->post('payfort/return', 'PayfortController@return');
    $router->post('payfort/notify', 'PayfortController@notify');

    $router->post('clictopay/redirto', 'ClictopayController@redirto');
    $router->post('clictopay/return', 'ClictopayController@return');

    $router->post('paysoft/redirto', 'PaysoftController@redirto');
    $router->post('paysoft/notify', 'PaysoftController@notify');
    $router->post('paysoft/return', 'PaysoftController@return');

    $router->post('alipay/redirto', 'AlipayController@redirto');
    $router->post('alipay/notify', 'AlipayController@notify');
    $router->post('alipay/return', 'AlipayController@return');

    $router->post('switch/redirto', 'SwitchController@redirto');
    $router->post('switch/return', 'SwitchController@return');

    //get transaction sync fail job or resend fail job
    $router->get('/retry_failed_queue/{queue_name}', 'QueueController@resend');
    $router->get('/health', 'QueueController@health');

    $router->post('binga/redirto', 'BingaController@redirto');
    $router->post('binga/notify', 'BingaController@notify');
    $router->post('binga/return', 'BingaController@return');

    $router->post('bnp/redirto', 'BnpController@redirto');
    $router->post('bnp/return', 'BnpController@return');
    $router->post('bnp/receipt', 'BnpController@receipt');

    $router->post('yookassa/redirto', 'YookassaController@redirto');
    $router->post('yookassa/return', 'YookassaController@return');

    // profile
    $router->post('profile', 'ProfileController@upload');
    $router->get('application-with-profile/{profile}', 'ProfileController@fetchApplications');
    $router->get('application-profile/{f_id}', 'ProfileController@fetchProfile');
    $router->post('application-profiles', 'ProfileController@fetchMultiProfiles');

    // recommendation config api
    $router->post('recommendation-config-upload', 'RecommendationConfigController@upload');
    $router->get('recommendation-configs', 'RecommendationConfigController@fetch');
    $router->get('recommendation-config/{rc_id}', 'RecommendationConfigController@download');

    // payment configurations api
    $router->options('payment-configurations-list', 'PaymentConfigurationsController@fetchList');
    $router->get('payment-configurations-list', 'PaymentConfigurationsController@fetchList');
    $router->put('payment-configurations/{pa_id}', 'PaymentConfigurationsController@update');
    $router->get('payment-accounts', 'PaymentAccountsController@getPaymentAccounts');
    $router->get('payment-exists-config', 'PaymentAccountsController@getPaymentExistsConfig');
    $router->post('add-payment-config', 'PaymentConfigurationsController@addPaymentConfig');


});

