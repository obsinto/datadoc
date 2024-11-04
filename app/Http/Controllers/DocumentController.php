<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Validação
            $validateData = $request->validate([
                'excel_file' => 'required|file|mimes:xls,xlsx',
            ], [
                'excel_file.required' => 'O arquivo é obrigatório!',
                'excel_file.mimes' => 'O arquivo deve ser do tipo xls ou xlsx!',
            ]);

            // Obtem o arquivo
            $file = $request->file('excel_file');

            // Verifica se o arquivo é válido
            if (!$file || !$file->isValid()) {
                return back()
                    ->withErrors(['excel_file' => 'Erro ao fazer o upload do arquivo Excel'])
                    ->withInput();
            }

            $filePath = $file->getRealPath();
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Mapeamento de templates
            $templateMap = [
                'termo_adesao' => 'termo_adesao.docx',
                'criterios' => 'criterios.dot.docx',
            ];

            // Processar cada template
            foreach ($templateMap as $templateKey => $templateFile) {
                $templatePath = storage_path('app/templates/' . $templateFile);

                if (!file_exists($templatePath)) {
                    return back()
                        ->withErrors(['template' => "Template $templateFile não encontrado"])
                        ->withInput();
                }

                $templateProcessor = new TemplateProcessor($templatePath);

                $cleanString = function ($value) {
                    return $value === null || !is_string($value) ? '' : str_replace(' ', '', $value);
                };

                // Lê dados da planilha e insere no template Word
                $data = [];
                $header = [];
                foreach ($worksheet->getRowIterator() as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    if ($row->getRowIndex() === 1) {
                        foreach ($cellIterator as $cell) {
                            $header[] = $cleanString($cell->getValue());
                        }
                    } else {
                        $rowData = [];
                        foreach ($cellIterator as $cell) {
                            $rowData[] = $cell->getValue();
                        }
                        $data[] = array_combine($header, $rowData);
                    }
                }

                $dataChunks = array_chunk($data, 4);
                $templateProcessor->cloneBlock('block_block', count($dataChunks), true, true);

                foreach ($dataChunks as $blockIndex => $chunk) {
                    foreach ($chunk as $index => $item) {
                        $placeholderSuffix = ($index + 1);
                        $templateProcessor->setValue('nome_do_titular' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['TITULAR'] ?? '');
                        $templateProcessor->setValue('cpf_do_titular' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['CPF'] ?? '');
                        $templateProcessor->setValue('nis_do_titular' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['NIS'] ?? '');
                        $templateProcessor->setValue('ci_do_titular' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['RG'] ?? '');
                        $templateProcessor->setValue('nome_do_conjugue' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['CONJUGE'] ?? '');
                        $templateProcessor->setValue('cpf_do_conjugue' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['CPFCONJUGE'] ?? '');
                        $templateProcessor->setValue('ci_do_conjugue' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['RGCONJUGE'] ?? '');
                        $templateProcessor->setValue('nis_do_conjugue' . $placeholderSuffix . '#' . ($blockIndex + 1), $item['NISCONJUGE'] ?? '');
                    }
                }

                // Salvar o documento preenchido para cada template
                $outputPath = storage_path("app/public/documento_preenchido_{$templateKey}.docx");
                $templateProcessor->saveAs($outputPath);
            }

            return response()->download(storage_path("app/public/documento_preenchido_{$templateKey}.docx"))->deleteFileAfterSend();

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
