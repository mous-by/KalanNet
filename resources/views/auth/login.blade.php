<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - KalanNet (Alliance Team)</title>
    
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Rubik:300,400,500,600%7CIBM+Plex+Sans:300,400,500,600,700" rel="stylesheet">
    
    <style>
        body {
            background-image: url('{{ asset('assets/images/télécharger.jpeg') }}') !important;
            background-size: cover;
            background-position: center;
            font-family: 'Segoe UI', sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            max-width: 450px;
            width: 100%;
        }

        .logo-msis {
            width: 120px;
            margin-bottom: 20px;
        }

        .form-title {
            font-weight: 700;
            font-size: 1.8rem;
            color: #003366;
            margin-bottom: 5px;
        }

        .form-subtitle {
            color: #6c757d;
            margin-bottom: 30px;
        }

        .btn-primary {
            background-color: #003366;
            border-color: #003366;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            background-color: #002244;
            transform: translateY(-2px);
        }

        .form-control {
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .school-card {
            background-color: #ffffff;
            border: 2px solid #f1f3f5;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 12px;
            cursor: pointer;
        }

        .school-card:hover {
            border-color: #003366;
            background-color: #f8f9fa;
            transform: scale(1.02);
        }

        .school-logo {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            overflow: hidden;
            background: #eee;
        }
    </style>
</head>
<body>
    <div class="overlay text-center">
        <img src="{{ asset('assets/images/logo_gsco1.png') }}" alt="Logo" class="logo-msis">
        <div class="form-title">Connexion</div>
        <p class="form-subtitle">Bienvenue sur votre espace GESCO</p>

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-4 py-2">
                <ul class="mb-0 list-unstyled small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST" class="text-start">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">Adresse Email</label>
                <input type="email" name="email" class="form-control" placeholder="exemple@ecole.com" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Mot de passe</label>
                <input type="password" name="pwd" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Se Connecter</button>
            </div>
        </form>

        <div class="mt-4 small text-muted">
            &copy; {{ date('Y') }} | Alliance Team GESCO
        </div>
    </div>

    @if(isset($ecoles_modal))
        <div class="modal fade" id="schoolModal" data-bs-backdrop="static" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Choisir un établissement</h5>
                    </div>
                    <div class="modal-body">
                        @foreach($ecoles_modal as $school)
                            <form action="{{ route('login.select-school') }}" method="POST">
                                @csrf
                                <input type="hidden" name="idUtilisateur" value="{{ $school->idUtilisateur }}">
                                <input type="hidden" name="idEcole" value="{{ $school->idEcole }}">
                                <button type="submit" class="btn w-100 p-3 school-card text-start">
                                    <div class="d-flex align-items-center">
                                        <div class="school-logo me-3 d-flex align-items-center justify-content-center text-muted fw-bold">
                                            @if($school->ecole && $school->ecole->logoEcole)
                                                <img src="{{ asset($school->ecole->logoEcole) }}" class="w-100 h-100 object-fit-cover">
                                            @else
                                                <i class="bi bi-building"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $school->ecole ? $school->ecole->nomEcole : 'N/A' }}</div>
                                            <small class="text-muted">{{ $school->ecole ? $school->ecole->typeEcole : 'N/A' }}</small>
                                        </div>
                                    </div>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
        <script>
            $(document).ready(function() {
                var myModal = new bootstrap.Modal(document.getElementById('schoolModal'));
                myModal.show();
            });
        </script>
    @endif
</body>
</html>
