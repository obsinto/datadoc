<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentController extends Controller
{
    //adicionar no php.ini upload_max_filesize = 10M  ; ou o tamanho necessário
    //post_max_size = 10M  ; deve ser maior ou igual ao upload_max_filesize
    public function store(Request $request)
    {

        try {
            // Validação
            $validateData = $request->validate([
                'excel_file' => 'required'
            ], [
                'excel_file.required' => 'O arquivo é obrigatório!',
            ]);

            // Obtem o arquivo
            $file = $request->file('excel_file');

            // Verifica se o arquivo é válido
            if (!$file || !$file->isValid()) {
                return back()
                    ->withErrors(['excel_file' => 'Erro ao fazer o upload do arquivo Excel'])
                    ->withInput();
            }

            // Caminho temporário do arquivo
            $filePath = $file->getRealPath();

            // Carrega a planilha Excel
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            dd($worksheet);
            // Carrega o template Word
            $templatePath = storage_path('app/template.docx');

            if (!file_exists($templatePath)) {
                return back()
                    ->withErrors(['template' => 'Template não encontrado'])
                    ->withInput();
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // Lê dados da planilha e insere no template Word
            $data = [];
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $data[] = $cell->getValue();
                }
            }

            // Exemplo de inserção de dados no template
            $templateProcessor->setValue('campo1', $data[0] ?? '');
            $templateProcessor->setValue('campo2', $data[1] ?? '');

            // Define o caminho para salvar o documento preenchido
            $outputPath = storage_path('app/public/documento_preenchido.docx');
            $templateProcessor->saveAs($outputPath);

            return response()->download($outputPath)->deleteFileAfterSend();

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Ocorreu um erro ao processar o arquivo: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
