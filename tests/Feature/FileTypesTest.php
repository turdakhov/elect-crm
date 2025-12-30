<?php

use App\Models\FileType;
use App\Models\User;
use Livewire\Volt\Volt;

it('renders file types index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('file-types.index'))->assertOk();
});

it('creates file type with unique name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('file-types-create')
        ->set('name', 'pdf')
        ->set('description', 'Документ PDF')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FileType::where('name', 'pdf')->exists())->toBeTrue();
});

it('validates unique name on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    FileType::create(['name' => 'xlsx', 'description' => 'Excel']);

    Volt::test('file-types-create')
        ->set('name', 'xlsx')
        ->call('submit')
        ->assertHasErrors(['name']);
});

it('updates file type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $type = FileType::create(['name' => 'image', 'description' => 'Img']);

    Volt::test('file-types-edit', ['file_type' => $type])
        ->set('name', 'picture')
        ->set('description', 'Picture type')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FileType::find($type->id)->name)->toEqual('picture');
});

it('deletes file type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $type = FileType::create(['name' => 'tmp', 'description' => null]);

    Volt::test('file-types-index')
        ->call('delete', $type->id);

    expect(FileType::find($type->id))->toBeNull();
});
