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
     * @param array  $template
     * @param array  $transaction
     * @param string $lang
     *
     * @throws \Exception
     *
     * @return array
     */
    public function resolveTemplate(
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
        $data = $this->getCorrectCollectionTranslation($template, 'tlspay_email_invoice');

        if (empty($data['invoice_content'])) {
            return $data;
        }

        $listOfToken = $this->pregMatchTemplate($data);

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
            $data['invoice_content'] = str_replace($token, $value, $data['invoice_content']);
        }

        return $data;
    }

    /**
     * @param array $transaction
     *
     * @throws \Exception
     *
     * @return string
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
            'price_without_vat' => 0,
        ];
        foreach ($transactionItems as $item) {
            foreach ($item['skus'] as $service) {
                $sku = $service['sku'];
                $productName = $service['product_name'];
                $quantity = $service['quantity'];
                $price = $service['price'];
                $vat = ($service['vat'] / 100 * $price);

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

                $basketValues['vat'] += $vat;
                $basketValues['price_without_vat'] += ($price - $vat);
            }
        }

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
     * @param array $content
     *
     * @return array
     */
    private function pregMatchTemplate(array $content): array
    {
        $pattern = '~({{\\w+:\\w+:\\w+}}|{{\\w+:\\w+}})~';
        $tokens = [];
        preg_match_all($pattern, $content['invoice_content'], $invoice_tokens);
        if (count($invoice_tokens)) {
            $tokens = array_unique($invoice_tokens, SORT_REGULAR)[0];
        }
        // will hold all tokens from invoice content
        return array_unique($tokens, SORT_REGULAR);
    }

    /**
     * @param array  $tokens
     * @param array  $transaction
     * @param string $lang
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getResolvedTokens(
        array $tokens,
        array $transaction,
        string $lang
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
                $resolvedTokens[$token] = $this->getTokenTranslationFromDirectus($tokenDetails, $lang);
            } elseif ($tokenPrefixRule === 'a' && $tokenDetails[1] === 'f_pers_surnames') {
                $resolvedTokens[$token] = $this->getTokenTranslationFromApplication($tokenDetails, $transaction['t_xref_fg_id']);
            } elseif ($tokenPrefixRule === 'a' && $tokenDetails[1] === 'qr_code') {
                $resolvedTokens[$token] = $this->getQrCodeFromApplication($tokenDetails, $transaction['t_xref_fg_id']);
            } elseif ($tokenPrefixRule === 'basket') {
                $resolvedTokens[$token] = $this->getTokenTranslationForPurchasedServices($transaction);
            }
        }

        return $resolvedTokens;
    }

    /**
     * @param array  $collections
     * @param string $collectionName
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getCorrectCollectionTranslation(array $collections, string $collectionName): array
    {
        $numberOfCollections = count($collections);
        $globalOnly = 1 === $numberOfCollections;
        $hasCity = $numberOfCollections > 2;

        $collectionIndex = null;
        $collectionGlobalIndex = null;
        foreach ($collections as $i => $collection) {
            if (empty($collection['translation'])) {
                continue;
            }
            $code = $collection['code'];
            if ('ww' == $code || $this->issuer == $code) {
                $collectionGlobalIndex = $i;
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
        if (null != $collectionIndex) {
            $translation = $this->getActiveTranslation($collections[$collectionIndex]['translation']);
        }

        if (empty($translation)) {
            if (null === $collectionGlobalIndex) {
                Log::error('Correct collection index not found for collection: ' . $collectionName . ' - code:' . $code);

                return '';
            }

            $translationGlobal = $this->getActiveTranslation($collections[$collectionGlobalIndex]['translation']);

            if (empty($translationGlobal)) {
                Log::error('No active translation found for collection: ' . $collectionName . ' - code: ' . $code);

                return '';
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
     * @param string $lang
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getTokenTranslationFromDirectus(array $tokenDetails, string $lang): string
    {
        $collection = $tokenDetails[1];
        $field = 'translation.' . $tokenDetails[2];
        $options['lang'] = $lang;
        $select = 'code,' . $field;
        $issuer_filter = [
            $this->city,
            $this->country,
            'ww',
        ];

        if ($tokenDetails[1] === 'application_centers') {
            $issuer_filter = [$this->issuer, 'ww'];
        }
        $filters = [
            'code' => [
                'in' => $issuer_filter,
            ],
            'status' => [
                'eq' => 'published',
            ],
        ];

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

        if (count($tokenCollections) > 1) {
            $translation = $this->getCorrectCollectionTranslation($tokenCollections, $tokenDetails[1]);
        } else {
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
            $translations[] = $applicant[$tokenDetails[1]];
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
