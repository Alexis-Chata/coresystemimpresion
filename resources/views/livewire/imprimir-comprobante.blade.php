<div>
    <br>
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
            <tr>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Empresa</th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Tipo Documento</th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Serie</th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Correlativo Desde</th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Correlativo Hasta</th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Impresora</th>
                <th scope="col" class="px-4 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Acción</th>
            </tr>
        </thead>
        <tbody class="bg-gray-400 divide-y divide-gray-200">
            @foreach ($series as $id => $serie)
            <form wire:submit.prevent="imprimir({{ $id }})">
                <tr>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $serie['f_sede']['name'] ?? '' }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $serie['f_tipo_comprobante']['name'] ?? '' }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">{{ $serie['serie'] }}</td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <input type="number" wire:model.defer="series.{{ $id }}.correlativo_desde" class="border border-gray-500 bg-gray-50 text-gray-700 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500" placeholder="-----" required>
                        <div>
                            @error("series.$id.correlativo_desde")
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <input type="number" wire:model.defer="series.{{ $id }}.correlativo_hasta" class="border border-gray-500 bg-gray-50 text-gray-700 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-gray-500" placeholder="-----" required>
                        <div>
                            @error("series.$id.correlativo_hasta")
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <select wire:model.defer="series.{{ $id }}.impresora" class="text-gray-500 border-gray-300 rounded-md shadow-sm focus:ring-gray-500 focus:border-gray-500 border p-2 bg-white focus:outline-none focus:ring-2" required>
                            <option value="">Seleccione una impresora</option>
                            @foreach ($impresoras as $impresora)
                            <option value="{{ $impresora }}">{{ $impresora }}</option>
                            @endforeach
                        </select>
                        <div>
                            @error("series.$id.impresora")
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap">
                        <button type="submit" wire:loading.attr="disabled" class="px-4 py-1 text-white bg-blue-500 rounded hover:bg-blue-600">
                            <span wire:loading.remove wire:target="imprimir">Imprimir</span>
                            <span wire:loading wire:target="imprimir">Procesando...</span>
                        </button>
                        <div>
                            @error("series.$id")
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        @if (session('error'))
                        <span class="alert alert-danger">
                            {{ session('error') }}
                        </span>
                        @endif
                    </td>
                </tr>
            </form>
            @endforeach
        </tbody>

    </table>

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
