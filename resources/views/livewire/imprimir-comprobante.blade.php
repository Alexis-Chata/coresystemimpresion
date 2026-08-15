<div wire:init="loadSeries" class="space-y-6">

    @if (session()->has('error'))
    <div class="p-4 text-sm text-danger-600 bg-danger-500/10 border border-danger-500/20 rounded-xl">
        <strong>⚠️ Error:</strong> {{ session('error') }}
    </div>
    @endif

    @if (session()->has('message'))
    <div class="p-4 text-sm text-success-600 bg-success-500/10 border border-success-500/20 rounded-xl">
        <strong>✅ Éxito:</strong> {{ session('message') }}
    </div>
    @endif

    @if(!$readyToLoad)
    <div class="flex flex-col items-center justify-center p-12 space-y-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm">
        <svg class="w-8 h-8 text-gray-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm text-gray-500 dark:text-gray-400 font-medium">Cargando series configuradas...</span>
    </div>
    @else
    <div class="fi-ta-ctn divide-y divide-gray-200 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-200 dark:divide-white/10 dark:bg-gray-900 dark:ring-white/10">
        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="px-4 py-3.5 text-start text-sm font-semibold text-gray-900 dark:text-white">Tipo Documento</th>
                        <th class="px-4 py-3.5 text-start text-sm font-semibold text-gray-900 dark:text-white">Serie</th>
                        <th class="px-4 py-3.5 text-start text-sm font-semibold text-gray-900 dark:text-white">Correlativo Desde</th>
                        <th class="px-4 py-3.5 text-start text-sm font-semibold text-gray-900 dark:text-white">Correlativo Hasta</th>
                        <th class="px-4 py-3.5 text-start text-sm font-semibold text-gray-900 dark:text-white">Impresora</th>
                        <th class="px-4 py-3.5 text-center text-sm font-semibold text-gray-900 dark:text-white">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                    @forelse ($series as $id => $serie)
                    <tr class="group transition-colors duration-75">

                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-medium bg-transparent group-hover:bg-gray-50/70 dark:group-hover:bg-white/[0.02]">
                            {{ $serie['f_tipo_comprobante']['name'] ?? 'N/A' }}
                        </td>

                        <td class="px-4 py-3 text-sm font-mono font-bold text-gray-900 dark:text-white bg-transparent group-hover:bg-gray-50/70 dark:group-hover:bg-white/[0.02]">
                            {{ $serie['serie'] }}
                        </td>

                        <td class="px-4 py-3 bg-transparent group-hover:bg-gray-50/70 dark:group-hover:bg-white/[0.02]">
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500 bg-white dark:bg-gray-800">
                                <input type="number" wire:model="series.{{ $id }}.correlativo_desde" wire:keydown.enter.prevent="imprimir({{ $id }})" class="w-full border-0 bg-transparent py-1.5 px-3 text-sm text-gray-900 dark:text-white focus:ring-0 placeholder:text-gray-400" placeholder="-----" required>
                            </div>
                            @error("series.$id.correlativo_desde")
                            <div class="text-danger-600 dark:text-danger-400 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </td>

                        <td class="px-4 py-3 bg-transparent group-hover:bg-gray-50/70 dark:group-hover:bg-white/[0.02]">
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500 bg-white dark:bg-gray-800">
                                <input type="number" wire:model="series.{{ $id }}.correlativo_hasta" wire:keydown.enter.prevent="imprimir({{ $id }})" class="w-full border-0 bg-transparent py-1.5 px-3 text-sm text-gray-900 dark:text-white focus:ring-0 placeholder:text-gray-400" placeholder="-----" required>
                            </div>
                            @error("series.$id.correlativo_hasta")
                            <div class="text-danger-600 dark:text-danger-400 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </td>

                        <td class="px-4 py-3 bg-transparent group-hover:bg-gray-50/70 dark:group-hover:bg-white/[0.02]">
                            <div class="flex rounded-lg shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus-within:ring-2 focus-within:ring-primary-600 dark:focus-within:ring-primary-500 bg-white dark:bg-gray-800">
                                <select wire:model="series.{{ $id }}.impresora" wire:keydown.enter.prevent="imprimir({{ $id }})" class="w-full border-0 bg-transparent py-1.5 ps-3 pe-8 text-sm text-gray-900 dark:text-white focus:ring-0" required>
                                    <option value="" class="dark:bg-gray-900">Seleccione impresora</option>
                                    @foreach ($impresoras as $impresora)
                                    <option value="{{ $impresora }}" class="dark:bg-gray-900">{{ $impresora }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error("series.$id.impresora")
                            <div class="text-danger-600 dark:text-danger-400 text-xs mt-1">{{ $message }}</div>
                            @enderror
                        </td>

                        <td class="px-4 py-3 text-center z-10 relative bg-transparent group-hover:bg-gray-50/70 dark:group-hover:bg-white/[0.02]">
                            <x-filament::button type="button" icon="heroicon-m-printer" color="info" size="sm" wire:click="imprimir({{ $id }})" wire:loading.attr="disabled" wire:target="imprimir({{ $id }})">

                                <span wire:loading.remove wire:target="imprimir({{ $id }})">
                                    Imprimir
                                </span>
                                <span wire:loading wire:target="imprimir({{ $id }})">
                                    Espere...
                                </span>
                            </x-filament::button>

                            @if($errors->has("series.$id"))
                            <div class="text-danger-600 dark:text-danger-400 text-xs mt-2 block font-medium">
                                {{ $errors->first("series.$id") }}
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-6 text-center text-sm text-gray-400 dark:text-gray-500">
                            No se encontraron series para esta sede.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:load', () => {
            Livewire.hook('message.sent', (message, component) => {
                const buttons = document.querySelectorAll('button[wire\:click]');
                buttons.forEach(button => button.setAttribute('disabled', true));
            });

            Livewire.hook('message.processed', (message, component) => {
                const buttons = document.querySelectorAll('button[wire\:click]');
                buttons.forEach(button => button.removeAttribute('disabled'));
            });
        });
    </script>
</div>
