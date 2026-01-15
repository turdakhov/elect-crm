<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['collection', 'cname', 'model' => null, 'name', 'label', 'disabled' => 0, 'multiple' => 0, 'with_empty' => 0]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['collection', 'cname', 'model' => null, 'name', 'label', 'disabled' => 0, 'multiple' => 0, 'with_empty' => 0]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div>
    <label for="<?php echo e($name); ?>" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"><?php echo e($label); ?></label>
    <select
        class="bg-gray-50 border <?php echo e($errors->has($name) ? ' border-red-500' : 'border-gray-300'); ?> text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
        <?php if($multiple): ?> name="<?php echo e($name); ?>[]" <?php else: ?> name="<?php echo e($name); ?>" <?php endif; ?>
        wire:model.live='<?php echo e($name); ?>'
        wire:key='<?php echo e($name); ?>'
        id="<?php echo e($name); ?>"
        <?php if($disabled): ?> disabled <?php endif; ?>
        <?php if($multiple): ?> multiple="multiple" <?php endif; ?>>
        <!--[if BLOCK]><![endif]--><?php if($with_empty): ?>
        <option value="">Не выбрано</option>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $collection; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <!--[if BLOCK]><![endif]--><?php if($multiple): ?>
        <option value="<?php echo e($item->id); ?>" <?php if(isset($model->{$name}) && $model->{$name}->contains($item->id) || (!$model && request()->{$name} == $item->id)): ?> selected <?php endif; ?>>
            <?php else: ?>
        <option value="<?php echo e($item->id); ?>">
            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            <b><?php echo e($item->{$cname}); ?></b>
        </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <option disabled>Нет опций</option>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </select>
    <!--[if BLOCK]><![endif]--><?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
    <div role="alert" aria-live="polite" aria-atomic="true" class="mt-3 text-sm font-medium text-red-500 dark:text-red-400">
        <svg class="shrink-0 [:where(&amp;)]:size-5 inline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"></path>
        </svg>
        <?php echo e($message); ?>

    </div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><!--[if ENDBLOCK]><![endif]-->
</div><?php /**PATH /var/www/html/resources/views/components/forms/select.blade.php ENDPATH**/ ?>