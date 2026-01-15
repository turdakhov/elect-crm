<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectProductSetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $projectProductSet = $this->route('projectProductSet');

        return $this->user()->can('update', $projectProductSet);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $projectProductSet = $this->route('projectProductSet');

        return [
            'product_id' => [
                'required',
                'exists:products,id',
                'unique:project_product_set_items,product_id,NULL,id,project_product_set_id,'.$projectProductSet->id,
            ],
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Пожалуйста, выберите товар.',
            'product_id.exists' => 'Выбранный товар не существует.',
            'product_id.unique' => 'Этот товар уже добавлен в смету.',
            'quantity.required' => 'Количество обязательно.',
            'quantity.integer' => 'Количество должно быть целым числом.',
            'quantity.min' => 'Количество должно быть не менее 1.',
            'quantity.max' => 'Количество не должно превышать 10000.',
            'comment.string' => 'Комментарий должен быть строкой.',
            'comment.max' => 'Комментарий не должен превышать 500 символов.',
        ];
    }
}
