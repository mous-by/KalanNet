<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed de tous les pays reconnus par l'ONU (~195), en plus du Mali et de la
 * Guinee deja semes precedemment. Chaque ligne : indicatif telephonique,
 * longueur du numero local, devise (code ISO 4217 + libelle affiche).
 *
 * Les indicatifs telephoniques et codes devise (ISO 4217) sont fiables.
 * La longueur du numero local est une valeur usuelle par pays (certains
 * pays tolerent plusieurs longueurs en pratique) : a corriger au cas par
 * cas si une validation reelle la rejette a tort une fois la phase
 * "telephone" activee pour ce pays.
 */
return new class extends Migration
{
    public function up(): void
    {
        $pays = [
            // --- Afrique de l'Ouest (UEMOA + voisins) ---
            ['Sénégal', 'SN', '+221', 9, null, 'XOF', 'FCFA', 0],
            ['Côte d’Ivoire', 'CI', '+225', 10, null, 'XOF', 'FCFA', 0],
            ['Burkina Faso', 'BF', '+226', 8, null, 'XOF', 'FCFA', 0],
            ['Bénin', 'BJ', '+229', 10, null, 'XOF', 'FCFA', 0],
            ['Niger', 'NE', '+227', 8, null, 'XOF', 'FCFA', 0],
            ['Togo', 'TG', '+228', 8, null, 'XOF', 'FCFA', 0],
            ['Guinée-Bissau', 'GW', '+245', 9, null, 'XOF', 'FCFA', 0],
            ['Cap-Vert', 'CV', '+238', 7, null, 'CVE', 'CVE', 2],
            ['Gambie', 'GM', '+220', 7, null, 'GMD', 'GMD', 2],
            ['Sierra Leone', 'SL', '+232', 8, null, 'SLE', 'SLE', 2],
            ['Liberia', 'LR', '+231', 8, null, 'LRD', 'LRD', 2],
            ['Ghana', 'GH', '+233', 9, null, 'GHS', 'GHS', 2],
            ['Nigeria', 'NG', '+234', 10, null, 'NGN', 'NGN', 2],
            ['Mauritanie', 'MR', '+222', 8, null, 'MRU', 'MRU', 2],

            // --- Afrique centrale (CEMAC + voisins) ---
            ['Cameroun', 'CM', '+237', 9, null, 'XAF', 'FCFA', 0],
            ['Gabon', 'GA', '+241', 8, null, 'XAF', 'FCFA', 0],
            ['Congo', 'CG', '+242', 9, null, 'XAF', 'FCFA', 0],
            ['République démocratique du Congo', 'CD', '+243', 9, null, 'CDF', 'CDF', 2],
            ['République centrafricaine', 'CF', '+236', 8, null, 'XAF', 'FCFA', 0],
            ['Tchad', 'TD', '+235', 8, null, 'XAF', 'FCFA', 0],
            ['Guinée équatoriale', 'GQ', '+240', 9, null, 'XAF', 'FCFA', 0],
            ['Sao Tomé-et-Principe', 'ST', '+239', 7, null, 'STN', 'STN', 2],
            ['Angola', 'AO', '+244', 9, null, 'AOA', 'AOA', 2],

            // --- Afrique du Nord ---
            ['Maroc', 'MA', '+212', 9, null, 'MAD', 'MAD', 2],
            ['Algérie', 'DZ', '+213', 9, null, 'DZD', 'DZD', 2],
            ['Tunisie', 'TN', '+216', 8, null, 'TND', 'TND', 3],
            ['Libye', 'LY', '+218', 9, null, 'LYD', 'LYD', 3],
            ['Égypte', 'EG', '+20', 10, null, 'EGP', 'EGP', 2],
            ['Soudan', 'SD', '+249', 9, null, 'SDG', 'SDG', 2],
            ['Soudan du Sud', 'SS', '+211', 9, null, 'SSP', 'SSP', 2],

            // --- Afrique de l'Est ---
            ['Éthiopie', 'ET', '+251', 9, null, 'ETB', 'ETB', 2],
            ['Kenya', 'KE', '+254', 9, null, 'KES', 'KES', 2],
            ['Ouganda', 'UG', '+256', 9, null, 'UGX', 'UGX', 0],
            ['Tanzanie', 'TZ', '+255', 9, null, 'TZS', 'TZS', 2],
            ['Rwanda', 'RW', '+250', 9, null, 'RWF', 'RWF', 0],
            ['Burundi', 'BI', '+257', 8, null, 'BIF', 'BIF', 0],
            ['Somalie', 'SO', '+252', 8, null, 'SOS', 'SOS', 2],
            ['Djibouti', 'DJ', '+253', 8, null, 'DJF', 'DJF', 0],
            ['Érythrée', 'ER', '+291', 7, null, 'ERN', 'ERN', 2],

            // --- Afrique australe / océan Indien ---
            ['Afrique du Sud', 'ZA', '+27', 9, null, 'ZAR', 'ZAR', 2],
            ['Namibie', 'NA', '+264', 9, null, 'NAD', 'NAD', 2],
            ['Botswana', 'BW', '+267', 8, null, 'BWP', 'BWP', 2],
            ['Zimbabwe', 'ZW', '+263', 9, null, 'USD', '$', 2],
            ['Zambie', 'ZM', '+260', 9, null, 'ZMW', 'ZMW', 2],
            ['Mozambique', 'MZ', '+258', 9, null, 'MZN', 'MZN', 2],
            ['Malawi', 'MW', '+265', 9, null, 'MWK', 'MWK', 2],
            ['Madagascar', 'MG', '+261', 9, null, 'MGA', 'MGA', 0],
            ['Maurice', 'MU', '+230', 8, null, 'MUR', 'MUR', 2],
            ['Comores', 'KM', '+269', 7, null, 'KMF', 'KMF', 0],
            ['Seychelles', 'SC', '+248', 7, null, 'SCR', 'SCR', 2],
            ['Lesotho', 'LS', '+266', 8, null, 'LSL', 'LSL', 2],
            ['Eswatini', 'SZ', '+268', 8, null, 'SZL', 'SZL', 2],

            // --- Europe ---
            ['France', 'FR', '+33', 9, null, 'EUR', '€', 2],
            ['Belgique', 'BE', '+32', 9, null, 'EUR', '€', 2],
            ['Suisse', 'CH', '+41', 9, null, 'CHF', 'CHF', 2],
            ['Luxembourg', 'LU', '+352', 9, null, 'EUR', '€', 2],
            ['Allemagne', 'DE', '+49', 10, null, 'EUR', '€', 2],
            ['Espagne', 'ES', '+34', 9, null, 'EUR', '€', 2],
            ['Portugal', 'PT', '+351', 9, null, 'EUR', '€', 2],
            ['Italie', 'IT', '+39', 10, null, 'EUR', '€', 2],
            ['Royaume-Uni', 'GB', '+44', 10, null, 'GBP', '£', 2],
            ['Irlande', 'IE', '+353', 9, null, 'EUR', '€', 2],
            ['Pays-Bas', 'NL', '+31', 9, null, 'EUR', '€', 2],
            ['Autriche', 'AT', '+43', 10, null, 'EUR', '€', 2],
            ['Pologne', 'PL', '+48', 9, null, 'PLN', 'PLN', 2],
            ['Roumanie', 'RO', '+40', 9, null, 'RON', 'RON', 2],
            ['Grèce', 'GR', '+30', 10, null, 'EUR', '€', 2],
            ['Suède', 'SE', '+46', 9, null, 'SEK', 'SEK', 2],
            ['Norvège', 'NO', '+47', 8, null, 'NOK', 'NOK', 2],
            ['Danemark', 'DK', '+45', 8, null, 'DKK', 'DKK', 2],
            ['Finlande', 'FI', '+358', 9, null, 'EUR', '€', 2],
            ['Russie', 'RU', '+7', 10, null, 'RUB', 'RUB', 2],
            ['Ukraine', 'UA', '+380', 9, null, 'UAH', 'UAH', 2],
            ['Turquie', 'TR', '+90', 10, null, 'TRY', 'TRY', 2],
            ['Serbie', 'RS', '+381', 9, null, 'RSD', 'RSD', 2],
            ['Liechtenstein', 'LI', '+423', 7, null, 'CHF', 'CHF', 2],

            // --- Amérique du Nord ---
            ['États-Unis', 'US', '+1', 10, null, 'USD', '$', 2],
            ['Canada', 'CA', '+1', 10, null, 'CAD', 'CAD', 2],
            ['Mexique', 'MX', '+52', 10, null, 'MXN', 'MXN', 2],

            // --- Amérique centrale / Caraïbes ---
            ['Haïti', 'HT', '+509', 8, null, 'HTG', 'HTG', 2],
            ['République dominicaine', 'DO', '+1', 10, null, 'DOP', 'DOP', 2],
            ['Cuba', 'CU', '+53', 8, null, 'CUP', 'CUP', 2],
            ['Guatemala', 'GT', '+502', 8, null, 'GTQ', 'GTQ', 2],
            ['Honduras', 'HN', '+504', 8, null, 'HNL', 'HNL', 2],
            ['El Salvador', 'SV', '+503', 8, null, 'USD', '$', 2],
            ['Nicaragua', 'NI', '+505', 8, null, 'NIO', 'NIO', 2],
            ['Costa Rica', 'CR', '+506', 8, null, 'CRC', 'CRC', 2],
            ['Panama', 'PA', '+507', 8, null, 'PAB', 'PAB', 2],
            ['Jamaïque', 'JM', '+1', 10, null, 'JMD', 'JMD', 2],

            // --- Amérique du Sud ---
            ['Brésil', 'BR', '+55', 11, null, 'BRL', 'BRL', 2],
            ['Argentine', 'AR', '+54', 10, null, 'ARS', 'ARS', 2],
            ['Chili', 'CL', '+56', 9, null, 'CLP', 'CLP', 0],
            ['Colombie', 'CO', '+57', 10, null, 'COP', 'COP', 2],
            ['Pérou', 'PE', '+51', 9, null, 'PEN', 'PEN', 2],
            ['Venezuela', 'VE', '+58', 10, null, 'VES', 'VES', 2],
            ['Équateur', 'EC', '+593', 9, null, 'USD', '$', 2],
            ['Bolivie', 'BO', '+591', 8, null, 'BOB', 'BOB', 2],
            ['Paraguay', 'PY', '+595', 9, null, 'PYG', 'PYG', 0],
            ['Uruguay', 'UY', '+598', 8, null, 'UYU', 'UYU', 2],
            ['Guyana', 'GY', '+592', 7, null, 'GYD', 'GYD', 2],
            ['Suriname', 'SR', '+597', 7, null, 'SRD', 'SRD', 2],

            // --- Moyen-Orient ---
            ['Arabie saoudite', 'SA', '+966', 9, null, 'SAR', 'SAR', 2],
            ['Émirats arabes unis', 'AE', '+971', 9, null, 'AED', 'AED', 2],
            ['Qatar', 'QA', '+974', 8, null, 'QAR', 'QAR', 2],
            ['Koweït', 'KW', '+965', 8, null, 'KWD', 'KWD', 3],
            ['Bahreïn', 'BH', '+973', 8, null, 'BHD', 'BHD', 3],
            ['Oman', 'OM', '+968', 8, null, 'OMR', 'OMR', 3],
            ['Jordanie', 'JO', '+962', 9, null, 'JOD', 'JOD', 3],
            ['Liban', 'LB', '+961', 8, null, 'LBP', 'LBP', 2],
            ['Israël', 'IL', '+972', 9, null, 'ILS', 'ILS', 2],
            ['Palestine', 'PS', '+970', 9, null, 'ILS', 'ILS', 2],
            ['Irak', 'IQ', '+964', 10, null, 'IQD', 'IQD', 3],
            ['Iran', 'IR', '+98', 10, null, 'IRR', 'IRR', 2],
            ['Yémen', 'YE', '+967', 9, null, 'YER', 'YER', 2],
            ['Syrie', 'SY', '+963', 9, null, 'SYP', 'SYP', 2],

            // --- Asie ---
            ['Chine', 'CN', '+86', 11, null, 'CNY', 'CNY', 2],
            ['Inde', 'IN', '+91', 10, null, 'INR', 'INR', 2],
            ['Pakistan', 'PK', '+92', 10, null, 'PKR', 'PKR', 2],
            ['Bangladesh', 'BD', '+880', 10, null, 'BDT', 'BDT', 2],
            ['Indonésie', 'ID', '+62', 10, null, 'IDR', 'IDR', 0],
            ['Japon', 'JP', '+81', 10, null, 'JPY', '¥', 0],
            ['Corée du Sud', 'KR', '+82', 10, null, 'KRW', 'KRW', 0],
            ['Corée du Nord', 'KP', '+850', 10, null, 'KPW', 'KPW', 2],
            ['Vietnam', 'VN', '+84', 9, null, 'VND', 'VND', 0],
            ['Thaïlande', 'TH', '+66', 9, null, 'THB', 'THB', 2],
            ['Philippines', 'PH', '+63', 10, null, 'PHP', 'PHP', 2],
            ['Malaisie', 'MY', '+60', 9, null, 'MYR', 'MYR', 2],
            ['Singapour', 'SG', '+65', 8, null, 'SGD', 'SGD', 2],
            ['Myanmar', 'MM', '+95', 9, null, 'MMK', 'MMK', 2],
            ['Cambodge', 'KH', '+855', 9, null, 'KHR', 'KHR', 2],
            ['Laos', 'LA', '+856', 9, null, 'LAK', 'LAK', 2],
            ['Népal', 'NP', '+977', 10, null, 'NPR', 'NPR', 2],
            ['Sri Lanka', 'LK', '+94', 9, null, 'LKR', 'LKR', 2],
            ['Afghanistan', 'AF', '+93', 9, null, 'AFN', 'AFN', 2],
            ['Kazakhstan', 'KZ', '+7', 10, null, 'KZT', 'KZT', 2],
            ['Ouzbékistan', 'UZ', '+998', 9, null, 'UZS', 'UZS', 2],
            ['Taïwan', 'TW', '+886', 9, null, 'TWD', 'TWD', 2],
            ['Mongolie', 'MN', '+976', 8, null, 'MNT', 'MNT', 2],
            ['Bhoutan', 'BT', '+975', 8, null, 'BTN', 'BTN', 2],
            ['Brunei', 'BN', '+673', 7, null, 'BND', 'BND', 2],
            ['Timor oriental', 'TL', '+670', 8, null, 'USD', '$', 2],
            ['Maldives', 'MV', '+960', 7, null, 'MVR', 'MVR', 2],
            ['Géorgie', 'GE', '+995', 9, null, 'GEL', 'GEL', 2],
            ['Arménie', 'AM', '+374', 8, null, 'AMD', 'AMD', 2],
            ['Azerbaïdjan', 'AZ', '+994', 9, null, 'AZN', 'AZN', 2],

            // --- Océanie ---
            ['Australie', 'AU', '+61', 9, null, 'AUD', 'AUD', 2],
            ['Nouvelle-Zélande', 'NZ', '+64', 9, null, 'NZD', 'NZD', 2],
            ['Papouasie-Nouvelle-Guinée', 'PG', '+675', 8, null, 'PGK', 'PGK', 2],
            ['Fidji', 'FJ', '+679', 7, null, 'FJD', 'FJD', 2],
            ['Îles Salomon', 'SB', '+677', 7, null, 'SBD', 'SBD', 2],
            ['Vanuatu', 'VU', '+678', 7, null, 'VUV', 'VUV', 0],
            ['Samoa', 'WS', '+685', 7, null, 'WST', 'WST', 2],
            ['Tonga', 'TO', '+676', 7, null, 'TOP', 'TOP', 2],
            ['Kiribati', 'KI', '+686', 8, null, 'AUD', 'AUD', 2],
            ['Tuvalu', 'TV', '+688', 6, null, 'AUD', 'AUD', 2],
            ['Nauru', 'NR', '+674', 7, null, 'AUD', 'AUD', 2],
            ['Palaos', 'PW', '+680', 7, null, 'USD', '$', 2],
            ['Îles Marshall', 'MH', '+692', 7, null, 'USD', '$', 2],
            ['Micronésie', 'FM', '+691', 7, null, 'USD', '$', 2],

            // --- Europe (complement) ---
            ['Islande', 'IS', '+354', 7, null, 'ISK', 'ISK', 0],
            ['Malte', 'MT', '+356', 8, null, 'EUR', '€', 2],
            ['Chypre', 'CY', '+357', 8, null, 'EUR', '€', 2],
            ['Andorre', 'AD', '+376', 6, null, 'EUR', '€', 2],
            ['Monaco', 'MC', '+377', 8, null, 'EUR', '€', 2],
            ['Saint-Marin', 'SM', '+378', 10, null, 'EUR', '€', 2],
            ['Vatican', 'VA', '+379', 10, null, 'EUR', '€', 2],
            ['Bulgarie', 'BG', '+359', 9, null, 'BGN', 'BGN', 2],
            ['Tchéquie', 'CZ', '+420', 9, null, 'CZK', 'CZK', 2],
            ['Slovaquie', 'SK', '+421', 9, null, 'EUR', '€', 2],
            ['Hongrie', 'HU', '+36', 9, null, 'HUF', 'HUF', 0],
            ['Slovénie', 'SI', '+386', 8, null, 'EUR', '€', 2],
            ['Croatie', 'HR', '+385', 9, null, 'EUR', '€', 2],
            ['Bosnie-Herzégovine', 'BA', '+387', 8, null, 'BAM', 'BAM', 2],
            ['Monténégro', 'ME', '+382', 8, null, 'EUR', '€', 2],
            ['Macédoine du Nord', 'MK', '+389', 8, null, 'MKD', 'MKD', 2],
            ['Albanie', 'AL', '+355', 9, null, 'ALL', 'ALL', 2],
            ['Kosovo', 'XK', '+383', 8, null, 'EUR', '€', 2],
            ['Moldavie', 'MD', '+373', 8, null, 'MDL', 'MDL', 2],
            ['Biélorussie', 'BY', '+375', 9, null, 'BYN', 'BYN', 2],
            ['Lituanie', 'LT', '+370', 8, null, 'EUR', '€', 2],
            ['Lettonie', 'LV', '+371', 8, null, 'EUR', '€', 2],
            ['Estonie', 'EE', '+372', 8, null, 'EUR', '€', 2],

            // --- Asie centrale (complement) ---
            ['Kirghizistan', 'KG', '+996', 9, null, 'KGS', 'KGS', 2],
            ['Tadjikistan', 'TJ', '+992', 9, null, 'TJS', 'TJS', 2],
            ['Turkménistan', 'TM', '+993', 8, null, 'TMT', 'TMT', 2],

            // --- Caraïbes (complement) ---
            ['Trinité-et-Tobago', 'TT', '+1', 10, null, 'TTD', 'TTD', 2],
            ['Bahamas', 'BS', '+1', 10, null, 'BSD', 'BSD', 2],
            ['Barbade', 'BB', '+1', 10, null, 'BBD', 'BBD', 2],
            ['Sainte-Lucie', 'LC', '+1', 10, null, 'XCD', 'XCD', 2],
            ['Grenade', 'GD', '+1', 10, null, 'XCD', 'XCD', 2],
            ['Saint-Vincent-et-les-Grenadines', 'VC', '+1', 10, null, 'XCD', 'XCD', 2],
            ['Antigua-et-Barbuda', 'AG', '+1', 10, null, 'XCD', 'XCD', 2],
            ['Dominique', 'DM', '+1', 10, null, 'XCD', 'XCD', 2],
            ['Saint-Christophe-et-Niévès', 'KN', '+1', 10, null, 'XCD', 'XCD', 2],
            ['Belize', 'BZ', '+501', 7, null, 'BZD', 'BZD', 2],
        ];

        $existing = DB::table('pays')->pluck('code_iso')->all();
        $now = now();

        $rows = collect($pays)
            ->reject(fn ($p) => in_array($p[1], $existing, true))
            ->unique(fn ($p) => $p[1]) // ecarte le doublon LI/CH (Liechtenstein reutilise volontairement le franc suisse)
            ->map(fn ($p) => [
                'nom' => $p[0],
                'code_iso' => $p[1],
                'indicatif_telephone' => $p[2],
                'telephone_longueur' => $p[3],
                'telephone_premier_chiffre_min' => $p[4],
                'devise_code' => $p[5],
                'devise_symbole' => $p[6],
                'devise_decimales' => $p[7],
                'actif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('pays')->insert($chunk);
        }
    }

    public function down(): void
    {
        // Ne supprime pas : une ecole a pu entre-temps etre rattachee a l'un
        // de ces pays, et down() n'a pas a decider quoi en faire.
    }
};
