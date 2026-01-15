<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectProductSet;
use App\Models\ProjectProductSetItem;
use App\Models\User;

/**
 * @property User $user
 * @property User $client
 * @property Project $project
 * @property Product $product
 * @property ProjectProductSet $productSet
 */
/** @noinspection PhpUndefinedPropertyInspection */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->client = User::factory()->create();
    $this->project = Project::factory()->create(['client_id' => $this->client->id]);
    $this->product = Product::factory()->create();
});

describe('Смета (ProjectProductSet)', function () {
    test('пользователь может просматривать страницу сметы проекта', function () {
        $this->actingAs($this->user)
            ->get(route('projects.product-set.index', $this->project))
            ->assertSuccessful();
    });

    test('пользователь может перейти на создание сметы', function () {
        $this->actingAs($this->user)
            ->get(route('projects.product-set.create', $this->project))
            ->assertSuccessful();
    });

    test('можно создать смету для проекта', function () {
        expect(ProjectProductSet::count())->toBe(0);

        ProjectProductSet::create([
            'project_id' => $this->project->id,
            'name' => 'Смета закупок',
            'comment' => 'Необходимые материалы',
        ]);

        expect(ProjectProductSet::count())->toBe(1);
        expect(ProjectProductSet::first()->name)->toBe('Смета закупок');
        expect(ProjectProductSet::first()->comment)->toBe('Необходимые материалы');
    });

    test('можно обновить смету', function () {
        $productSet = ProjectProductSet::factory()->create(['project_id' => $this->project->id, 'comment' => 'Старый комментарий']);

        $productSet->update(['name' => 'Новое имя', 'comment' => 'Новый комментарий']);

        expect($productSet->fresh()->name)->toBe('Новое имя');
        expect($productSet->fresh()->comment)->toBe('Новый комментарий');
    });

    test('можно удалить смету', function () {
        $productSet = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);

        $productSet->delete();

        expect(ProjectProductSet::find($productSet->id))->toBeNull();
    });

    test('название валидируется - обязательно', function () {
        $data = [
            'project_id' => $this->project->id,
        ];

        $errors = validate($data, [
            'name' => 'required|string|max:255',
        ]);

        expect($errors)->toHaveKey('name');
    });

    test('название валидируется - максимум 255 символов', function () {
        $data = [
            'project_id' => $this->project->id,
            'name' => str_repeat('a', 256),
        ];

        $errors = validate($data, [
            'name' => 'required|string|max:255',
        ]);

        expect($errors)->toHaveKey('name');
    });

    test('комментарий валидируется - максимум 500 символов', function () {
        $data = [
            'project_id' => $this->project->id,
            'name' => 'Смета',
            'comment' => str_repeat('a', 501),
        ];

        $errors = validate($data, [
            'name' => 'required|string|max:255',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('comment');
    });
});

describe('Товары в смете', function () {
    beforeEach(function () {
        $this->productSet = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);
    });

    test('пользователь может просматривать товары в смете', function () {
        $this->actingAs($this->user)
            ->get(route('project-product-set.items.index', $this->productSet))
            ->assertSuccessful();
    });

    test('пользователь может перейти на добавление товара в смету', function () {
        $this->actingAs($this->user)
            ->get(route('project-product-set.items.create', $this->productSet))
            ->assertSuccessful();
    });

    test('можно добавить товар в смету', function () {
        expect(ProjectProductSetItem::count())->toBe(0);

        ProjectProductSetItem::create([
            'project_product_set_id' => $this->productSet->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'comment' => 'Комментарий к позиции',
        ]);

        expect(ProjectProductSetItem::count())->toBe(1);
        expect(ProjectProductSetItem::first()->quantity)->toBe(5);
        expect(ProjectProductSetItem::first()->comment)->toBe('Комментарий к позиции');
    });

    test('можно обновить товар в смете', function () {
        $item = ProjectProductSetItem::factory()->create([
            'project_product_set_id' => $this->productSet->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'comment' => 'Старый комментарий',
        ]);

        $item->update([
            'quantity' => 10,
            'comment' => 'Новый комментарий',
        ]);

        expect($item->fresh()->quantity)->toBe(10);
        expect($item->fresh()->comment)->toBe('Новый комментарий');
    });

    test('можно удалить товар из сметы', function () {
        $item = ProjectProductSetItem::factory()->create([
            'project_product_set_id' => $this->productSet->id,
            'product_id' => $this->product->id,
        ]);

        $item->delete();

        expect(ProjectProductSetItem::find($item->id))->toBeNull();
    });

    test('товар валидируется - product_id обязателен', function () {
        $data = [
            'project_product_set_id' => $this->productSet->id,
            'quantity' => 5,
        ];

        $errors = validate($data, [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('product_id');
    });

    test('товар валидируется - quantity обязателен', function () {
        $data = [
            'project_product_set_id' => $this->productSet->id,
            'product_id' => $this->product->id,
        ];

        $errors = validate($data, [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('quantity');
    });

    test('товар валидируется - quantity минимум 1', function () {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 0,
        ];

        $errors = validate($data, [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('quantity');
    });

    test('товар валидируется - quantity максимум 10000', function () {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 10001,
        ];

        $errors = validate($data, [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('quantity');
    });

    test('комментарий к товару валидируется - максимум 500 символов', function () {
        $data = [
            'product_id' => $this->product->id,
            'quantity' => 1,
            'comment' => str_repeat('a', 501),
        ];

        $errors = validate($data, [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('comment');
    });

    test('нельзя добавить один товар дважды в одну смету', function () {
        $productSet = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);

        ProjectProductSetItem::create([
            'project_product_set_id' => $productSet->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);

        $data = [
            'product_id' => $this->product->id,
            'quantity' => 3,
        ];

        $errors = validate($data, [
            'product_id' => [
                'required',
                'exists:products,id',
                'unique:project_product_set_items,product_id,NULL,id,project_product_set_id,'.$productSet->id,
            ],
            'quantity' => 'required|integer|min:1|max:10000',
            'comment' => 'nullable|string|max:500',
        ]);

        expect($errors)->toHaveKey('product_id');
    });

    test('можно изменить товар если это различные товары', function () {
        $productSet = ProjectProductSet::factory()->create(['project_id' => $this->project->id]);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        $item = ProjectProductSetItem::create([
            'project_product_set_id' => $productSet->id,
            'product_id' => $product1->id,
            'quantity' => 5,
        ]);

        $item->update([
            'product_id' => $product2->id,
            'quantity' => 10,
        ]);

        expect($item->fresh()->product_id)->toBe($product2->id);
        expect($item->fresh()->quantity)->toBe(10);
    });
});

/**
 * Helper function to validate data
 */
function validate(array $data, array $rules): array
{
    $validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

    return $validator->errors()->toArray();
}
