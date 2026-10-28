<?php

namespace App\Http\Controllers;

use App\Services\Support\CsbSupportClient;
use App\Services\Support\CsbSupportUnavailable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * « Support CSB » : chaque utilisateur (Admin, enseignant, parent…) ouvre un ticket auprès de Computer Service Barry,
 * suit les réponses et répond, sans quitter KalanNet. Les tickets vivent sur le site CSB (CsbSupportClient).
 */
class SupportCsbController extends Controller
{
    private const FICHIERS = 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,odt,ods,jpg,jpeg,png,webp,txt,log,zip|max:10240';

    public function __construct(private CsbSupportClient $csb)
    {
    }

    public function index()
    {
        return $this->ouIndisponible(fn () => view('support-csb.index', ['tickets' => $this->csb->tickets(Auth::user())]));
    }

    public function create()
    {
        return $this->ouIndisponible(fn () => view('support-csb.create', ['options' => $this->csb->options()]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:200',
            'category' => 'required|string|max:20',
            'priority' => 'required|string|max:20',
            'device' => 'nullable|string|max:200',
            'message' => 'required|string|min:10|max:10000',
            'attachment' => self::FICHIERS,
        ], ['message.min' => __('support_csb.message_court')], [
            'subject' => __('support_csb.objet'), 'category' => __('support_csb.type'), 'priority' => __('support_csb.urgence'),
            'message' => __('support_csb.description'), 'attachment' => __('support_csb.piece_jointe'),
        ]);

        return $this->ouIndisponible(function () use ($request, $data) {
            $ticket = $this->csb->open(Auth::user(), collect($data)->except('attachment')->all(), $request->file('attachment'));

            return redirect()->route('support-csb.show', $ticket['number'])->with('success', __('support_csb.envoye', ['numero' => $ticket['number']]));
        }, back: true);
    }

    public function show(string $numero)
    {
        return $this->ouIndisponible(fn () => view('support-csb.show', ['ticket' => $this->csb->ticket(Auth::user(), $numero)]));
    }

    public function reply(Request $request, string $numero)
    {
        $data = $request->validate(['body' => 'required|string|max:10000', 'attachment' => self::FICHIERS], [], ['body' => __('support_csb.message')]);

        return $this->ouIndisponible(function () use ($request, $numero, $data) {
            $this->csb->reply(Auth::user(), $numero, $data['body'], $request->file('attachment'));

            return redirect()->route('support-csb.show', $numero)->with('success', __('support_csb.reponse_envoyee'));
        }, back: true);
    }

    public function attachment(string $numero, int $message)
    {
        return $this->ouIndisponible(function () use ($numero, $message) {
            $response = $this->csb->attachment(Auth::user(), $numero, $message);

            return response($response->body(), 200, array_filter([
                'Content-Type' => $response->header('Content-Type') ?: 'application/octet-stream',
                'Content-Disposition' => $response->header('Content-Disposition') ?: 'attachment',
            ]));
        });
    }

    /** CSB injoignable : la page l'explique au lieu d'une erreur (ou retour au formulaire, saisie conservée). */
    private function ouIndisponible(callable $action, bool $back = false)
    {
        try {
            return $action();
        } catch (CsbSupportUnavailable $e) {
            return $back ? back()->withInput()->with('error', $e->getMessage()) : view('support-csb.indisponible', ['message' => $e->getMessage()]);
        }
    }
}
