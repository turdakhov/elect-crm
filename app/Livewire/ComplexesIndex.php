<?php

namespace App\Livewire;

use App\Models\Complex;
use Livewire\Component;

class ComplexesIndex extends Component
{
    public function delete(Complex $complex)
    {
        $complex->delete();
        session()->flash('message', 'ЖК успешно удален.');
    }

    public function render()
    {
        $complexes = Complex::orderByDesc('id')->paginate(10);

        return view('complexes.index', compact('complexes'));
    }
}
