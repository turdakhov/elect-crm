<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;

/**
 * @property User $user
 * @property User $client
 * @property Project $project
 */
/** @noinspection PhpUndefinedFieldInspection */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = User::factory()->create();
    $this->project = Project::factory()->create(['client_id' => $this->client->id]);
});

test('пользователь может просматривать страницу сводки товаров', function () {
    $this->actingAs($this->user)
        ->get(route('projects.products-summary', $this->project))
        ->assertSuccessful();
});

test('сводка показывает товары из смет и закупов', function () {
    $product1 = Product::factory()->create(['name' => 'Товар 1']);
    $product2 = Product::factory()->create(['name' => 'Товар 2']);

    // Создаём смету с товарами
    $productSet = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);
    ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product1->id,
        'quantity' => 10,
    ]);
    ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product2->id,
        'quantity' => 5,
    ]);

    // Создаём закуп с товарами
    $purchase = Purchase::factory()->create(['project_id' => $this->project->id]);
    PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product1->id,
        'quantity' => 8,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('projects.products-summary', $this->project));

    $response->assertSee('Товар 1');
    $response->assertSee('Товар 2');
});

test('сводка правильно суммирует количество из нескольких смет', function () {
    $product = Product::factory()->create();

    $set1 = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);
    ProjectProductSetItem::create([
        'project_product_set_id' => $set1->id,
        'product_id' => $product->id,
        'quantity' => 5,
    ]);

    $set2 = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);
    ProjectProductSetItem::create([
        'project_product_set_id' => $set2->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('projects.products-summary', $this->project));

    // Должно быть 8 в сметах (5+3)
    $response->assertSuccessful();
});

test('сводка правильно суммирует количество из нескольких закупов', function () {
    $product = Product::factory()->create();

    $purchase1 = Purchase::factory()->create(['project_id' => $this->project->id]);
    PurchaseItem::create([
        'purchase_id' => $purchase1->id,
        'product_id' => $product->id,
        'quantity' => 4,
        'unit_price' => 100,
    ]);

    $purchase2 = Purchase::factory()->create(['project_id' => $this->project->id]);
    PurchaseItem::create([
        'purchase_id' => $purchase2->id,
        'product_id' => $product->id,
        'quantity' => 6,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('projects.products-summary', $this->project));

    // Должно быть 10 в закупах (4+6)
    $response->assertSuccessful();
});
