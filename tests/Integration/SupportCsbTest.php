<?php

namespace Tests\Integration;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/** Support CSB : un utilisateur ouvre et suit ses tickets auprès de CSB (API du site CSB simulée). */
class SupportCsbTest extends IntegrationTestCase
{
    private function ticket(array $extra = []): array
    {
        return $extra + [
            'number' => 'TCK-2026-0042', 'subject' => 'Bulletins vides', 'category' => 'logiciel', 'category_label' => 'Assistance sur un logiciel',
            'priority' => 'haute', 'priority_label' => 'Haute', 'status' => 'attente_client', 'status_label' => 'En attente de votre réponse',
            'is_open' => true, 'awaiting_reporter' => true, 'device' => null, 'created_at' => now()->toIso8601String(), 'last_activity_at' => now()->toIso8601String(), 'closed_at' => null,
            'messages' => [
                ['id' => 1, 'from' => 'reporter', 'author' => 'Moi', 'body' => 'Page blanche à l\'impression.', 'created_at' => now()->toIso8601String(), 'attachment' => null],
                ['id' => 2, 'from' => 'csb', 'author' => 'Awa', 'body' => 'Videz le cache du navigateur.', 'created_at' => now()->toIso8601String(), 'attachment' => ['name' => 'guide.pdf', 'size' => 1200]],
            ],
        ];
    }

    public function test_un_utilisateur_ouvre_et_suit_un_ticket_csb(): void
    {
        config(['services.csb_support.url' => 'https://csb.test', 'services.csb_support.token' => 'csb_test']);
        Cache::forget('csb_support.options');
        Http::fake([
            'csb.test/api/v1/tickets/options' => Http::response(['categories' => ['logiciel' => 'Assistance sur un logiciel'], 'priorities' => ['normale' => 'Normale', 'haute' => 'Haute']]),
            'csb.test/api/v1/tickets/TCK-2026-0042/messages' => Http::response(['data' => $this->ticket()], 201),
            'csb.test/api/v1/tickets/TCK-2026-0042*' => Http::response(['data' => $this->ticket()]),
            'csb.test/api/v1/tickets*' => fn (Request $r) => $r->method() === 'POST' ? Http::response(['data' => $this->ticket()], 201) : Http::response(['data' => [$this->ticket()]]),
        ]);
        $enseignant = $this->compte('enseignant', 1);

        $this->actingAs($enseignant)->withSession(['idEcole' => 1])->get('/dashboard')->assertSee(__('support_csb.menu'));
        $this->get('/support-csb')->assertOk()->assertSee('Bulletins vides')->assertSee(__('support_csb.a_vous'));
        $this->get('/support-csb/nouveau')->assertOk()->assertSee('Assistance sur un logiciel');

        $this->post('/support-csb', ['subject' => 'Bulletins vides', 'category' => 'logiciel', 'priority' => 'haute', 'message' => 'Page blanche à l\'impression.'])
            ->assertRedirect('/support-csb/TCK-2026-0042')->assertSessionHas('success');
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://csb.test/api/v1/tickets'
            && $r->hasHeader('Authorization', 'Bearer csb_test') && $r['reporter']['ref'] === (string) $enseignant->idUtilisateur
            && $r['reporter']['name'] === $enseignant->nomPrenom);

        $this->get('/support-csb/TCK-2026-0042')->assertOk()->assertSee('Videz le cache du navigateur.')->assertSee('guide.pdf');
        $this->post('/support-csb/TCK-2026-0042/messages', ['body' => 'Toujours vide.'])->assertRedirect('/support-csb/TCK-2026-0042');
    }

    public function test_csb_injoignable_ou_non_configure(): void
    {
        $parent = $this->compte('parent', 1);

        config(['services.csb_support.url' => null, 'services.csb_support.token' => null]);
        $this->actingAs($parent)->withSession(['idEcole' => 1])->get('/dashboard')->assertDontSee(__('support_csb.menu'));
        $this->get('/support-csb')->assertOk()->assertSee(__('support_csb.non_configure'));

        config(['services.csb_support.url' => 'https://csb.test', 'services.csb_support.token' => 'csb_test']);
        Http::fake(['csb.test/*' => Http::response('', 503)]);
        $this->get('/support-csb')->assertOk()->assertSee(__('support_csb.injoignable'));
        $this->from('/support-csb/nouveau')->post('/support-csb', ['subject' => 'Panne', 'category' => 'logiciel', 'priority' => 'haute', 'message' => 'Plus rien ne marche depuis ce matin.'])
            ->assertRedirect('/support-csb/nouveau')->assertSessionHas('error')->assertSessionHasInput('subject', 'Panne');
    }
}
