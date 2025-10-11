@props(['collection', 'cname', 'model' => null, 'name', 'label', 'disabled' => 0, 'multiple' => 0, 'with_empty' => 0])

<div>
    <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">{{ $label }}</label>
    <select
        class="bg-gray-50 border {{ $errors->has($name) ? ' border-red-500' : 'border-gray-300' }} text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        @if ($multiple) name="{{$name}}[]" @else name="{{ $name }}" @endif
        wire:model.live='{{ $name }}'
        wire:key='{{ $name }}'
        id="{{ $name }}"
        @if ($disabled) disabled @endif
        @if ($multiple) multiple="multiple" @endif>
        @if ($with_empty)
        <option value="">Не выбрано</option>
        @endif
        @forelse ($collection as $item)
        @if ($multiple)
        <option value="{{ $item->id }}" @if(isset($model->{$name}) && $model->{$name}->contains($item->id) || (!$model && request()->{$name} == $item->id)) selected @endif>
            @else
        <option value="{{ $item->id }}">
            @endif
            <b>{{ $item->{$cname} }}</b>
        </option>
        @empty
        <option disabled>Нет опций</option>
        @endforelse
    </select>
    @error($name)
    <div role="alert" aria-live="polite" aria-atomic="true" class="mt-3 text-sm font-medium text-red-500 dark:text-red-400">
        <svg class="shrink-0 [:where(&amp;)]:size-5 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
        </svg>
        {{ $message }}
    </div>
    @enderror
</div>