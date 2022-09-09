<?php


namespace App\Services;

class TokenResolveService
{
    protected $directusService;

    public function __construct(DirectusService $directusService){
        $this->directusService = $directusService;
    }

    // to get template which needs to be resolved
    public function getTemplateData($item,$issuer){
        $country = substr($issuer, 0,2);
        $city = substr($issuer, 2,3);
        $filters = [
            'code'=>[
                'in' => [$city,$country,'ww']
            ],
            'status' => [
                'eq' => 'published'
            ]
        ];
        $select = 'code, translation.email_title, translation.email_content, translation.invoice_content, translation.activation';
        $options['lang'] = 'en-us';
        $getTemplate = $this->directusService->getContent($item, $select, $filters, $options);
        $data = $this->resolveTemplate($getTemplate, $issuer);
        return $data;
        
    }

    // will resolved the template based on collection tokens
    public function resolveTemplate($template, $issuer){
        $data = [];
        $resolved_tokens = [];
        $final_tokens = [];
        $emailcontent = '';
        $invoicecontent = '';
        if(is_array($template) and count($template)){
            $data = $this->getEmailInvoiceContent($template,$issuer);

            if(isset($data['email_content'])){
                $emailcontent = $data['email_content'];
            }
            if(isset($data['invoice_content'])){
                $invoicecontent = $data['invoice_content'];
            }
            $pattern = "~({{\w+:\w+:\w+}}|{{\w+:\w+}})~";
            if($emailcontent != ''){
                $final_tokens = $this->pregMatchTemplate($data,$pattern);
            }
            if(count($final_tokens)){
                $resolved_tokens = $this->getResolvedTokens($final_tokens,$issuer);
                if(count($resolved_tokens)){
                    ksort($resolved_tokens);
                    foreach ($resolved_tokens as $key => $value) {
                       $data['email_content'] = str_replace($final_tokens[$key], $value, $emailcontent);
                       $data['invoice_content'] = str_replace($final_tokens[$key], $value, $invoicecontent);
                       $emailcontent = $data['email_content'];
                       $invoicecontent = $data['invoice_content'];
                    }
                }
            }

        }
        return $data;
    }

    //returns the list of tokens after preg_match with given pattern
    private function pregMatchTemplate($content, $pattern){
        preg_match_all($pattern, $content['email_content'], $email_tokens);
        preg_match_all($pattern, $content['invoice_content'], $invoice_tokens);
        if(count($email_tokens)){
            $tokens[] = array_unique($email_tokens, SORT_REGULAR)[0];
        }        
        if(count($invoice_tokens)){
            $tokens[] = array_unique($invoice_tokens, SORT_REGULAR)[0];

        }    
        // will hold all tokens from email and invoice content 
        $final_tokens  = array_unique($tokens, SORT_REGULAR)[0];
        return $final_tokens;
    }

    //returns the list of resolved tokens 
    private function getResolvedTokens($tokens,$issuer){
        $resolvedtokens = [];
        $resolved_tokens = [];
        $country = substr($issuer, 0, 2);
        $city = substr($issuer, 2, 3);
        foreach ($tokens as $tkey => $tok) {
            $val = str_replace(array('{{','}}'), '', $tok);
            $arr = explode(':', $val);

            if($arr[0] == 'c'){ // if collection token - directus
                if($arr[2] == 'address'){
                    $issuer_filter = [$issuer,'ww'];
                }
                else {
                     $issuer_filter = [$city,$country,'ww'];
                }
                $filters = [
                    'code'=>[
                        'in' =>  $issuer_filter
                    ],
                    'status' => [
                        'eq' => 'published'
                    ],
                ];
                $resolvedtokens[$tkey] = $this->getTokenTranslationFromDirectus($arr,$filters,$country,$city);
            }
        }
        if(count($resolvedtokens)){
            foreach ($resolvedtokens as $r => $res) {
                if(isset($res[0]) and $res[0] != ''){
                     $resolved_tokens[$r] = $res[0];
                } elseif(isset($res[1]) and $res[1] != ''){
                     $resolved_tokens[$r] = $res[1];
                } elseif(isset($res[2]) and $res[2] != ''){
                     $resolved_tokens[$r] = $res[2];
                } elseif(isset($res[99]) and $res[99] != ''){
                    $resolved_tokens[$r] = $res[99];
                }

            }
        }        
        return $resolved_tokens;
    }

    // get best matched collection item based on city,country & ww
    private function getEmailInvoiceContent($template,$issuer){
        $contentdata = [];
        $country = substr($issuer, 0, 2);
        $city = substr($issuer, 2, 3);
        $continue = 0;
        foreach ($template as $key => $value) {
            if(isset($value['translation']) and count($value['translation'])){
                $continue = 1;
                foreach ($value['translation'] as $k => $val) {
                    if($val['activation'] == true){
                        if($value['code'] == $city){
                            $email_title[0] = $val['email_title'];
                            $email_content[0] = $val['email_content'];
                            $invoice_content[0] = $val['invoice_content'];
                        }
                        else if($value['code'] == $country){
                            $email_title[1] = $val['email_title'];
                            $email_content[1] = $val['email_content'];
                            $invoice_content[1] = $val['invoice_content'];
                        }
                        else if($value['code'] == 'ww'){
                            $email_title[2] = $val['email_title'];
                            $email_content[2] = $val['email_content'];
                            $invoice_content[2] = $val['invoice_content'];
                        }
                    }
                    
                }
            }
        }
        if($continue == 1){
            if(isset($email_content[0]) and $email_content[0] != ''){
                $emailtitle = $email_title[0];
                $emailcontent = $email_content[0];
                $invoicecontent = $invoice_content[0];
            } elseif (isset($email_content[1]) and $email_content[1] != '') {
                $emailtitle = $email_title[1];
                $emailcontent = $email_content[1];
                $invoicecontent = $invoice_content[1];
            } elseif (isset($email_content[2]) and $email_content[2] != '') {
                $emailtitle = $email_title[2];
                $emailcontent = $email_content[2];
                $invoicecontent = $invoice_content[2];
            }
            $contentdata['email_title'] =  $emailtitle;
            $contentdata['email_content'] =  $emailcontent;
            $contentdata['invoice_content'] =  $invoicecontent;
        }
        return $contentdata;
    }

    //calls directus collection to get translation of Token
    private function getTokenTranslationFromDirectus($arr,$filters,$country,$city){
        $collection = $arr[1];
        $field = 'translation.'.$arr[2];
        $options['lang'] = 'en-us';
        $select = "code,".$field;
        $resolvedtokens = [];
        $getTokenval = $this->directusService->getContent($collection, $select, $filters, $options);
        if(count($getTokenval)){
            foreach ($getTokenval as $key => $value) {
                foreach ($value['translation'] as $k => $val) {
                    if($arr[2] == 'address'){
                        $resolvedtokens[99] = $val[$arr[2]];
                    }
                    else if($value['code'] == $city) {
                        $resolvedtokens[0] = $val[$arr[2]];
                    }
                    else if($value['code'] == $country) {
                        $resolvedtokens[1] = $val[$arr[2]];
                    }
                    else if($value['code'] == 'ww') {
                        $resolvedtokens[2] = $val[$arr[2]];
                    }
                }
            } 
        }
        return $resolvedtokens;
    }

}