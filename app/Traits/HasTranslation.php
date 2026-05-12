<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslation
{
    /**
     * هذا الـ Accessor سيعمل بشكل ديناميكي لأي حقل نطلبه
     * مثال: عندما تنادي $model->title سيقوم هو بالبحث عن title_ar أو title_en
     */
    public function translate(string $field)
    {
        $locale = App::getLocale();
        $column = "{$field}_{$locale}";

        // جلب القيمة من عمود اللغة الحالية، وإذا كانت فارغة نجلبها من العمود العربي كاحتياط
        return $this->{$column} ?? $this->{"{$field}_ar"};
    }

    /**
     * Accessor للعنوان
     */
    public function getTitleAttribute()
    {
        return $this->translate('title');
    }

    /**
     * Accessor للوصف
     */
    public function getDescriptionAttribute()
    {
        return $this->translate('description');
    }
}