<?php

use App\Models\PartCategory;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public bool $isEditing = false;
    public ?PartCategory $selectedCategory = null;

    // Form fields
    public string $name = '';
    public string $description = '';
    public $sort_order = 0;
    public bool $is_active = true;

    public bool $showDeleteModal = false;
    public ?int $categoryToDelete = null;

    protected $queryString = ['search'];

    public function layout()
    {
        return 'components.layouts.app';
    }

    public function title()
    {
        return __('Part Categories');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->selectedCategory = PartCategory::findOrFail($id);
        $this->name = $this->selectedCategory->name;
        $this->description = $this->selectedCategory->description ?? '';
        $this->sort_order = $this->selectedCategory->sort_order;
        $this->is_active = $this->selectedCategory->is_active;
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('part_categories', 'name')->ignore($this->selectedCategory?->id),
            ],
            'description' => 'nullable|string',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($this->isEditing && $this->selectedCategory) {
            $this->selectedCategory->update($validated);
            $message = 'Category updated.';
        } else {
            PartCategory::create($validated);
            $message = 'Category created.';
        }

        $this->closeModal();
        $this->dispatch('success', message: $message);
    }

    public function confirmDelete(int $id): void
    {
        $this->categoryToDelete = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $category = PartCategory::withCount('parts')->findOrFail($this->categoryToDelete);

        // Deleting would null the FK on every part in it, quietly
        // uncategorising stock the shop still holds. Deactivating hides it
        // from the pickers without losing what is already filed under it.
        if ($category->parts_count > 0) {
            $this->showDeleteModal = false;
            $this->dispatch('error', message: "{$category->name} still has {$category->parts_count} part(s). Move them first, or deactivate the category instead.");

            return;
        }

        $category->delete();

        $this->showDeleteModal = false;
        $this->categoryToDelete = null;
        $this->dispatch('success', message: 'Category deleted.');
    }

    private function resetForm(): void
    {
        $this->selectedCategory = null;
        $this->name = '';
        $this->description = '';
        $this->sort_order = 0;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function with(): array
    {
        $query = PartCategory::withCount('parts');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return [
            'categories' => $query->ordered()->paginate(15),
        ];
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">Part Categories</h1>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">How parts are grouped in the inventory and on job orders</p>
            </div>
            <button type="button" wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white text-sm font-semibold rounded-xl shadow-lg transition-all cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                New Category
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <label for="search" class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Search</label>
        <input type="text" id="search" wire:model.live.debounce.300ms="search"
            placeholder="Search categories..."
            class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-700 rounded-xl bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all" />
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        @if($categories->isEmpty())
            <div class="p-12 text-center">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">No Categories Found</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $search ? 'Try a different search.' : 'Create your first category to start grouping parts.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                            <th class="px-6 py-4 text-left text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Parts</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Order</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($categories as $category)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $category->name }}</div>
                                    @if($category->description)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $category->description }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-700 dark:text-zinc-300">{{ $category->parts_count }}</td>
                                <td class="px-6 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ $category->sort_order }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($category->is_active)
                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full text-green-700 bg-green-100 dark:text-green-300 dark:bg-green-900/30">Active</span>
                                    @else
                                        <span class="inline-flex px-2.5 py-1 text-xs font-semibold rounded-full text-zinc-600 bg-zinc-100 dark:text-zinc-300 dark:bg-zinc-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button type="button" wire:click="openEditModal({{ $category->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button" wire:click="confirmDelete({{ $category->id }})"
                                        class="px-3 py-1.5 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors cursor-pointer">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- Create / Edit modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="closeModal"></div>

                <div class="relative w-full max-w-lg bg-white dark:bg-zinc-800 rounded-2xl shadow-xl">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $isEditing ? 'Edit Category' : 'New Category' }}
                        </h3>
                    </div>

                    <form wire:submit="save" class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model="name"
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Description</label>
                            <textarea wire:model="description" rows="2"
                                class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"></textarea>
                            @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Sort Order
                                </label>
                                <input type="number" wire:model="sort_order" min="0"
                                    class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                                @error('sort_order') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-end pb-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="is_active"
                                        class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500" />
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Active</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors cursor-pointer">
                                {{ $isEditing ? 'Save Changes' : 'Create Category' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete confirmation -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50" wire:click="$set('showDeleteModal', false)"></div>

                <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Delete this category?</h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                        A category still holding parts cannot be deleted &mdash; deactivate it instead.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button type="button" wire:click="$set('showDeleteModal', false)"
                            class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" wire:click="delete"
                            class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors cursor-pointer">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
