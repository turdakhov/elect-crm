@props(['collection', 'model' => null, 'name', 'label', 'disabled' => 0, 'multiple' => 0, 'with_empty' => 0, 'role'])
<div>
    <div class="xl:flex">
        <div class="basis-2/3">
            <x-forms.select name="{{ $name }}" cname="name" label="{{ $label }}" :collection="$collection" with_empty />
        </div>
        <div class="basis-1/3 flex items-end xl:mt-0 mt-4">
            <button wire:click="show('{{ $role }}')" type="button" class="rounded-md bg-gray-950/5 px-2.5 py-2.5 ml-2 text-sm font-semibold text-gray-900 hover:bg-gray-950/10 w-full">+ {{ $label }}</button>
        </div>
    </div>
</div>