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
        $options['lang'] = empty($lang) ? 'en-us' : $lang;
        $getTemplate = $this->directusService->getContent($item, $select, $filters, $options);
        $data = $this->resolveTemplate($getTemplate, $issuer);
        return $data;
        
    }

    // will resolved the template based on collection and application tokens
    public function resolveTemplate($template, $issuer){
        $data = [];
        $resolvedtokens = [];
        $resolved_tokens = [];
        $continue = 0;
        if(is_array($template) and count($template)){
            $country = substr($issuer, 0, 2);
            $city = substr($issuer, 2, 3);
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
                $data['email_title'] =  $emailtitle;
                $data['email_content'] =  $emailcontent;
                $data['invoice_content'] =  $invoicecontent;

                $pattern = "~({{\w+:\w+:\w+}}|{{\w+:\w+}})~";
                preg_match_all($pattern, $emailcontent, $email_tokens);
                preg_match_all($pattern, $invoicecontent, $invoice_tokens);
                if(count($email_tokens)){
                    $tokens[] = array_unique($email_tokens, SORT_REGULAR)[0];
                }        
                if(count($invoice_tokens)){
                    $tokens[] = array_unique($invoice_tokens, SORT_REGULAR)[0];
                }    

                // will hold all tokens from email and invoice content 
                $final_tokens  = array_unique($tokens, SORT_REGULAR)[0];
                if(count($final_tokens)){
                    foreach ($final_tokens as $t => $tok) {
                        $val = str_replace(array('{{','}}'), '', $tok);
                        $arr = explode(':', $val);

                        if($arr[0] == 'c'){ // if collection token - directus
                            $collection = $arr[1];
                            $field = 'translation.'.$arr[2];
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
                            $select = "code,".$field;
                            $options['lang'] = empty($lang) ? 'en-us' : $lang;
                            $getTokenval = $this->directusService->getContent($collection, $select, $filters, $options);
                            if(count($getTokenval)){
                                foreach ($getTokenval as $key => $value) {
                                    foreach ($value['translation'] as $k => $val) {
                                        if($arr[2] == 'address'){
                                            $resolved_tokens[$t] = strip_tags($val[$arr[2]]);
                                        }
                                        else if($value['code'] == $city) {
                                            $resolvedtokens[$t][0] = strip_tags($val[$arr[2]]);
                                        }
                                        else if($value['code'] == $country) {
                                            $resolvedtokens[$t][1] = strip_tags($val[$arr[2]]);
                                        }
                                        else if($value['code'] == 'ww') {
                                            $resolvedtokens[$t][2] = strip_tags($val[$arr[2]]);
                                        }
                                    }
                                } 
                            }
                        }
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
                        }

                    }
                }

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

}