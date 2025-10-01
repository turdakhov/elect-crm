<?php

use Livewire\Volt\Component;

new class extends Component {
    public $projects;

    public function mount()
    {
        $this->projects = \App\Models\Project::all();
    }
}; ?>

<div>
    @foreach ($projects as $project)
        <div class="p-4 mb-4 border rounded-lg">
            <h2 class="text-xl font-bold">{{ $project->name }}</h2>
            <p class="text-gray-600">{{ $project->description }}</p>
        </div>
    @endforeach
</div>
