<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentController extends Controller
{
    private $templateMap = [
        'termo_adesao' => ['file' => 'termo_adesao.docx', 'chunkSize' => 4],
        'criterios' => ['file' => 'criterios.docx', 'chunkSize' => 7],
    ];

    public function store(Request $request)
    {
        try {
            Log::info('Iniciando processamento do arquivo Excel');

            if (!$request->hasFile('excel_file')) {
                throw new Exception('Nenhum arquivo foi enviado.');
            }

            $file = $request->file('excel_file');
            Log::info('Arquivo recebido', [
                'nome' => $file->getClientOriginalName(),
                'tamanho' => $file->getSize(),
                'tipo' => $file->getMimeType()
            ]);

            $inputFileType = IOFactory::identify($file->getRealPath());
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);

            if ($inputFileType === 'Xls') {
                Log::info('Arquivo XLS detectado, usando configurações específicas');
                $reader->setReadEmptyCells(false);
            }

            $spreadsheet = $reader->load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            Log::info('Excel carregado com sucesso');

            $data = $this->readExcelData($worksheet);
            if (empty($data)) {
                throw new Exception('Nenhum dado válido encontrado na planilha.');
            }

            Log::info('Dados lidos com sucesso', [
                'total_registros' => count($data)
            ]);

            $downloadPaths = [];
            foreach ($this->templateMap as $templateKey => $templateInfo) {
                $downloadPaths[] = $this->processTemplate($templateKey, $templateInfo, $data);
            }

            return response()->json([
                'success' => true,
                'files' => $downloadPaths
            ]);

        } catch (Exception $e) {
            Log::error('Erro no processamento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar o arquivo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function readExcelData($worksheet)
    {
        $data = [];
        $headers = [];

        // Lê os headers
        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $value = trim($cell->getValue());
                if (!empty($value)) {
                    $headers[] = $value;
                }
            }
        }

        Log::info('Headers encontrados', ['headers' => $headers]);

        if (empty($headers)) {
            throw new Exception('Nenhum cabeçalho encontrado na planilha');
        }

        // Lê os dados
        foreach ($worksheet->getRowIterator(2) as $row) {
            $rowData = [];
            $hasData = false;
            $colIndex = 0;

            foreach ($row->getCellIterator() as $cell) {
                if (isset($headers[$colIndex])) {
                    $value = $cell->getValue();

                    if ($cell->isFormula()) {
                        $value = $cell->getCalculatedValue();
                    }

                    if (Date::isDateTime($cell)) {
                        try {
                            $value = Date::excelToDateTimeObject($value)->format('d/m/Y');
                        } catch (Exception $e) {
                            $value = '';
                        }
                    }

                    if ($value !== null && $value !== '') {
                        $hasData = true;
                    }

                    $rowData[$headers[$colIndex]] = $value;
                }
                $colIndex++;
            }

            if ($hasData) {
                $data[] = $rowData;
            }
        }

        return $data;
    }

    private function processTemplate($templateKey, $templateInfo, $data)
    {
        try {
            Log::info("Iniciando processamento do template: {$templateKey}");

            $templatePath = storage_path("app/templates/{$templateInfo['file']}");
            if (!file_exists($templatePath)) {
                throw new Exception("Template não encontrado: {$templateInfo['file']}");
            }

            $templateProcessor = new TemplateProcessor($templatePath);
            Log::info("Template carregado com sucesso");

            $chunks = array_chunk($data, $templateInfo['chunkSize']);
            Log::info("Dados divididos em chunks", ['quantidade' => count($chunks)]);

            $templateProcessor->cloneBlock('block_block', count($chunks), true, true);

            foreach ($chunks as $blockIndex => $chunk) {
                foreach ($chunk as $index => $item) {
                    $this->fillTemplateValues($templateProcessor, $item, $index + 1, $blockIndex + 1);
                }
            }

            $outputFileName = "documento_preenchido_{$templateKey}.docx";
            $outputPath = storage_path("app/public/{$outputFileName}");
            $templateProcessor->saveAs($outputPath);

            Log::info("Documento salvo com sucesso", ['caminho' => $outputPath]);

            return asset("storage/{$outputFileName}");

        } catch (Exception $e) {
            Log::error("Erro processando template {$templateKey}: " . $e->getMessage());
            throw $e;
        }
    }

    private function fillTemplateValues($templateProcessor, $item, $index, $blockIndex)
    {
        try {
            $blockId = '#' . $blockIndex;

            // Processa e valida os dados antes de formatar
            $titular = $this->processField($item['TITULAR'], 'text');
            $cpfTitular = $this->processField($item['CPF'], 'cpf');
            $conjuge = $this->processField($item['CONJUGE'], 'text');
            $cpfConjuge = $this->processField($item['CPF CONJUGE'], 'cpf');

            $replacements = [
                'nome_do_titular' => $titular,
                'cpf_do_titular' => $cpfTitular,
                'nis_tit' => $this->formatNIS($item['NIS']),
                'rg_tit' => $this->formatRG($item['RG']),
                'nome_do_conjugue' => $conjuge,
                'cpf_do_conjugue' => $cpfConjuge,
                'rg_conj' => $this->formatRG($item['RG CONJUGE']),
                'nis_conj' => $this->formatNIS($item['NIS CONJUGE']),
                'nascimento' => $this->formatDate($item['NASCIMENTO']),
                'endereco' => $this->formatTextValue($item['ENDEREÇO'])
            ];

            foreach ($replacements as $field => $value) {
                $placeholder = $field . $index . $blockId;
                $templateProcessor->setValue($placeholder, $value);
            }

        } catch (Exception $e) {
            Log::error("Erro ao preencher valores do template", [
                'bloco' => $blockIndex,
                'index' => $index,
                'erro' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function processField($value, $type)
    {
        if (empty($value) && $value !== '0') {
            return '';
        }

        switch ($type) {
            case 'text':
                if (is_numeric($value)) {
                    return ''; // Ignora valores numéricos em campos de texto
                }
                return $this->formatTextValue($value);

            case 'cpf':
                if (!is_numeric($value) && strlen($value) > 14) {
                    return ''; // Ignora textos longos em campos de CPF
                }
                return $this->formatCPF($value);

            default:
                return $value;
        }
    }

    private function formatTextValue($value)
    {
        if (empty($value)) {
            return '';
        }

        $text = trim((string)$value);
        $text = preg_replace('/\s+/', ' ', $text);

        // Capitaliza as palavras
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    private function formatCPF($value)
    {
        if (empty($value)) {
            return '';
        }

        // Remove tudo que não é número
        $cpf = preg_replace('/[^0-9]/', '', (string)$value);

        if (strlen($cpf) !== 11) {
            return '';
        }

        return substr($cpf, 0, 3) . '.' .
            substr($cpf, 3, 3) . '.' .
            substr($cpf, 6, 3) . '-' .
            substr($cpf, 9, 2);
    }

    private function formatRG($value)
    {
        if (empty($value)) {
            return '';
        }

        if (is_numeric($value)) {
            return (string)$value;
        }

        return preg_replace('/[^0-9]/', '', (string)$value);
    }

    private function formatNIS($value)
    {
        if (empty($value)) {
            return '';
        }

        return preg_replace('/[^0-9]/', '', (string)$value);
    }

    private function formatDate($value)
    {
        if (empty($value)) {
            return '';
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            return $value;
        }

        if (is_numeric($value)) {
            try {
                return Date::excelToDateTimeObject($value)->format('d/m/Y');
            } catch (Exception $e) {
                return '';
            }
        }

        return '';
    }
}
