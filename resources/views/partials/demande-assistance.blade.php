{{-- Admin d'une école : fenêtre d'accord quand le support KalanNet demande l'accès à un compte. --}}
@if(auth()->user()?->droit === 'Admin' && !session()->has('assistance'))
    @php
        $textesDemande = [
            'titre' => __('assistance.demande_titre'),
            'texte' => __('assistance.demande_texte', ['compte' => ':compte']),
            'motif' => __('assistance.demande_motif'),
            'restant' => __('assistance.demande_restant'),
            'accepter' => __('assistance.accepter'),
            'refuser' => __('assistance.refuser'),
        ];
    @endphp
    @push('scripts')
        <script>
            (function () {
                if (typeof Swal === 'undefined') return;
                const textes = @json($textesDemande);
                const urlDemande = @json(route('assistance.demande'));
                const urlReponse = @json(url('/assistance/__ID__/repondre'));
                const jeton = @json(csrf_token());
                const vues = new Set();
                const echapper = (t) => String(t).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

                async function repondre(id, decision) {
                    try {
                        const reponse = await fetch(urlReponse.replace('__ID__', id), {
                            method: 'POST',
                            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': jeton },
                            body: JSON.stringify({ decision }),
                        });
                        const donnees = await reponse.json();
                        Swal.fire({ icon: donnees.ok ? 'success' : 'info', text: donnees.message, timer: 4000, showConfirmButton: false });
                    } catch (e) { /* réseau */ }
                }

                async function verifier() {
                    if (Swal.isVisible()) return;
                    try {
                        const reponse = await fetch(urlDemande, { headers: { 'Accept': 'application/json' } });
                        const demande = (await reponse.json()).demande;
                        if (!demande || vues.has(demande.id) || demande.restant <= 0) return;
                        vues.add(demande.id);
                        let restant = demande.restant;
                        const html = '<p>' + echapper(textes.texte.replace(':compte', demande.compte)) + '</p>'
                            + '<p class="mb-1"><strong>' + echapper(textes.motif) + ' :</strong> ' + echapper(demande.motif) + '</p>'
                            + '<p class="small text-muted mb-0">' + echapper(textes.restant) + ' <strong id="assistance-delai"></strong></p>';
                        const chrono = setInterval(() => {
                            restant--;
                            const zone = document.getElementById('assistance-delai');
                            if (zone) zone.textContent = Math.floor(Math.max(restant, 0) / 60) + ':' + String(Math.max(restant, 0) % 60).padStart(2, '0');
                            if (restant <= 0) { clearInterval(chrono); Swal.close(); }
                        }, 1000);
                        const choix = await Swal.fire({
                            title: textes.titre, html, icon: 'question',
                            showCancelButton: true, confirmButtonText: textes.accepter, cancelButtonText: textes.refuser,
                            confirmButtonColor: '#198754', cancelButtonColor: '#d33',
                            allowOutsideClick: false, allowEscapeKey: false,
                            didOpen: () => { document.getElementById('assistance-delai').textContent = Math.floor(restant / 60) + ':' + String(restant % 60).padStart(2, '0'); },
                        });
                        clearInterval(chrono);
                        if (restant > 0 && (choix.isConfirmed || choix.dismiss === Swal.DismissReason.cancel)) {
                            repondre(demande.id, choix.isConfirmed ? 'accepter' : 'refuser');
                        }
                    } catch (e) { /* réseau : on réessaie au prochain passage */ }
                }
                verifier();
                setInterval(verifier, 10000);
            })();
        </script>
    @endpush
@endif
