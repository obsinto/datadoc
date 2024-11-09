<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        try {
            $validateData = $request->validate([
                'excel_file' => 'required|file|mimes:xls,xlsx',
            ], [
                'excel_file.required' => 'O arquivo é obrigatório!',
                'excel_file.mimes' => 'O arquivo deve ser do tipo xls ou xlsx!',
            ]);

            $file = $request->file('excel_file');
            if (!$file || !$file->isValid()) {
                return back()->withErrors(['excel_file' => 'Erro ao fazer o upload do arquivo Excel'])->withInput();
            }

            $filePath = $file->getRealPath();
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            // Mapeamento de templates e tamanhos de bloco
            $templateMap = [
                'termo_adesao' => ['file' => 'termo_adesao.docx', 'chunkSize' => 4],
                'criterios' => ['file' => 'criterios.docx', 'chunkSize' => 7],
            ];

            $downloadPaths = []; // Armazena os caminhos para download

            foreach ($templateMap as $templateKey => $templateInfo) {
                $templatePath = storage_path('app/templates/' . $templateInfo['file']);

                if (!file_exists($templatePath)) {
                    return back()->withErrors(['template' => "Template {$templateInfo['file']} não encontrado"])->withInput();
                }

                $templateProcessor = new TemplateProcessor($templatePath);

                // Função de limpeza de strings
                $cleanString = function ($value) {
                    return $value === null || !is_string($value) ? '' : str_replace(' ', '', $value);
                };

                // Leitura dos dados do Excel e preenchimento do template
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
                            $cellValue = $cell->getValue();

                            // Verifica se o valor é uma data serial
                            if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell) && is_numeric($cellValue)) {
                                // Converte para o formato legível
                                $cellValue = Date::excelToDateTimeObject($cellValue)->format('d/m/Y');
                            }

                            $rowData[] = $cellValue;
                        }
                        $data[] = array_combine($header, $rowData);
                    }
                }

                // Define o tamanho do bloco com base no template
                $chunkSize = $templateInfo['chunkSize'];
                $dataChunks = array_chunk($data, $chunkSize);
                $templateProcessor->cloneBlock('block_block', count($dataChunks), true, true);
                foreach ($dataChunks as $blockIndex => $chunk) {
                    foreach ($chunk as $index => $item) {
                        $placeholderSuffix = ($index + 1);
                        $blockIdentifier = '#' . ($blockIndex + 1);

                        // Substituições para titulares e coobrigados
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

                // Salvar o documento preenchido
                $outputPath = storage_path("app/public/documento_preenchido_{$templateKey}.docx");
                $templateProcessor->saveAs($outputPath);
                $downloadPaths[] = asset("storage/documento_preenchido_{$templateKey}.docx");
            }


            // Retorna os caminhos dos arquivos para o JavaScript
            return response()->json(['files' => $downloadPaths]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage()])->withInput();
        }
    }

}
