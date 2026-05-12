<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
            'pros' => ['sometimes', 'array', 'max:5'],
            'pros.*' => ['string', 'max:200'],
            'cons' => ['sometimes', 'array', 'max:5'],
            'cons.*' => ['string', 'max:200'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'التقييم مطلوب',
            'rating.min' => 'التقييم يجب أن يكون بين 1 و 5',
            'rating.max' => 'التقييم يجب أن يكون بين 1 و 5',
            'comment.required' => 'التعليق مطلوب',
            'comment.min' => 'التعليق يجب أن يكون 10 أحرف على الأقل',
            'comment.max' => 'التعليق يجب ألا يتجاوز 1000 حرف',
            'pros.max' => 'يمكنك إضافة 5 نقاط إيجابية كحد أقصى',
            'cons.max' => 'يمكنك إضافة 5 نقاط سلبية كحد أقصى',
        ];
    }
}