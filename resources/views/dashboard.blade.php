<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Importação de planilha') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <!-- Área de Alertas/Erros -->
            <div id="error-area" class="mb-4 hidden text-red-600">
                <div class="rounded-md bg-red-50 dark:bg-red-900/50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                      d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                      clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800 dark:text-red-200" id="error-title"></h3>
                            <div class="mt-2 text-sm text-red-700 dark:text-red-200" id="error-message"></div>
                            <div class="mt-2 text-sm text-red-700 dark:text-red-200" id="missing-columns"></div>
                        </div>
                    </div>
                </div>
            </div>
{{--            //--}}
            {{--            <form action="{{ route('diagnose.excel') }}" method="POST" enctype="multipart/form-data" class="space-y-4">--}}
            {{--                @csrf--}}
            {{--                <div class="flex flex-col space-y-2">--}}
            {{--                    <label for="excel_file" class="font-medium text-gray-700">Selecione o arquivo Excel</label>--}}
            {{--                    <input--}}
            {{--                        type="file"--}}
            {{--                        name="excel_file"--}}
            {{--                        id="excel_file"--}}
            {{--                        accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"--}}
            {{--                        class="px-4 py-2 border rounded-md"--}}
            {{--                        required--}}
            {{--                    >--}}
            {{--                </div>--}}

            {{--                @error('excel_file')--}}
            {{--                <div class="text-red-500 text-sm mt-1">{{ $message }}</div>--}}
            {{--                @enderror--}}

            {{--                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">--}}
            {{--                    Diagnosticar Planilha--}}
            {{--                </button>--}}
            {{--            </form>--}}

            {{--            @if(session('error'))--}}
            {{--                <div class="mt-4 p-4 bg-red-100 text-red-700 rounded-md">--}}
            {{--                    {{ session('error') }}--}}
            {{--                </div>--}}
            {{--            @endif--}}
            {{--            //--}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form id="excel-upload-form"
                          action="{{ route('import.excel') }}"
                          method="POST"
                          enctype="multipart/form-data">
                        @csrf

                        <div class="mb-6">
                            <label for="excel_file"
                                   class="text-center block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
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

                                    <div class="flex flex-col sm:flex-row items-center justify-center text-sm text-gray-600 dark:text-gray-400">
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
                </div>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('excel_file');
        const fileSelected = document.getElementById('file-selected');
        const fileNameSpan = fileSelected.querySelector('span');
        const errorArea = document.getElementById('error-area');
        const errorTitle = document.getElementById('error-title');
        const errorMessage = document.getElementById('error-message');
        const missingColumns = document.getElementById('missing-columns');

        // Função para mostrar erro
        function showError(title, message, columns = null) {
            errorTitle.textContent = title;
            errorMessage.textContent = message;

            if (columns && columns.length > 0) {
                missingColumns.textContent = 'Colunas faltantes: ' + columns.join(', ');
                missingColumns.classList.remove('hidden');
            } else {
                missingColumns.classList.add('hidden');
            }

            errorArea.classList.remove('hidden');
        }

        // Função para esconder erro
        function hideError() {
            errorArea.classList.add('hidden');
        }

        // Configuração do input de arquivo
        fileInput.addEventListener('change', function (e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                fileNameSpan.textContent = fileName;
                fileSelected.classList.remove('hidden');
                hideError(); // Esconde erros anteriores
            } else {
                fileSelected.classList.add('hidden');
            }
        });

        // Configuração do drag and drop
        const dropZone = document.querySelector('.border-dashed');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-indigo-500', 'dark:border-indigo-400');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-indigo-500', 'dark:border-indigo-400');
            });
        });

        dropZone.addEventListener('drop', function (e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];

            if (file) {
                fileInput.files = dt.files;
                fileNameSpan.textContent = file.name;
                fileSelected.classList.remove('hidden');
                hideError(); // Esconde erros anteriores
            }
        });

        // Configuração do envio do formulário
        document.addEventListener("DOMContentLoaded", function () {
            const form = document.getElementById('excel-upload-form');

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                hideError(); // Limpa erros anteriores

                const formData = new FormData(form);
                const submitButton = form.querySelector('button[type="submit"]');

                // Desabilitar botão e mostrar loading
                submitButton.disabled = true;
                submitButton.innerHTML = `
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processando...
                `;

                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        // Reabilitar botão
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Gerar Documentos';

                        if (data.success) {
                            if (data.files && data.files.length > 0) {
                                data.files.forEach(fileUrl => {
                                    const link = document.createElement('a');
                                    link.href = fileUrl;
                                    link.download = fileUrl.split('/').pop();
                                    document.body.appendChild(link);
                                    link.click();
                                    document.body.removeChild(link);
                                });
                            }
                        } else {
                            showError('Erro!', data.message, data.missing_columns);
                        }
                    })
                    .catch(error => {
                        // Reabilitar botão
                        submitButton.disabled = false;
                        submitButton.innerHTML = 'Gerar Documentos';

                        showError('Erro!', 'Ocorreu um erro ao processar o arquivo. Por favor, tente novamente.');
                    });
            });
        });
    </script>
</x-app-layout>
