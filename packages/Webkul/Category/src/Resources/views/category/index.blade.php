<x-admin::layouts >
    <x-slot:title>
        @lang('admin::app.categories.index.title')
    </x-slot>

    <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
        {{-- {!! view_render_event('admin.leads.index.header.left.before') !!} --}}

        <div class="flex flex-col gap-2">
            {{-- <div class="flex cursor-pointer items-center">
                <!-- Bredcrumbs -->
                <x-admin::breadcrumbs name="category" />
            </div> --}}

            <div class="text-xl font-bold dark:text-white">
                Categories
                @lang('admin::app.categories.index.title')
            </div>
        </div>
    </div>
</x-admin::layouts>
