<?php


namespace App\Services;


class TranslationService
{
    public function getTranslation() {
        return config('translation');
    }
}
