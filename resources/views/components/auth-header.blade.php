@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h1>
    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $description }}</p>
</div>
