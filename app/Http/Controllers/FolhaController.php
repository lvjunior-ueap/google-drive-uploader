<?php

namespace App\Http\Controllers;

use App\Models\Folha;
use App\Models\Ticket;
use App\Models\User;
use App\Services\FolhaPontoService;
use Illuminate\Http\File;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolhaController extends Controller
{
    protected GoogleDriveService $drive;

    public function __construct(GoogleDriveService $drive)
    {
        $this->drive = $drive;
    }

    public function index()
    {
        // Se não passar nada, pega a pasta root (Shared Drive root definida no .env)
        $folderId = request()->query('folderId', env('GOOGLE_DRIVE_FOLDER_ID'));

        // Lista o conteúdo da pasta (pastas ou arquivos)
        $items = $this->drive->listFiles($folderId);

        // Pegamos também o "pai" (pra permitir voltar para a pasta anterior)
        $currentFolderId = $folderId;

        return view('folhas.index', compact('items', 'currentFolderId'));
    }

    public function create()
    {
        $users = User::orderBy('login')->with('person')->get();
        return view('folhas.create', compact('users'));
    }

    public function store(Request $request)
    {
        dd($request);
        $request->validate([
            'user_id' => 'required',
            'year' => 'required',
            'month' => 'required',
            'file' => 'required',
        ]);

        $uploadedFile = $request->file('file'); // UploadedFile
        $pdfFile = new File($uploadedFile->getRealPath()); // converte para Illuminate\Http\File

        $file = $this->drive->upload($pdfFile, env('GOOGLE_DRIVE_FOLDER_ID'));

        // Folha::create([
        //     'user_id' => Auth::id(),
        //     'original_name' => $file->name,
        //     'drive_file_id' => $file->id,
        //     'drive_web_view_link' => $file->webViewLink,
        //     'drive_web_content_link' => $file->webContentLink,
        // ]);

        return redirect()->route('folhas.index')->with('success', 'Folha enviada com sucesso!');
    }

    public function destroy(string $id)
    {
        if ($this->drive->deleteFile($id)) {
            return redirect()->back()->with('success', 'Arquivo removido com sucesso!');
        }

        return redirect()->back()->with('error', 'Erro ao remover arquivo.');
    }

    public function enviar(Request $request, FolhaPontoService $ponto)
    {
        $file = $request->file('file');

        $year = $request->year;           // Ex: "2025"
        $month = $request->month;     // Ex: "Agosto"

        $user = User::where('id', $request->user_id)->first();

        try {
            $ponto->submitSheet($user, $year, $month, $file);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        return redirect()->route('folhas.index')->with('success', 'Folha enviada com sucesso!');
    }
}
