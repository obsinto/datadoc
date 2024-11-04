<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Importação de planilha') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('import.excel') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label for="excel_file"
                                   class=" text-center block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Selecione o arquivo Excel
                            </label>

                            <div
                                    class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md hover:border-gray-400 dark:hover:border-gray-500 transition-colors duration-200">
                                <div class="space-y-1 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none"
                                         stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>

                                    <div
                                            class="flex flex-col sm:flex-row items-center justify-center text-sm text-gray-600 dark:text-gray-400">
                                        <label for="excel_file"
                                               class="relative cursor-pointer rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500 dark:focus-within:ring-offset-gray-800">
                                            <span>Selecione um arquivo</span>
                                            <input type="file"
                                                   class="sr-only"
                                                   name="excel_file"
                                                   id="excel_file"
                                                   accept=".xls,.xlsx"
                                                   required>
                                        </label>
                                        <p class="pl-1">ou arraste e solte</p>
                                    </div>
                                    <div id="file-selected" class="text-sm text-gray-600 dark:text-gray-400 hidden">
                                        Arquivo selecionado: <span class="font-medium"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        XLS, XLSX até 10MB
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-center gap-4">
                            <x-primary-button type="submit">
                                {{ __('Gerar Documentos') }}
                            </x-primary-button>

                        </div>
                    </form>
                    @if($errors->any())
                        <x-alert/>
                    @endif

                </div>
            </div>
        </div>

        <script>
            const fileInput = document.getElementById('excel_file');
            const fileSelected = document.getElementById('file-selected');
            const fileNameSpan = fileSelected.querySelector('span');

            fileInput.addEventListener('change', function (e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    fileNameSpan.textContent = fileName;
                    fileSelected.classList.remove('hidden');
                } else {
                    fileSelected.classList.add('hidden');
                }
            });

            // Adiciona suporte para drag and drop
            const dropZone = document.querySelector('.border-dashed');

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropZone.classList.add('border-indigo-500', 'dark:border-indigo-400');
            }

            function unhighlight(e) {
                dropZone.classList.remove('border-indigo-500', 'dark:border-indigo-400');
            }

            dropZone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const file = dt.files[0];

                if (file) {
                    fileInput.files = dt.files;
                    fileNameSpan.textContent = file.name;
                    fileSelected.classList.remove('hidden');
                }
            }

            document.getElementById('excel_file').addEventListener('change', function () {
                const fileName = this.files[0]?.name || 'Nenhum arquivo selecionado';
                document.getElementById('file-selected').classList.remove('hidden');
                document.getElementById('file-selected').querySelector('span').textContent = fileName;
            });
        </script>
</x-app-layout>
