<?php

namespace App\Livewire;

use App\Models\Complex;
use Livewire\Component;

class ComplexesCreate extends Component
{
    public $name;

    public $address;

    public $description;

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:complexes,name',
            'address' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Complex::create([
            'name' => $this->name,
            'address' => $this->address,
            'description' => $this->description,
        ]);

        session()->flash('message', 'ЖК успешно создан!');

        $this->redirect(route('complexes.index'));
    }

    public function render()
    {
        return view('complexes.create');
    }
}
