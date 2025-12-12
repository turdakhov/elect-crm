<?php

use App\Models\Product;
use App\Models\Project;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('authenticated user can view purchase items index', function () {
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->for($project)->create();
    $product = Product::factory()->create();
    $purchaseItem = PurchaseItem::factory()->for($purchase)->for($product)->create();

    actingAs($this->user)
        ->get(route('purchases.items.index', $purchase))
        ->assertOk()
        ->assertSee($product->name);
});

test('authenticated user can create purchase item', function () {
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->for($project)->create();
    $product = Product::factory()->create();

    actingAs($this->user);

    Volt::test('purchase-items-create', ['purchase' => $purchase])
        ->set('product_id', $product->id)
        ->set('quantity', 10)
        ->set('unit_price', 50000)
        ->call('submit')
        ->assertHasNoErrors();

    assertDatabaseHas('purchase_items', [
        'purchase_id' => $purchase->id,
        'product_id' => $product->id,
        'quantity' => 10,
        'unit_price' => 50000,
    ]);
});

test('authenticated user can update purchase item', function () {
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->for($project)->create();
    $product = Product::factory()->create();
    $purchaseItem = PurchaseItem::factory()->for($purchase)->for($product)->create();

    actingAs($this->user);

    Volt::test('purchase-items-edit', ['purchaseItem' => $purchaseItem])
        ->set('product_id', $product->id)
        ->set('quantity', 20)
        ->set('unit_price', 75000)
        ->call('submit')
        ->assertHasNoErrors();

    assertDatabaseHas('purchase_items', [
        'id' => $purchaseItem->id,
        'quantity' => 20,
        'unit_price' => 75000,
    ]);
});

test('authenticated user can delete purchase item', function () {
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->for($project)->create();
    $product = Product::factory()->create();
    $purchaseItem = PurchaseItem::factory()->for($purchase)->for($product)->create();

    actingAs($this->user);

    expect(PurchaseItem::count())->toBe(1);

    Volt::test('purchase-items-index', ['purchase' => $purchase])
        ->call('delete', $purchaseItem->id)
        ->assertHasNoErrors();

    expect(PurchaseItem::count())->toBe(0);
});

test('guest cannot access purchase items pages', function () {
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->for($project)->create();

    $this->get(route('purchases.items.index', $purchase))
        ->assertRedirect(route('login'));

    $this->get(route('purchases.items.create', $purchase))
        ->assertRedirect(route('login'));
});

test('purchase item validation requires all fields', function () {
    $project = Project::factory()->create();
    $purchase = Purchase::factory()->for($project)->create();

    actingAs($this->user);

    Volt::test('purchase-items-create', ['purchase' => $purchase])
        ->set('product_id', '')
        ->set('quantity', '')
        ->set('unit_price', '')
        ->call('submit')
        ->assertHasErrors(['product_id', 'quantity', 'unit_price']);
});

test('purchase item total price accessor calculates correctly', function () {
    $purchaseItem = new PurchaseItem([
        'quantity' => 5,
        'unit_price' => 10000,
    ]);

    expect($purchaseItem->total_price)->toBe(50000);
});
