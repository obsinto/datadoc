<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\Response;

class ExcelDiagnosticController extends Controller
{
    protected $allowedMimes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream'
    ];

    public function diagnose(Request $request)
    {
        try {
            // 1. Verificação inicial do arquivo
            if (!$request->hasFile('excel_file')) {
                return $this->errorResponse('Nenhum arquivo foi enviado.', Response::HTTP_BAD_REQUEST);
            }

            $file = $request->file('excel_file');

            if (!$file->isValid()) {
                return $this->errorResponse('O arquivo está corrompido ou é inválido.', Response::HTTP_BAD_REQUEST);
            }

            // 2. Validação do tipo de arquivo
            if (!in_array($file->getMimeType(), $this->allowedMimes)) {
                return $this->errorResponse(
                    'Tipo de arquivo inválido. Por favor, envie um arquivo Excel (.xls ou .xlsx).',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            // 3. Informações básicas do arquivo
            $diagnostics = [
                'file_info' => [
                    'nome_original' => $file->getClientOriginalName(),
                    'tamanho' => $this->formatBytes($file->getSize()),
                    'tipo_mime' => $file->getMimeType(),
                    'extensao' => $file->getClientOriginalExtension(),
                    'caminho_temp' => $file->getRealPath(),
                ]
            ];

            // 4. Carregamento do arquivo Excel
            try {
                $reader = IOFactory::createReaderForFile($file->getRealPath());
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file->getRealPath());
                $worksheet = $spreadsheet->getActiveSheet();
            } catch (SpreadsheetException $e) {
                Log::error('Erro ao ler arquivo Excel:', [
                    'erro' => $e->getMessage(),
                    'arquivo' => $diagnostics['file_info']
                ]);
                return $this->errorResponse(
                    'Não foi possível ler o arquivo Excel. Ele pode estar corrompido ou em formato inválido.',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // 5. Análise da estrutura
            $diagnostics['estrutura'] = [
                'total_linhas' => $worksheet->getHighestRow(),
                'total_colunas' => Coordinate::columnIndexFromString($worksheet->getHighestColumn()),
                'nome_planilha' => $worksheet->getTitle(),
            ];

            // 6. Análise dos cabeçalhos
            $headers = [];
            $headerRow = $worksheet->getRowIterator()->current();
            $cellIterator = $headerRow->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                if ($cell->getValue()) {
                    $headers[] = [
                        'coluna' => $cell->getColumn(),
                        'valor' => $cell->getValue(),
                        'tipo' => $cell->getDataType(),
                    ];
                }
            }

            $diagnostics['cabecalhos'] = $headers;

            // 7. Verificação de células mescladas
            $mergedCells = $worksheet->getMergeCells();
            if (!empty($mergedCells)) {
                $diagnostics['alertas'][] = [
                    'tipo' => 'celulas_mescladas',
                    'mensagem' => 'A planilha contém células mescladas, o que pode causar problemas na importação.',
                    'localizacao' => array_values($mergedCells)
                ];
            }

            // 8. Amostra de dados
            $sampleData = [];
            $maxSampleRows = 5;
            $startRow = 2; // Começa da segunda linha (após cabeçalhos)
            $endRow = min($worksheet->getHighestRow(), $startRow + $maxSampleRows - 1);

            for ($rowIndex = $startRow; $rowIndex <= $endRow; $rowIndex++) {
                $rowData = [];
                foreach ($worksheet->getRowIterator($rowIndex, $rowIndex) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    foreach ($cellIterator as $cell) {
                        $valor = $cell->getValue();
                        if ($cell->getDataType() === 'n' && \PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                            $valor = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor)->format('d/m/Y');
                        }
                        $rowData[$cell->getColumn()] = $valor;
                    }
                }
                $sampleData[] = $rowData;
            }

            $diagnostics['amostra_dados'] = $sampleData;

            // 9. Verificação de consistência
            $columnCount = count($headers);
            $inconsistentRows = [];

            foreach ($worksheet->getRowIterator(2) as $row) {
                $currentColumnCount = 0;
                foreach ($row->getCellIterator() as $cell) {
                    if ($cell->getValue() !== null) {
                        $currentColumnCount++;
                    }
                }
                if ($currentColumnCount !== $columnCount && $currentColumnCount > 0) {
                    $inconsistentRows[] = $row->getRowIndex();
                }
            }

            if (!empty($inconsistentRows)) {
                $diagnostics['alertas'][] = [
                    'tipo' => 'inconsistencia_colunas',
                    'mensagem' => 'Algumas linhas têm um número diferente de colunas.',
                    'linhas_afetadas' => $inconsistentRows
                ];
            }

            // 10. Retorno do diagnóstico
            return response()->json([
                'success' => true,
                'diagnostics' => $diagnostics
            ]);

        } catch (\Exception $e) {
            Log::error('Erro não esperado ao analisar planilha:', [
                'erro' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Erro ao analisar a planilha: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    protected function errorResponse($message, $status)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], $status);
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
    }
}
