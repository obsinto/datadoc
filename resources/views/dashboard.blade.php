<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Importação de planilha:') }}
        </h2>
    </x-slot>

    <div class="alert alert-info">
        Importante: Se seu arquivo está no formato .xlsb, por favor:
        1. Abra-o no Excel
        2. Clique em "Salvar como"
        3. Selecione "Pasta de Trabalho do Excel (.xlsx)"
        4. Salve e envie o novo arquivo
    </div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 flex justify-center">
                    <form action="{{route(('import.excel'))}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="file" class="mx-auto block px-4 py-2" name="excel_file" id="excel_file" required>
                        <br>
                        <div class="flex flex-col items-center gap-4">
                            <x-primary-button type="submit">
                                Importar Planilha
                            </x-primary-button>

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

</x-app-layout>
