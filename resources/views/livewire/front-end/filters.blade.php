<div class="shop-filters flex-shrink-0 border-end">
    <ul class="list-group pt-2 border-bottom rounded-0">
        <h6 class="my-3 mx-4 fw-semibold">Filter by Category</h6>
        <li class="list-group-item border-0 p-0 mx-4 mb-0">
            <a class="d-flex align-items-center gap-6 list-group-item-action text-dark px-3 py-1 rounded-1 {{ $selectedCategory == 'all' ? 'bg-primary text-white' : 'text-dark' }}"
                href="javascript:void(0)" wire:click="selectCategory('all')">
                - All
            </a>
        </li>
        @foreach ($categories as $category)
            <li class="list-group-item border-0 p-0 mx-4 mb-0">
                <a class="d-flex align-items-center list-group-item-action py-1 text-dark px-3 rounded-1 {{ $selectedCategory == $category->id ? 'bg-primary text-white' : 'text-dark' }}"
                    href="javascript:void(0)" wire:click="selectCategory({{ $category->id }})">
                    - {{ $category->name }}
                </a>
            </li>
        @endforeach
        <div class="p-4">
            <a href="javascript:void(0)" wire:click="selectCategory('all')" class="btn btn-secondary w-100">Reset
                Filters</a>
        </div>
</div>
