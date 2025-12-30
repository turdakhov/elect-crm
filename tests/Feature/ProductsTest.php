<?php

use App\Models\FileType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

it('renders products index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('products.index'))->assertOk();
});

it('creates product with optional file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    FileType::firstOrCreate(['name' => 'картинка'], ['description' => 'Изображение']);

    $file = UploadedFile::fake()->image('photo.jpg');

    Volt::test('products-create')
        ->set('name', 'Test Product')
        ->set('description', 'Desc')
        ->set('approximate_price', 12.34)
        ->set('unit', 'шт')
        ->set('url', 'https://example.com')
        ->set('comments', 'Note')
        ->set('uploaded_file', $file)
        ->call('submit')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Test Product')->first();
    expect($product)->not()->toBeNull();
    expect($product->file_id)->not()->toBeNull();

    Storage::disk('public')->assertExists($product->file->path);
});

it('updates product and replaces file', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    FileType::firstOrCreate(['name' => 'картинка'], ['description' => 'Изображение']);

    $product = Product::factory()->create([
        'name' => 'Initial',
        'approximate_price' => 1.0,
        'unit' => 'шт',
    ]);

    $newFile = UploadedFile::fake()->image('new.jpg');

    Volt::test('products-edit', ['product' => $product])
        ->set('name', 'Updated')
        ->set('approximate_price', 22.0)
        ->set('unit', 'кг')
        ->set('uploaded_file', $newFile)
        ->call('submit')
        ->assertHasNoErrors();

    $product->refresh();
    expect($product->name)->toEqual('Updated');
    Storage::disk('public')->assertExists($product->file->path);
});
