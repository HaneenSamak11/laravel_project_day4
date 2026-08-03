<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($this->isMethod('post')) {
            return $user->can('create', Product::class);
        }

        $product = $this->route('product');

        return $product instanceof Product && $user->can('update', $product);
    }

    public function rules(): array
    {
        $presence = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'name' => [$presence, 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'price' => [$presence, 'numeric', 'min:0', 'max:9999999999.99'],
            'quantity' => [$presence, 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'price.required' => 'Product price is required.',
            'price.numeric' => 'Product price must be a number.',
            'quantity.required' => 'Product quantity is required.',
            'quantity.integer' => 'Product quantity must be an integer.',
        ];
    }
}
