<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;

test('единица измерения отображается в списке товаров сметы', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $productSet = ProjectProductSet::factory()->create(['project_id' => $project->id]);
    $product = Product::factory()->create(['name' => 'Кирпич', 'unit' => 'шт']);

    $item = ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product->id,
        'quantity' => 100,
        'comment' => 'Тестовый комментарий',
    ]);

    $response = $this->actingAs($user)
        ->get(route('project-product-set.items.index', $productSet));

    $response->assertSee('100 шт');
});

test('единица измерения отображается в списке позиций закупа', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->create(['project_id' => $project->id]);
    $product = Product::factory()->create(['name' => 'Цемент', 'unit' => 'кг']);

    $purchaseItem = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 50,
        'unit_price' => 100,
    ]);

    $response = $this->actingAs($user)
        ->get(route('purchases.items.index', $purchase));

    $response->assertSee('50 кг');
});

test('единица измерения отображается в сумме закупа', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->create(['project_id' => $project->id]);
    $product = Product::factory()->create(['name' => 'Песок', 'unit' => 'т']);

    $purchaseItem = PurchaseItem::create([
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 5,
        'unit_price' => 10000,
    ]);

    $response = $this->actingAs($user)
        ->get(route('purchases.items.index', $purchase));

    $response->assertSee('(5 т)');
});

test('смета отображается корректно если единица измерения не указана', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $productSet = ProjectProductSet::factory()->create(['project_id' => $project->id]);
    $product = Product::factory()->create(['name' => 'Товар без единицы', 'unit' => '']);

    $item = ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'comment' => null,
    ]);

    $response = $this->actingAs($user)
        ->get(route('project-product-set.items.index', $productSet));

    $response->assertSuccessful();
});

test('позиции в смете отсортированы по убыванию даты добавления', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $productSet = ProjectProductSet::factory()->create(['project_id' => $project->id]);
    $product1 = Product::factory()->create(['name' => 'Первый товар']);
    $product2 = Product::factory()->create(['name' => 'Второй товар']);
    $product3 = Product::factory()->create(['name' => 'Третий товар']);

    // Создаем товары с задержкой, чтобы гарантировать разное время создания
    $item1 = ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product1->id,
        'quantity' => 1,
    ]);
    sleep(1);

    $item2 = ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product2->id,
        'quantity' => 2,
    ]);
    sleep(1);

    $item3 = ProjectProductSetItem::create([
        'project_product_set_id' => $productSet->id,
        'product_id' => $product3->id,
        'quantity' => 3,
    ]);

    $response = $this->actingAs($user)
        ->get(route('project-product-set.items.index', $productSet));

    $response->assertSuccessful();

    // Проверяем, что последний добавленный товар (item3) отображается первым
    $content = $response->getContent();
    $posItem3 = strpos($content, 'Третий товар');
    $posItem2 = strpos($content, 'Второй товар');
    $posItem1 = strpos($content, 'Первый товар');

    expect($posItem3)->toBeLessThan($posItem2);
    expect($posItem2)->toBeLessThan($posItem1);
});
