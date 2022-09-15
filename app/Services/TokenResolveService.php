<?php

namespace App\Services;

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
     * @param string $issuer
     * @param string $lang
     * @param string $fg_id
     *
     * @throws \Exception
     *
     * @return array
     */
    public function resolveTemplate(
        array $template,
        string $issuer,
        string $lang,
        string $fg_id
    ): array {
        $data = [];
        $this->issuer = $issuer;
        $this->country = substr($this->issuer, 0, 2);
        $this->city = substr($this->issuer, 2, 3);
        $this->client = substr($this->issuer, 6, 2);
        if (empty($template)) {
            return $data;
        }
        $data = $this->getCorrectCollectionTranslation($template);

        if (empty($data['email_content']) && empty($data['invoice_content'])) {
            return $data;
        }

        $listOfToken = $this->pregMatchTemplate($data);

        if (empty($listOfToken)) {
            return $data;
        }

        $resolvedTokens = $this->getResolvedTokens(
            $listOfToken,
            $lang,
            $fg_id
        );

        if (empty($resolvedTokens)) {
            throw new \Exception('Token were not resolved');
        }

        foreach ($resolvedTokens as $token => $value) {
            $data['email_content'] = str_replace($token, $value, $data['email_content']);
            $data['invoice_content'] = str_replace($token, $value, $data['invoice_content']);
        }

        return $data;
    }

    /**
     * @param array $content
     *
     * @return array
     */
    private function pregMatchTemplate(array $content): array
    {
        $pattern = '~({{\\w+:\\w+:\\w+}}|{{\\w+:\\w+}})~';

        preg_match_all($pattern, $content['email_content'], $email_tokens);
        preg_match_all($pattern, $content['invoice_content'], $invoice_tokens);
        if (count($email_tokens)) {
            $tokens[] = array_unique($email_tokens, SORT_REGULAR)[0];
        }
        if (count($invoice_tokens)) {
            $tokens[] = array_unique($invoice_tokens, SORT_REGULAR)[0];
        }
        // will hold all tokens from email and invoice content
        return array_unique($tokens, SORT_REGULAR)[0];
    }

    /**
     * @param array  $tokens
     * @param string $lang
     * @param string $fg_id
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getResolvedTokens(
        array $tokens,
        string $lang,
        string $fg_id
    ): array {
        $resolved_tokens = [];

        foreach ($tokens as $token) {
            /*
             * Token structure
             * {{collection : collection_name : field_name}}
             * {{application : field_name}}
             */
            $token_details = explode(':', str_replace(['{{', '}}'], '', $token));

            if ('c' == $token_details[0]) {  // if collection token - directus
                $resolved_tokens[$token] = $this->getTokenTranslationFromDirectus($token_details, $lang);
            } elseif ('a' == $token_details[0]) { // if application token - api call
                $resolved_tokens[$token] = $this->getTokenTranslationFromApplication($token_details, $fg_id);
            }
        }

        return $resolved_tokens;
    }

    /**
     * @param array $collections
     *
     * @throws \Exception
     *
     * @return array
     */
    private function getCorrectCollectionTranslation(array $collections): array
    {
        $numberOfCollections = count($collections);
        $globalOnly = 1 === $numberOfCollections;
        $hasCity = $numberOfCollections > 2;

        $collectionIndex = null;
        foreach ($collections as $i => $collection) {
            $code = $collection['code'];
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

        if (null === $collectionIndex) {
            throw new \Exception('Correct collection index not found');
        }

        $translation = $this->getActiveTranslation($collections[$collectionIndex]['translation']);

        if (empty($translation)) {
            throw new \Exception('No active translation found');
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
     * @param array  $token_details
     * @param string $lang
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getTokenTranslationFromDirectus(array $token_details, string $lang): string
    {
        $collection = $token_details[1];
        $field = 'translation.'.$token_details[2];
        $options['lang'] = $lang;
        $select = 'code,'.$field;
        $issuer_filter = [
            $this->city,
            $this->country,
            'ww',
        ];

        if ('application_centers' == $token_details[1]) {
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
            throw new \Exception('No collections returned for token: '.$collection.'.'.$field);
        }

        if (count($tokenCollections) > 1) {
            $translation = $this->getCorrectCollectionTranslation($tokenCollections);
        } else {
            $translation = $this->getActiveTranslation(array_first($tokenCollections)['translation']);
        }

        return array_first($translation);
    }

    /**
     * @param array  $token_details
     * @param string $fg_id
     *
     * @throws \Exception
     *
     * @return string
     */
    private function getTokenTranslationFromApplication(array $token_details, string $fg_id): string
    {
        $translations = [];
        $applicationsResponse = $this->apiService->callTlsApi('GET', '/tls/v2/'.$this->client.'/forms_in_group/'.$fg_id);
        if (200 != $applicationsResponse['status']) {
            throw new \Exception('No applicant details returned for token: '.$token_details[1]);
        }
        foreach ($applicationsResponse['body'] as $applicant) {
            $translations[] = $applicant[$token_details[1]];
        }
        if (empty($translations)) {
            return '';
        }

        return implode(', ', array_unique($translations, SORT_REGULAR));
    }
}
