<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Importação de planilha:') }}
        </h2>
    </x-slot>


    <!-- Formulário -->
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 flex justify-center">
                    <form action="{{ route('import.excel') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file"
                               class="mx-auto block px-4 py-2 mb-4"
                               name="excel_file"
                               id="excel_file"
                               required>

                        <div class="flex flex-col items-center gap-4">
                            <x-primary-button type="submit">
                                Importar Planilha
                            </x-primary-button>

                            <!-- Mensagens de erro -->
                            @error('excel_file')
                            <div class="text-red-600 font-medium">
                                {{ $message }}
                            </div>
                            @enderror

                            @error('template')
                            <div class="text-red-600 font-medium">
                                {{ $message }}
                            </div>
                            @enderror

                            @error('error')
                            <div class="text-red-600 font-medium">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta com estilização aprimorada -->
    <div class="bg-blue-100  border-blue-500 text-green-700 p-4 rounded-md my-4 mx-6">
        <div class="flex justify-center">
            <svg class="w-6 h-6 mr-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9 2a7 7 0 100 14 7 7 0 000-14zM7.707 9.707a1 1 0 01-1.414 0L5.293 8.707a1 1 0 011.414-1.414L7 8.586l3.293-3.293a1 1 0 111.414 1.414L7.707 9.707z"/>
            </svg>
            <div>
                <p class="font-semibold text-red-600">Importante:</p>
                <p class="text-red-600">Se seu arquivo está no formato .xlsb, por favor:</p>
                <ul class=" text-green-600 list-decimal list-inside ml-4 mt-1">
                    <li>Abra-o no Excel</li>
                    <li>Clique em "Salvar como"</li>
                    <li>Selecione "Pasta de Trabalho do Excel (.xlsx) ou Excel 97-2003"</li>
                    <li>Salve e envie o novo arquivo</li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
