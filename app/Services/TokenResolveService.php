<?php


namespace App\Services;

class TokenResolveService
{
    /**
     * @var DirectusService
     * @var ApiService
     */
    protected DirectusService $directusService;
    protected ApiService $apiService;
    /**
     * @var string
     */
    private string $issuer;

    /**
     * @var string
     */
    private string $country;

    /**
     * @var string
     */
    private string $city;

    /**
     * @var string
     */
    private string $client;

    /**
     * @param DirectusService $directusService
     * @param ApiService $apiService
     */
    public function __construct(DirectusService $directusService, ApiService $apiService)
    {
        $this->directusService = $directusService;
        $this->apiService = $apiService;
    }

    /**
     * Get Directus template for test purspose
     *
     * @param string $collection
     * @param string $lang
     * @param string $issuer
     * @param string $fg_id
     * @return array
     * @throws \Exception
     */
    public function getTemplateData(string $collection, string $issuer, string $lang, string $fg_id): array
    {
        $this->issuer = $issuer;
        $this->country = substr($this->issuer, 0,2);
        $this->city = substr($this->issuer, 2,3);
        $filters = [
            'code'=>[
                'in' => [
                    $this->city,
                    $this->country,
                    'ww'
                ]
            ],
            'status' => [
                'eq' => 'published'
            ]
        ];
        $select = 'code, translation.email_title, translation.email_content, translation.invoice_content, translation.activation';
        $options['lang'] = $lang;
        $getTemplate = $this->directusService->getContent(
            $collection,
            $select,
            $filters,
            $options
        );
        return $this->resolveTemplate($getTemplate,$issuer, $lang, $fg_id);
    }

    /**
     * @param array $template
     * @param string $issuer
     * @param string $lang
     * @param string $fg_id
     * @return array
     * @throws \Exception
     */

    public function resolveTemplate(array $template,  string $issuer, string $lang, string $fg_id): array
    {
        $data = [];
        $this->issuer = $issuer;
        $this->country = substr($this->issuer, 0, 2);
        $this->city = substr($this->issuer, 2, 3);
        $this->client = substr($this->issuer, 6, 2);
        if (empty($template)) {
            return $data;
        }
        $data = $this->getCorrectCollectionTranslation($template);

        if(empty($data['email_content']) && empty($data['invoice_content'])) {
            return $data;
        }

        $listOfToken = $this->pregMatchTemplate($data);

        if (empty($listOfToken)) {
            return $data;
        }

        $resolvedTokens = $this->getResolvedTokens($listOfToken, $lang, $fg_id);

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
     * returns the list of tokens after preg_match with given pattern
     *
     * @param array $content
     *
     * @return array
     */
    private function pregMatchTemplate(array $content): array
    {
        $pattern = "~({{\w+:\w+:\w+}}|{{\w+:\w+}})~";

        preg_match_all($pattern, $content['email_content'], $email_tokens);
        preg_match_all($pattern, $content['invoice_content'], $invoice_tokens);
        if(count($email_tokens)) {
            $tokens[] = array_unique($email_tokens, SORT_REGULAR)[0];
        }
        if(count($invoice_tokens)) {
            $tokens[] = array_unique($invoice_tokens, SORT_REGULAR)[0];

        }
        // will hold all tokens from email and invoice content
        return array_unique($tokens, SORT_REGULAR)[0];
    }

    /**
     * returns the list of resolved tokens
     *
     * @param array $tokens
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getResolvedTokens(array $tokens, string $lang, string $fg_id): array
    {
        $resolved_tokens = [];

        foreach ($tokens as $token) {
            /*
             * Token structure
             * {{collection : collection_name : field_name}}
             * {{application : field_name}}
             */
            $token_details = explode(':', str_replace(array('{{','}}'), '', $token));

            if($token_details[0] == 'c') {  /* if collection token - directus */
                $resolved_tokens[$token] = $this->getTokenTranslationFromDirectus(
                    $token_details,
                    $lang
                );
            }
            else if($token_details[0] == 'a') { /* if application token - api call */
                $resolved_tokens[$token] = $this->getTokenTranslationFromApplication($token_details,$fg_id);
            }
        }

        return $resolved_tokens;
    }

    /**
     *  get best matched collection item based on city,country & ww
     *
     * @param array $collections
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getCorrectCollectionTranslation(
        array $collections
    ): array
    {
        $numberOfCollections = count($collections);
        $globalOnly = $numberOfCollections === 1;
        $hasCity = $numberOfCollections > 2;

        $collectionIndex = null;
        foreach ($collections as $i => $collection) {
            $code = $collection['code'];
            if($code == $this->city) {
                $collectionIndex = $i;
                break;
            }
            else if(!$hasCity && $code == $this->country) {
                $collectionIndex = $i;
                break;
            }
            else if($globalOnly && $code == 'ww') {
                $collectionIndex = $i;
            }
        }

        if ($collectionIndex === null) {
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
     * calls directus collection to get translation of Token
     *
     * @param array $token_details
     * @param array $issuer_filter
     *
     * @return string
     *
     * @throws \Exception
     */
    private function getTokenTranslationFromDirectus(
        array $token_details,
        string $lang
    ): string
    {
        $collection = $token_details[1];
        $field = 'translation.'.$token_details[2];
        $options['lang'] = $lang;
        $select = "code," . $field;
        $issuer_filter = [
            $this->city,
            $this->country ,
            'ww'
        ];

        if($token_details[1] == 'application_centers') {
            $issuer_filter = [$this->issuer, 'ww'];
        }
        $filters = [
            'code'=>[
                'in' =>  $issuer_filter
            ],
            'status' => [
                'eq' => 'published'
            ],
        ];

        $tokenCollections = $this->directusService->getContent(
            $collection,
            $select,
            $filters,
            $options
        );

        if (empty($tokenCollections)) {
            throw new \Exception('No collections returned for token: ' . $collection . '.' . $field);
        }

        if (count($tokenCollections) > 1) {
            $translation = $this->getCorrectCollectionTranslation($tokenCollections);
        } else {
            $translation = $this->getActiveTranslation(array_first($tokenCollections)['translation']);
        }

        return array_first($translation);
    }

    /**
     * @param array $token_details
     * @param string $fg_id
     * @return string
     * @throws \Exception
     */
    private function getTokenTranslationFromApplication(
        array $token_details,
        string $fg_id
    ): string
    {
        $translations = [];
        $applicationsResponse = $this->apiService->callTlsApi('GET', '/tls/v2/'.$this->client.'/forms_in_group/' . $fg_id);
        if($applicationsResponse['status'] != 200){
            throw new \Exception('No applicant details returned for token: '.$token_details[1]);
        }
        foreach ($applicationsResponse['body'] as $applicant){
            $translations[] = $applicant[$token_details[1]];
        }
        if(empty($translations)){
            return '';
        }
        return implode(', ', array_unique($translations,SORT_REGULAR));
    }

}
