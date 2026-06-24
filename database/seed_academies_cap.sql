-- ============================================================
-- Seed complet : 26 Académies d'Enseignement + 125 CAP du Mali
-- Décret n°2019-0230/P-RM du 19 mars 2019
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE `cap`;
TRUNCATE TABLE `academie`;

-- ============================================================
-- 26 ACADÉMIES D'ENSEIGNEMENT
-- ============================================================
INSERT INTO `academie` (`id_academie`, `nom_academie`, `code_academie`, `localite_academie`) VALUES
(1,  'AE de Kayes',              'AE-KAYES',       'Kayes'),
(2,  'AE de Kéniéba',            'AE-KENIEBA',     'Kéniéba'),
(3,  'AE de Kita',               'AE-KITA',        'Kita'),
(4,  'AE de Nioro',              'AE-NIORO',       'Nioro'),
(5,  'AE de Koulikoro',          'AE-KOULIKORO',   'Koulikoro'),
(6,  'AE de Kati',               'AE-KATI',        'Kati'),
(7,  'AE de Kalabancoro',        'AE-KALABANCORO', 'Kalabancoro'),
(8,  'AE de Nara',               'AE-NARA',        'Nara'),
(9,  'AE de Dioïla',             'AE-DIOILA',      'Dioïla'),
(10, 'AE de Sikasso',            'AE-SIKASSO',     'Sikasso'),
(11, 'AE de Bougouni',           'AE-BOUGOUNI',    'Bougouni'),
(12, 'AE de Koutiala',           'AE-KOUTIALA',    'Koutiala'),
(13, 'AE de Ségou',              'AE-SEGOU',       'Ségou'),
(14, 'AE de San',                'AE-SAN',         'San'),
(15, 'AE de Mopti',              'AE-MOPTI',       'Mopti'),
(16, 'AE de Ténenkou',           'AE-TENENKOU',    'Ténenkou'),
(17, 'AE de Douentza',           'AE-DOUENTZA',    'Douentza'),
(18, 'AE de Bandiagara',         'AE-BANDIAGARA',  'Bandiagara'),
(19, 'AE de Tombouctou',         'AE-TOMBOUCTOU',  'Tombouctou'),
(20, 'AE de Gourma-Rharous',     'AE-GOURMA-RH',   'Gourma-Rharous'),
(21, 'AE de Gao',                'AE-GAO',         'Gao'),
(22, 'AE de Kidal',              'AE-KIDAL',       'Kidal'),
(23, 'AE de Taoudénit',          'AE-TAOUDENIT',   'Taoudénit'),
(24, 'AE de Ménaka',             'AE-MENAKA',      'Ménaka'),
(25, 'AE de Bamako Rive-Gauche', 'AE-BKO-RG',      'Bamako'),
(26, 'AE de Bamako Rive-Droite', 'AE-BKO-RD',      'Bamako');

-- ============================================================
-- 125 CENTRES D'ANIMATION PÉDAGOGIQUE
-- ============================================================
INSERT INTO `cap` (`id_cap`, `nom_cap`, `code_cap`, `localite_cap`, `id_academie`) VALUES

-- AE 1 : Kayes (4 CAP)
(1,  'CAP de Kayes Rives-Droite',    'CAP-KYS-RD',      'Kayes',             1),
(2,  'CAP de Kayes Rives-Gauche',    'CAP-KYS-RG',      'Kayes',             1),
(3,  'CAP d\'Ambidedi',              'CAP-AMBIDEDI',     'Ambidedi',          1),
(4,  'CAP de Yélimané',              'CAP-YELIMANE',     'Yélimané',          1),

-- AE 2 : Kéniéba (2 CAP)
(5,  'CAP de Kéniéba',               'CAP-KENIEBA',      'Kéniéba',           2),
(6,  'CAP de Faléa',                 'CAP-FALEA',        'Faléa',             2),

-- AE 3 : Kita (6 CAP)
(7,  'CAP de Kita',                  'CAP-KITA',         'Kita',              3),
(8,  'CAP de Sébékoro',              'CAP-SEBEKORO',     'Sébékoro',          3),
(9,  'CAP de Toukoto',               'CAP-TOUKOTO',      'Toukoto',           3),
(10, 'CAP de Bafoulabé',             'CAP-BAFOULABE',    'Bafoulabé',         3),
(11, 'CAP de Oussoubidiagna',        'CAP-OUSSOUBIDIA',  'Oussoubidiagna',    3),
(12, 'CAP de Sagabari',              'CAP-SAGABARI',     'Sagabari',          3),

-- AE 4 : Nioro (3 CAP)
(13, 'CAP de Nioro',                 'CAP-NIORO',        'Nioro',             4),
(14, 'CAP de Diéma',                 'CAP-DIEMA',        'Diéma',             4),
(15, 'CAP de Sandaré',               'CAP-SANDARE',      'Sandaré',           4),

-- AE 5 : Koulikoro (2 CAP)
(16, 'CAP de Koulikoro',             'CAP-KOULIKORO',    'Koulikoro',         5),
(17, 'CAP de Banamba',               'CAP-BANAMBA',      'Banamba',           5),

-- AE 6 : Kati (6 CAP)
(18, 'CAP de Kati',                  'CAP-KATI',         'Kati',              6),
(19, 'CAP de Kolokani',              'CAP-KOLOKANI',     'Kolokani',          6),
(20, 'CAP de Nonsombougou',          'CAP-NONSOMBOUGOU', 'Nonsombougou',      6),
(21, 'CAP de Kangaba',               'CAP-KANGABA',      'Kangaba',           6),
(22, 'CAP de Sangarébougou',         'CAP-SANGAREBOUGOU','Sangarébougou',     6),
(23, 'CAP de Siby',                  'CAP-SIBY',         'Siby',              6),

-- AE 7 : Kalabancoro (5 CAP)
(24, 'CAP de Kalabancoro',           'CAP-KALABANCORO',  'Kalabancoro',       7),
(25, 'CAP de Baguineda',             'CAP-BAGUINEDA',    'Baguineda',         7),
(26, 'CAP de Ouelessebougou',        'CAP-OUELESSEBOUGOU','Ouelessebougou',   7),
(27, 'CAP de Sirakoro Méguetana',    'CAP-SIRAKORO-MEG', 'Sirakoro Méguetana',7),
(28, 'CAP de Ouenzindougou',         'CAP-OUENZINDOUGOU','Ouenzindougou',     7),

-- AE 8 : Nara (2 CAP)
(29, 'CAP de Nara',                  'CAP-NARA',         'Nara',              8),
(30, 'CAP de Ballé',                 'CAP-BALLE',        'Ballé',             8),

-- AE 9 : Dioïla (4 CAP)
(31, 'CAP de Dioïla',                'CAP-DIOILA',       'Dioïla',            9),
(32, 'CAP de Béléko',                'CAP-BELEKO',       'Béléko',            9),
(33, 'CAP de Fana',                  'CAP-FANA',         'Fana',              9),
(34, 'CAP de Massigui',              'CAP-MASSIGUI',     'Massigui',          9),

-- AE 10 : Sikasso (7 CAP)
(35, 'CAP de Sikasso',               'CAP-SIKASSO',      'Sikasso',          10),
(36, 'CAP de Kadiolo',               'CAP-KADIOLO',      'Kadiolo',          10),
(37, 'CAP de N\'Kourala',            'CAP-NKOURALA',     'N\'Kourala',       10),
(38, 'CAP de Kléla',                 'CAP-KLELA',        'Kléla',            10),
(39, 'CAP de Niéna',                 'CAP-NIENA',        'Niéna',            10),
(40, 'CAP de Kignan',                'CAP-KIGNAN',       'Kignan',           10),
(41, 'CAP de Fourou',                'CAP-FOUROU',       'Fourou',           10),

-- AE 11 : Bougouni (6 CAP)
(42, 'CAP de Bougouni',              'CAP-BOUGOUNI',     'Bougouni',         11),
(43, 'CAP de Koumantou',             'CAP-KOUMANTOU',    'Koumantou',        11),
(44, 'CAP de Kolondiéba',            'CAP-KOLONDIEBA',   'Kolondiéba',       11),
(45, 'CAP de Garalo',                'CAP-GARALO',       'Garalo',           11),
(46, 'CAP de Yanfolila',             'CAP-YANFOLILA',    'Yanfolila',        11),
(47, 'CAP de Selingué',              'CAP-SELINGUE',     'Selingué',         11),

-- AE 12 : Koutiala (5 CAP)
(48, 'CAP de Koutiala',              'CAP-KOUTIALA',     'Koutiala',         12),
(49, 'CAP de M\'Pessoba',            'CAP-MPESSOBA',     'M\'Pessoba',       12),
(50, 'CAP de Zangasso',              'CAP-ZANGASSO',     'Zangasso',         12),
(51, 'CAP de Yorosso',               'CAP-YOROSSO',      'Yorosso',          12),
(52, 'CAP de Koury',                 'CAP-KOURY',        'Koury',            12),

-- AE 13 : Ségou (8 CAP)
(53, 'CAP de Ségou',                 'CAP-SEGOU',        'Ségou',            13),
(54, 'CAP de Markala',               'CAP-MARKALA',      'Markala',          13),
(55, 'CAP de Farako',                'CAP-FARAKO',       'Farako',           13),
(56, 'CAP de Baraouéli',             'CAP-BARAOULI',     'Baraouéli',        13),
(57, 'CAP de Macina',                'CAP-MACINA',       'Macina',           13),
(58, 'CAP de Niono',                 'CAP-NIONO',        'Niono',            13),
(59, 'CAP de Sarro',                 'CAP-SARRO',        'Sarro',            13),
(60, 'CAP de Sanando',               'CAP-SANANDO',      'Sanando',          13),

-- AE 14 : San (6 CAP)
(61, 'CAP de San',                   'CAP-SAN',          'San',              14),
(62, 'CAP de Bla',                   'CAP-BLA',          'Bla',              14),
(63, 'CAP de Yangasso',              'CAP-YANGASSO',     'Yangasso',         14),
(64, 'CAP de Kimparana',             'CAP-KIMPARANA',    'Kimparana',        14),
(65, 'CAP de Tominian',              'CAP-TOMINIAN',     'Tominian',         14),
(66, 'CAP de Fangasso',              'CAP-FANGASSO',     'Fangasso',         14),

-- AE 15 : Mopti (4 CAP)
(67, 'CAP de Mopti',                 'CAP-MOPTI',        'Mopti',            15),
(68, 'CAP de Djenné',                'CAP-DJENNE',       'Djenné',           15),
(69, 'CAP de Sofara',                'CAP-SOFARA',       'Sofara',           15),
(70, 'CAP de Konna',                 'CAP-KONNA',        'Konna',            15),

-- AE 16 : Ténenkou (3 CAP)
(71, 'CAP de Ténenkou',              'CAP-TENENKOU',     'Ténenkou',         16),
(72, 'CAP de Youwarou',              'CAP-YOUWAROU',     'Youwarou',         16),
(73, 'CAP de Toguéré-Coumbé',        'CAP-TOGUERE-C',   'Toguéré-Coumbé',   16),

-- AE 17 : Douentza (5 CAP)
(74, 'CAP de Douentza',              'CAP-DOUENTZA',     'Douentza',         17),
(75, 'CAP de Dioungani',             'CAP-DIOUNGANI',    'Dioungani',        17),
(76, 'CAP de Koro',                  'CAP-KORO',         'Koro',             17),
(77, 'CAP de Madougou',              'CAP-MADOUGOU',     'Madougou',         17),
(78, 'CAP de Boni',                  'CAP-BONI',         'Boni',             17),

-- AE 18 : Bandiagara (5 CAP)
(79, 'CAP de Bandiagara',            'CAP-BANDIAGARA',   'Bandiagara',       18),
(80, 'CAP de Bankass',               'CAP-BANKASS',      'Bankass',          18),
(81, 'CAP de Sokoura',               'CAP-SOKOURA',      'Sokoura',          18),
(82, 'CAP de Sangha',                'CAP-SANGHA',       'Sangha',           18),
(83, 'CAP de Kendié',                'CAP-KENDIE',       'Kendié',           18),

-- AE 19 : Tombouctou (6 CAP)
(84, 'CAP de Tombouctou',            'CAP-TOMBOUCTOU',   'Tombouctou',       19),
(85, 'CAP de Goundam',               'CAP-GOUNDAM',      'Goundam',          19),
(86, 'CAP de Diré',                  'CAP-DIRE',         'Diré',             19),
(87, 'CAP de Niafunké',              'CAP-NIAFUNKE',     'Niafunké',         19),
(88, 'CAP de Léré',                  'CAP-LERE',         'Léré',             19),
(89, 'CAP de Tonka',                 'CAP-TONKA',        'Tonka',            19),

-- AE 20 : Gourma-Rharous (2 CAP)
(90, 'CAP de Gourma-Rharous',        'CAP-GOURMA-RH',    'Gourma-Rharous',   20),
(91, 'CAP de Gossi',                 'CAP-GOSSI',        'Gossi',            20),

-- AE 21 : Gao (5 CAP)
(92, 'CAP de Gao',                   'CAP-GAO',          'Gao',              21),
(93, 'CAP d\'Ansongo',               'CAP-ANSONGO',      'Ansongo',          21),
(94, 'CAP de Bourem',                'CAP-BOUREM',       'Bourem',           21),
(95, 'CAP de Haoussa-Foulane',       'CAP-HAOUSSA-F',    'Haoussa-Foulane',  21),
(96, 'CAP d\'Almoustrat',            'CAP-ALMOUSTRAT',   'Almoustrat',       21),

-- AE 22 : Kidal (4 CAP)
(97,  'CAP de Kidal',                'CAP-KIDAL',        'Kidal',            22),
(98,  'CAP d\'Abeibara',             'CAP-ABEIBARA',     'Abeibara',         22),
(99,  'CAP de Tessalit',             'CAP-TESSALIT',     'Tessalit',         22),
(100, 'CAP de Tinassako',            'CAP-TINASSAKO',    'Tinassako',        22),

-- AE 23 : Taoudénit (6 CAP)
(101, 'CAP d\'Araouane',             'CAP-ARAOUANE',     'Araouane',         23),
(102, 'CAP de Taoudénit',            'CAP-TAOUDENIT',    'Taoudénit',        23),
(103, 'CAP de Fouma-Elba',           'CAP-FOUMA-ELBA',   'Fouma-Elba',       23),
(104, 'CAP d\'Achouratt',            'CAP-ACHOURATT',    'Achouratt',        23),
(105, 'CAP d\'Al-Ourche',            'CAP-AL-OURCHE',    'Al-Ourche',        23),
(106, 'CAP de Boû-Djebéha',          'CAP-BOU-DJEBEH',   'Boû-Djebéha',      23),

-- AE 24 : Ménaka (4 CAP)
(107, 'CAP de Ménaka',               'CAP-MENAKA',       'Ménaka',           24),
(108, 'CAP d\'Andéramboukane',       'CAP-ANDERAMBOUAK', 'Andéramboukane',   24),
(109, 'CAP d\'Inékar',               'CAP-INEKAR',       'Inékar',           24),
(110, 'CAP de Tidermène',            'CAP-TIDERMENE',    'Tidermène',        24),

-- AE 25 : Bamako Rive-Gauche (8 CAP)
(111, 'CAP de Djélibougou',          'CAP-DJELIBOUGOU',  'Djélibougou',      25),
(112, 'CAP de Banconi',              'CAP-BANCONI',      'Banconi',          25),
(113, 'CAP de Bamako-Coura',         'CAP-BKO-COURA',    'Bamako-Coura',     25),
(114, 'CAP de Bozola',               'CAP-BOZOLA',       'Bozola',           25),
(115, 'CAP de l\'Hippodrome',        'CAP-HIPPODROME',   'Hippodrome',       25),
(116, 'CAP du Centre Commercial',    'CAP-CENTRE-COM',   'Centre Commercial', 25),
(117, 'CAP de Lafiabougou',          'CAP-LAFIABOUGOU',  'Lafiabougou',      25),
(118, 'CAP de Sébénikoro',           'CAP-SEBENIKORO',   'Sébénikoro',       25),

-- AE 26 : Bamako Rive-Droite (7 CAP)
(119, 'CAP de Torokorobougou',       'CAP-TOROKOROBG',   'Torokorobougou',   26),
(120, 'CAP de Banankabougou',        'CAP-BANANKABOUGOU','Banankabougou',    26),
(121, 'CAP de Baco Djicoroni',       'CAP-BACO-DJIC',    'Baco Djicoroni',   26),
(122, 'CAP de Kalabancoura',         'CAP-KALABANCOURA', 'Kalabancoura',     26),
(123, 'CAP de Faladiè',              'CAP-FALADIE',      'Faladiè',          26),
(124, 'CAP de Sogoniko',             'CAP-SOGONIKO',     'Sogoniko',         26),
(125, 'CAP de Sénou',                'CAP-SENOU',        'Sénou',            26);

ALTER TABLE `academie` AUTO_INCREMENT = 27;
ALTER TABLE `cap` AUTO_INCREMENT = 126;

SET FOREIGN_KEY_CHECKS = 1;
