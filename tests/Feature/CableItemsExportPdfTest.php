<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;

/**
 * @property User $user
 * @property Project $project
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
});

test('пользователь может скачать pdf с кабелями проекта', function () {
    $response = $this->actingAs($this->user)
        ->get(route('projects.cable-items.export-pdf', $this->project));

    $response->assertSuccessful();
    $response->assertHeader('content-type', 'application/pdf');
});
