<?php

namespace App\Http\Requests;

use App\Models\ProjectProductSet;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectProductSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->getMethod() === 'POST') {
            return $this->user()->can('create', ProjectProductSet::class);
        }

        return $this->user()->can('update', $this->route('projectProductSet'));
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'comment' => 'nullable|string|max:500',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название сметы обязательно.',
            'name.max' => 'Название сметы не должно превышать 255 символов.',
            'comment.max' => 'Комментарий не должен превышать 500 символов.',
        ];
    }
}
