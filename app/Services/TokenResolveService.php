<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class TokenResolveService
{
    /**
     * @var DirectusService
     */
    protected $directusService;

    /**
     * @var ApiService
     */
    protected $apiService;

    /**
     * @var string
     */
    private $issuer;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $city;

    /**
     * @var string
     */
    private $client;

    /**
     * @param DirectusService $directusService
     * @param ApiService      $apiService
     */
    public function __construct(DirectusService $directusService, ApiService $apiService)
    {
        $this->directusService = $directusService;
        $this->apiService = $apiService;
    }

    /**
     * @param array $template
     * @param array $transaction
     *
     * @return array
     *
     * @throws \Exception
     */
    public function resolveTemplate(
        array $template,
        array $transaction
    ): array {
        $data = [];
        $this->issuer = $transaction['t_issuer'];
        $this->country = substr($this->issuer, 0, 2);
        $this->city = substr($this->issuer, 2, 3);
        $this->client = substr($this->issuer, 6, 2);
        if (empty($template)) {
            return $data;
        }
        $data = $this->getCorrectCollectionTranslation($template, 'tlspay_email_invoice');

        if (empty($data['invoice_content'])) {
            return $data;
        }

        $listOfToken = $this->pregMatchTemplate($data, 'invoice_content');

        if (empty($listOfToken)) {
            return $data;
        }

        $resolvedTokens = $this->getResolvedTokens(
            $listOfToken,
            $transaction
        );

        if (empty($resolvedTokens)) {
            throw new \Exception('Token were not resolved');
        }

        foreach ($resolvedTokens as $token => $value) {
            $data['invoice_content'] = str_replace($token, $value, $data['invoice_content']);
        }

        return $data;
    }

    /**
     * @param array  $template
     * @param array  $transaction
     * @param string $lang
     *
     * @return array
     *
     * @throws \Exception
     */
    public function resolveReceiptTemplate(
        array $template,
        array $transaction,
        string $lang
    ): array {
        $data = [];
        $this->issuer = $transaction['t_issuer'];
        $this->country = substr($this->issuer, 0, 2);
        $this->city = substr($this->issuer, 2, 3);
        $this->client = substr($this->issuer, 6, 2);
        if (empty($template)) {
            return $data;
        }
        $data = $this->getCorrectCollectionTranslation($template, 'tlspay_receipts');

        if (empty($data['receipt_content'])) {
            return $data;
        }

        $listOfToken = $this->pregMatchTemplate($data, 'receipt_content');
        if (empty($listOfToken)) {
            return $data;
        }

        $resolvedTokens = $this->getResolvedTokens(
            $listOfToken,
            $transaction,
            $lang
        );

        if (empty($resolvedTokens)) {
            throw new \Exception('Token were not resolved');
        }

        foreach ($resolvedTokens as $token => $value) {
            $data['receipt_content'] = str_replace($token, $value, $data['receipt_content']);
        }

        return $data;
    }

    /**
     * @param array $transaction
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getTokenTranslationForPurchasedServices(array $transaction): string
    {
        $transactionItems = $transaction['t_items'];

        if (empty($transactionItems)) {
            throw new \Exception('No Transaction items found');
        }

        $collection = 'basket';
        $selectFields = 'content, meta_tokens';
        $selectFilters = [
            'status' => [
                'eq' => 'published',
            ],
        ];
        $options = ['type' => 'Purchased services'];
        $response = $this->directusService->getContent(
            $collection,
            $selectFields,
            $selectFilters,
            $options
        );

        if (empty($response)) {
            throw new \Exception('No item found for the collection - ' . $collection);
        }

        $response = array_first($response);
        $basketContent = $response['content'];
        $basketTokens = $this->getBasketTokens($basketContent);

        if (empty($basketTokens)) {
            return $basketContent;
        }

        $transactionCurrency = $transaction['t_currency'];
        $basketServiceValues = $this->getBasketServiceValues($transactionItems, $transactionCurrency);
        $basketValues = [
            'currency' => $transactionCurrency,
            'amount' => $transaction['t_amount'],
            'total_with_tax' => $this->formatDecimalNumber((float) $transaction['t_amount']),
            'total_without_tax' => $this->formatDecimalNumber((float) $basketServiceValues['price_without_vat']),
            'tax' => $this->formatDecimalNumber((float) $basketServiceValues['vat']),
            'tax_percent' => $basketServiceValues['vat_percent'],
        ];

        $metaContents = $this->getResolvedMetaTokensContents($response['meta_tokens'], $basketServiceValues['services']);

        return $this->resolveBasketContent(
            $basketContent,
            $basketTokens,
            $basketValues,
            $metaContents
        );
    }

    /**
     * @param array  $transactionItems
     * @param string $transactionCurrency
     *
     * @return array
     */
    private function getBasketServiceValues(array $transactionItems, string $transactionCurrency): array
    {
        $basketValues = [
            'services' => [],
            'vat' => 0,
            'vat_percent' => 0,
            'price_without_vat' => 0,
        ];
        foreach ($transactionItems as $item) {
            foreach ($item['skus'] as $service) {
                $sku = $service['sku'];
                $productName = $service['product_name'];
                $quantity = $service['quantity'];
                $price = $service['price'];
                $vat = ($price / (1 + $service['vat'] / 100)) * $service['vat'] / 100;
                $vatPercent = $service['vat'];
                $basketValues['services'][$sku]['product_name'] = $productName;
                $basketValues['services'][$sku]['currency'] = $transactionCurrency;

                if (!array_key_exists('quantity', $basketValues['services'][$sku])) {
                    $basketValues['services'][$sku]['quantity'] = 0;
                }
                $basketValues['services'][$sku]['quantity'] += $quantity;

                if (!array_key_exists('price', $basketValues['services'][$sku])) {
                    $basketValues['services'][$sku]['price'] = 0;
                }
                $basketValues['services'][$sku]['price'] += $price;
                $basketValues['vat'] += round($vat, 2) * $quantity;
                $basketValues['price_without_vat'] += ($price - $vat) * $quantity;
            }
        }
        $basketValues['vat_percent'] = $vatPercent . '%' ?? '0%';

        return $basketValues;
    }

    /**
     * @param string $language
     * @param string $sku
     *
     * @return array
     */
    private function getSkuTranslationsFromDirectus(string $language, string $sku): array
    {
        $collection = 'sku_translations';
        $selectFields = 'product_name,sku';
        $selectFilters = [
            'status' => [
                'eq' => 'published',
            ],
            'translation' => [
                'eq' => $language,
            ],
            'sku' => [
                'eq' => $sku,
            ],
        ];

        $skuTranslations = $this->directusService->getContent(
            $collection,
            $selectFields,
            $selectFilters
        );

        foreach ($skuTranslations as $product) {
            $translatedSKU[$product['sku']] = $product['product_name'];
        }

        return $translatedSKU ?? [];
    }

    /**
     * @param string $basketContent
     * @param array  $basketTokens
     * @param array  $basketValues
     * @param array  $metaContents
     *
     * @return string
     */
    private function resolveBasketContent(
        string $basketContent,
        array $basketTokens,
        array $basketValues,
        array $metaContents
    ): string {
        foreach ($basketTokens['meta'] as $token => $tokenName) {
            $basketContent = str_replace($token, $metaContents[$tokenName], $basketContent);
        }

        foreach ($basketTokens['normal'] as $token => $tokenName) {
            if (!isset($basketValues[$tokenName])) {
                continue;
            }
            $basketContent = str_replace($token, $basketValues[$tokenName], $basketContent);
        }

        return $basketContent;
    }

    /**
     * @param array $basketServiceTokensContents
     * @param array $basketServices
     *
     * @return array
     */
    private function getResolvedMetaTokensContents(array $basketServiceTokensContents, array $basketServices): array
    {
        $servicesTableItems = [];
        foreach ($basketServiceTokensContents as $metaToken => $metaContent) {
            $contentTokens = $this->getBasketTokens($metaContent);
            if ($metaToken === 'META_service_rows') {
                $servicesTableItems[$metaToken] = $this->getResolvedBasketServiceTokens($metaContent, $contentTokens['normal'], $basketServices);
            }
        }

        return $servicesTableItems;
    }

    /**
     * @param string $content
     * @param array  $tokens
     * @param array  $basketServices
     *
     * @return string
     */
    private function getResolvedBasketServiceTokens(
        string $content,
        array $tokens,
        array $basketServices
    ): string {
        $basketServicesContent = '';
        foreach ($basketServices as $sku => $service) {
            $serviceContent = $content;
            foreach ($tokens as $token => $tokenName) {
                $getProductNameLanguage = explode(':', $tokenName);

                if (count($getProductNameLanguage) > 1) {
                    $language = $getProductNameLanguage[1];
                    $translatedSku = $this->getSkuTranslationsFromDirectus($language, $sku);

                    if (empty($translatedSku)) {
                        $serviceContent = str_replace($token, $service[array_first($getProductNameLanguage)], $serviceContent);
                    } else {
                        $serviceContent = str_replace($token, $translatedSku[$sku], $serviceContent);
                    }
                } else {
                    $serviceContent = str_replace($token, $service[$tokenName], $serviceContent);
                }
            }
            $basketServicesContent .= $serviceContent;
        }

        return $basketServicesContent;
    }

    /**
     * @param float $amount
     *
     * @return string
     */
    private function formatDecimalNumber(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * @param array  $content
     * @param string $contentName
     *
     * @return array
     */
    private function pregMatchTemplate(array $content, string $contentName): array
    {
        $pattern = '~({{\\w+:\\w+:\\w+}}|{{\\w+:\\w+}})~';
        $tokens = [];
        preg_match_all($pattern, $content[$contentName], $invoiceReceiptTokens);
        if (count($invoiceReceiptTokens)) {
            $tokens = array_unique($invoiceReceiptTokens, SORT_REGULAR)[0];
        }
        // will hold all tokens from invoice content
        return array_unique($tokens, SORT_REGULAR);
    }

    /**
     * @param array  $tokens
     * @param array  $transaction
     * @param string $language
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getResolvedTokens(
        array $tokens,
        array $transaction
    ): array {
        $resolvedTokens = [];
        foreach ($tokens as $token) {
            /*
             * Token structure
             * {{collection : collection_name : field_name}}
             * {{application : field_name}}
             * {{basket : services}}
             */
            $tokenDetails = explode(':', str_replace(['{{', '}}'], '', $token));

            $tokenPrefixRule = $tokenDetails[0];
            if ($tokenPrefixRule === 'c') {
                $resolvedTokens[$token] = $this->getTokenTranslationFromDirectus($tokenDetails, ($transaction['t_language'] !== null) ? $transaction['t_language'] : 'en-us');
            } elseif ($tokenPrefixRule === 'a') {
                $resolvedTokens[$token] = $this->getApplicationTokenValues($tokenDetails, $transaction);
            } elseif ($tokenPrefixRule === 'basket') {
                $resolvedTokens[$token] = $this->getTokenTranslationForPurchasedServices($transaction);
            }
        }

        return $resolvedTokens;
    }

    /**
     * @param array $tokenDetails
     * @param array $transaction
     *
     * @return string
     */
    private function getApplicationTokenValues(array $tokenDetails, array $transaction): string
    {
        switch ($tokenDetails[1]) {
            case 'f_pers_surnames':
                $tokenValue = $this->getTokenTranslationFromApplication($tokenDetails, $transaction['t_xref_fg_id']);

                break;

            case 'f_pers_addr_is_owned':
                $tokenValue = $this->getTokenTranslationFromApplication($tokenDetails, $transaction['t_xref_fg_id']);

                break;

            case 'qr_code':
                $tokenValue = $this->getQrCodeFromApplication($tokenDetails, $transaction['t_xref_fg_id']);

                break;

            case 'customer_references':
                $tokenValue = $this->getCustomerReferences($transaction);

                break;

            case 'order_id':
                $tokenValue = $transaction['t_transaction_id'];

                break;

            case 'receipt_date':
                $tokenValue = date('Y-m-d h:i:s a');

                break;

            case 'appointment_date_time':
                $tokenValue = ($transaction['t_appointment_time'] == '00:00:00') ? $transaction['t_appointment_date'] : $transaction['t_appointment_date'] . ' ' . $transaction['t_appointment_time'];

                break;
        }

        return $tokenValue ?? '';
    }

    /**
     * @param array  $collections
     * @param string $collectionName
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getCorrectCollectionTranslation(array $collections, string $collectionName): array
    {
        $numberOfCollections = count($collections);
        $globalOnly = 1 === $numberOfCollections;
        $hasCity = $numberOfCollections > 2;
        $collectionIndex = null;
        $collectionGlobalIndex = null;
        $code = '';
        foreach ($collections as $i => $collection) {
            if (empty($collection['translation'])) {
                continue;
            }
            if ($collectionName == 'application_center_detail') {
                $code = $collection['application_center']['code'];
            } else {
                $code = $collection['code'];
            }

            if ('ww' == $code || 'wwWWW2ww' == $code) {
                $collectionGlobalIndex = $i;
            }
            if ($code == $this->issuer) {
                $collectionIndex = $i;

                break;
            }
            if ($code == $this->city) {
                $collectionIndex = $i;

                break;
            }
            if (!$hasCity && $code == $this->country) {
                $collectionIndex = $i;

                break;
            }
            if ($globalOnly && 'ww' == $code) {
                $collectionIndex = $i;
            }
        }
        if (null !== $collectionIndex) {
            $translation = $this->getActiveTranslation($collections[$collectionIndex]['translation']);
        }

        if (empty($translation)) {
            if (null === $collectionGlobalIndex) {
                Log::error('Correct collection index not found for collection: ' . $collectionName . ' - code:' . $code);

                return [];
            }

            $translationGlobal = $this->getActiveTranslation($collections[$collectionGlobalIndex]['translation']);

            if (empty($translationGlobal)) {
                Log::error('No active translation found for collection: ' . $collectionName . ' - code: ' . $code);

                return [];
            }

            return $translationGlobal;
        }

        return $translation;
    }

    /**
     * @param array $translations
     *
     * @return array
     */
    private function getActiveTranslation(array $translations): array
    {
        foreach ($translations as $translation) {
            if (!isset($translation['activation'])) {
                return $translation;
            }
            if ($translation['activation']) {
                return $translation;
            }
        }

        return [];
    }

    /**
     * @param array  $tokenDetails
     * @param string $language
     *
     * @return null|string
     *
     * @throws \Exception
     */
    private function getTokenTranslationFromDirectus(array $tokenDetails, string $language): ?string
    {
        $collection = $tokenDetails[1];
        $field = ($tokenDetails[2] === 'name') ? $tokenDetails[2] : 'translation.' . $tokenDetails[2];
        $options['lang'] = $language;
        $select = 'code,' . $field;
        $issuer_filter = [
            $this->city,
            $this->country,
            'ww',
        ];

        if ($tokenDetails[1] === 'application_centers') {
            $issuer_filter = [$this->issuer, 'wwWWW2ww'];
        }
        $filters = [
            'code' => [
                'in' => $issuer_filter,
            ],
            'status' => [
                'eq' => 'published',
            ],
        ];
        if ($tokenDetails[1] === 'application_center_detail') {
            $filters = [
                'detail_code' => [
                    'contains' => $tokenDetails[2],
                ],
                'status' => [
                    'eq' => 'published',
                ],
            ];
            $select = 'application_center.code,translation.value';
        }
        $tokenCollections = $this->directusService->getContent(
            $collection,
            $select,
            $filters,
            $options
        );

        if (empty($tokenCollections)) {
            Log::error('No collections returned for token with issuer:' . $this->issuer . ' - ' . $collection . '.' . $field);

            return '';
        }

        if (filled($tokenCollections) && $tokenDetails[2] !== 'name') {
            $translation = $this->getCorrectCollectionTranslation($tokenCollections, $tokenDetails[1]);
        } else {
            if ($tokenDetails[2] == 'name' && filled($tokenCollections)) {
                foreach ($tokenCollections as $getName) {
                    if ($getName['code'] === $this->issuer) {
                        $translation = $getName['name'];

                        break;
                    }
                    if ($getName['code'] === 'wwWWW2ww') {
                        $translation = $getName['name'];
                    }
                }

                return $translation ?? '';
            }
            if (empty(array_first($tokenCollections)['translation'])) {
                Log::error('No Translation found for token with issuer:' . $this->issuer . ' - ' . $collection . '.' . $field);

                return '';
            }
            $translation = $this->getActiveTranslation(array_first($tokenCollections)['translation']);
        }

        return array_first($translation);
    }

    /**
     * @param array  $tokenDetails
     * @param string $fg_id
     *
     * @return string
     */
    private function getTokenTranslationFromApplication(array $tokenDetails, string $fg_id): string
    {
        $translations = [];
        $applicationsResponse = $this->apiService->callTlsApi('GET', '/tls/v2/' . $this->client . '/forms_in_group/' . $fg_id);
        if ($applicationsResponse['status'] != 200 || empty($applicationsResponse['body'])) {
            Log::error('No applicant details returned from TLS API for token: ' . $tokenDetails[1] . '- form group :' . $fg_id);

            return '';
        }
        foreach ($applicationsResponse['body'] as $applicant) {
            $translations[] = ($tokenDetails[1] == 'f_pers_surnames') ? $applicant['f_pers_givennames'] . ' ' . $applicant[$tokenDetails[1]] : $applicant[$tokenDetails[1]];
        }

        return implode(', ', array_unique($translations, SORT_REGULAR));
    }

    /**
     * @param array  $tokenDetails
     * @param string $fg_id
     *
     * @return string
     */
    private function getQrCodeFromApplication(array $tokenDetails, string $fg_id): string
    {
        $response = $this->apiService->callTlsApi('GET', '/tls/v1/' . $this->client . '/receipt_qrcode?fg_id=' . $fg_id . '&type=all_receipts&occurence=1&scale=5');
        if ($response['status'] != 200) {
            Log::error('No QrCode details returned from TLS API for token: ' . $tokenDetails[1] . '- form group :' . $fg_id);

            return '';
        }

        return 'data:image/png;base64,' . base64_encode($response['body']->getContents()) ?? '';
    }

    /**
     * @param $transaction
     *
     * @return string
     */
    private function getCustomerReferences(array $transaction): string
    {
        $customerReferences = [];
        foreach ($transaction['t_items'] as $item) {
            foreach ($item['skus'] as $service) {
                $customerReferences[$item['f_id']] = $service['customer_reference'];
            }
        }

        if (empty($customerReferences)) {
            return '';
        }

        return implode(', ', array_values($customerReferences));
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function getBasketTokens(string $content): array
    {
        $tokenList = [];
        $pattern = '~({{\\w+}}|{{\\w+:\\w+}})~';

        preg_match_all($pattern, $content, $allTokens);
        if (count($allTokens)) {
            $allTokens = array_unique($allTokens, SORT_REGULAR)[0];
        }

        foreach ($allTokens as $token) {
            $checkIfMeta = substr($token, 0, 7);
            $tokenName = str_replace(['{{', '}}'], '', $token);

            if ($checkIfMeta === '{{META_') {
                $tokenList['meta'][$token] = $tokenName;
            } else {
                $tokenList['normal'][$token] = $tokenName;
            }
        }

        return $tokenList;
    }
}
