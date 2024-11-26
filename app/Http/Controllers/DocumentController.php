<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentController extends Controller
{
    private $requiredColumns = [
        'TITULAR',
        'CPF',
        'NIS',
        'RG',
        'CONJUGE',
        'CPF CONJUGE',
        'RG CONJUGE',
        'NIS CONJUGE',
        'NASCIMENTO',
        'ENDEREÇO'
    ];

    public function store(Request $request)
    {
        try {
            $this->validateExcelFile($request);

            $file = $request->file('excel_file');
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao fazer o upload do arquivo Excel'
                ], 400);
            }

            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true); // Otimiza a leitura
            $spreadsheet = $reader->load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();

            // Validar cabeçalhos antes de processar
            $headerValidation = $this->validateHeaders($worksheet);
            if (!$headerValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Colunas obrigatórias ausentes no arquivo',
                    'missing_columns' => $headerValidation['missing_columns']
                ], 422);
            }

            $templateMap = $this->getTemplateMap();
            $downloadPaths = [];

            foreach ($templateMap as $templateKey => $templateInfo) {
                $templateProcessor = $this->loadTemplate($templateInfo['file']);
                $data = $this->readExcelDataUsingReader($worksheet);
                $this->populateTemplate($templateProcessor, $data, $templateInfo['chunkSize']);

                $outputPath = $this->saveDocument($templateProcessor, $templateKey);
                $downloadPaths[] = asset("storage/{$outputPath}");
            }

            return response()->json([
                'success' => true,
                'files' => $downloadPaths
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validateExcelFile(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx',
        ], [
            'excel_file.required' => 'O arquivo é obrigatório!',
            'excel_file.mimes' => 'O arquivo deve ser do tipo xls ou xlsx!',
        ]);
    }

    private function validateHeaders($worksheet)
    {
        $headerRow = [];
        $firstRow = $worksheet->getRowIterator()->current();
        $cellIterator = $firstRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cell) {
            $headerRow[] = $this->cleanString($cell->getValue());
        }

        $missingColumns = array_diff($this->requiredColumns, $headerRow);

        return [
            'valid' => empty($missingColumns),
            'missing_columns' => array_values($missingColumns)
        ];
    }

    private function getTemplateMap()
    {
        return [
            'termo_adesao' => ['file' => 'termo_adesao.docx', 'chunkSize' => 4],
            'criterios' => ['file' => 'criterios.docx', 'chunkSize' => 7],
        ];
    }

    private function loadTemplate($fileName)
    {
        $templatePath = storage_path("app/templates/{$fileName}");

        if (!file_exists($templatePath)) {
            throw new \Exception("Template {$fileName} não encontrado");
        }

        return new TemplateProcessor($templatePath);
    }

    private function readExcelDataUsingReader($worksheet)
    {
        $data = [];
        $header = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $rowIndex = $row->getRowIndex();
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue();

                if (Date::isDateTime($cell) && is_numeric($cellValue)) {
                    $cellValue = Date::excelToDateTimeObject($cellValue)->format('d/m/Y');
                }

                $rowData[] = $cellValue;
            }

            if ($rowIndex === 1) {
                $header = array_map(fn($value) => $this->cleanString($value), $rowData);
            } else {
                if (count($header) !== count($rowData)) {
                    throw new \Exception("Inconsistência de dados na linha {$rowIndex}");
                }
                $data[] = array_combine($header, $rowData);
            }
        }

        return $data;
    }

    private function cleanString($value)
    {
        if ($value === null) {
            return '';
        }

        if (!is_string($value)) {
            return $value;
        }

        return trim($value);
    }

    private function populateTemplate($templateProcessor, $data, $chunkSize)
    {
        $dataChunks = array_chunk($data, $chunkSize);
        $templateProcessor->cloneBlock('block_block', count($dataChunks), true, true);

        foreach ($dataChunks as $blockIndex => $chunk) {
            foreach ($chunk as $index => $item) {
                $placeholderSuffix = ($index + 1);
                $blockIdentifier = '#' . ($blockIndex + 1);

                $templateProcessor->setValue('nome_do_titular' . $placeholderSuffix . $blockIdentifier, $item['TITULAR'] ?? '');
                $templateProcessor->setValue('cpf_do_titular' . $placeholderSuffix . $blockIdentifier, $item['CPF'] ?? '');
                $templateProcessor->setValue('nis_tit' . $placeholderSuffix . $blockIdentifier, $item['NIS'] ?? '');
                $templateProcessor->setValue('rg_tit' . $placeholderSuffix . $blockIdentifier, $item['RG'] ?? '');
                $templateProcessor->setValue('nome_do_conjugue' . $placeholderSuffix . $blockIdentifier, $item['CONJUGE'] ?? '');
                $templateProcessor->setValue('cpf_do_conjugue' . $placeholderSuffix . $blockIdentifier, $item['CPFCONJUGE'] ?? '');
                $templateProcessor->setValue('rg_conj' . $placeholderSuffix . $blockIdentifier, $item['RGCONJUGE'] ?? '');
                $templateProcessor->setValue('nis_conj' . $placeholderSuffix . $blockIdentifier, $item['NISCONJUGE'] ?? '');
                $templateProcessor->setValue('nascimento' . $placeholderSuffix . $blockIdentifier, $item['NASCIMENTO'] ?? '');
                $templateProcessor->setValue('endereco' . $placeholderSuffix . $blockIdentifier, $item['ENDERECO'] ?? '');
            }
        }
    }

    private function saveDocument($templateProcessor, $templateKey)
    {
        $outputFileName = "{$templateKey}_" . now()->timestamp . ".docx";
        $outputPath = "public/{$outputFileName}";
        $templateProcessor->saveAs(storage_path("app/{$outputPath}"));

        if (!file_exists(storage_path("app/{$outputPath}"))) {
            throw new \Exception("Erro ao salvar o arquivo {$outputFileName}");
        }

        return $outputFileName;
    }
}
