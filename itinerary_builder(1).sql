-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 04, 2025 at 02:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `itinerary_builder`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `name`, `email`, `phone`, `notes`, `password`, `role`, `created_at`) VALUES
(9, 5, 'Bente', 'bente@mail.dk', '2323', '2 gæster', NULL, 'customer', '2025-07-13 02:40:45'),
(15, 5, 'Ib Ibsen', 'ib@mail.dk', '12211221', '5 gæster', '$2y$10$7X.lVdr.Wl4kDfJiGSvYPOk4Bv5qO42SYi.BSoWkhptq5amOLOHOa', 'customer', '2025-07-13 02:41:47'),
(16, 5, 'Dorte Øby', 'dorte@mail.com', '12312', '3 gæster', '$2y$10$7ZKQd1KwPNjybsvnr0/aoeZKUcOKyNupAlFcrAsPZvFDUC4dxlCSC', 'customer', '2025-07-17 17:05:57'),
(17, 5, 'Ole', 'ole@test.dk', '74725566', '8 gæster', '$2y$10$MKf3DV3jiBWShxcRTJXFRe/tN0YJNtDj9Ua5OUMRorhCRHniLEjeq', 'customer', '2025-07-22 05:56:41');

-- --------------------------------------------------------

--
-- Table structure for table `customer_files`
--

CREATE TABLE `customer_files` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_files`
--

INSERT INTO `customer_files` (`id`, `customer_id`, `file_name`, `file_path`, `uploaded_at`) VALUES
(1, 15, 'Velkommen til Vietnam - Vietnam Rejser.pdf', 'uploads/customer_files/1753067002_Velkommen_til_Vietnam_-_Vietnam_Rejser.pdf', '2025-07-21 03:03:22'),
(2, 15, 'Vigtigt information inden din rejse.pdf', 'uploads/customer_files/1753067002_Vigtigt_information_inden_din_rejse.pdf', '2025-07-21 03:03:22');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `itineraries`
--

CREATE TABLE `itineraries` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `public_token` varchar(64) DEFAULT NULL,
  `flight` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `price_child` int(11) DEFAULT 0,
  `num_adults` int(11) DEFAULT 2,
  `num_children` int(11) DEFAULT 0,
  `included` text DEFAULT NULL,
  `not_included` text DEFAULT NULL,
  `start_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itineraries`
--

INSERT INTO `itineraries` (`id`, `customer_id`, `title`, `created_at`, `public_token`, `flight`, `price`, `price_child`, `num_adults`, `num_children`, `included`, `not_included`, `start_date`) VALUES
(7, 16, 'Klassiske Vietnam 2026', '2025-07-21 21:24:42', '7af552d57e0c5069840c7620ccaa2287fe1fb7e44da6a0dac9cb6916c16b44a2', NULL, NULL, 0, 2, 0, NULL, NULL, NULL),
(8, 9, 'Klassiske Vietnam 2025', '2025-07-21 21:41:52', '9780a69923a3badbeed7194175068fc734477a2d5467eea3ee2b3642466e809a', '<p>VN 038 E 29JAN &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;København – Ho Chi Minh &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;kl. &nbsp; &nbsp; &nbsp;1050 0430+1</p><p>&nbsp; &nbsp;</p><p>VN 039 T 13FEB &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;Ho Chi Minh – København &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;kl. &nbsp; &nbsp; &nbsp;2245 0600+1</p>', 1995, 1295, 2, 1, NULL, NULL, NULL),
(9, 17, 'Vinter get away', '2025-07-22 13:01:15', '9abc8a461065d61a4bb3a3cbb6dc14ac3cb3e7ecd23f94d6ea19324aac0f1680', NULL, NULL, 0, 2, 0, NULL, NULL, NULL),
(10, 9, 'Ny rejse', '2025-07-25 17:19:43', 'f3d624007a36186b4cbacc4ba4d5affe8f02c831ad75e0d58dca52f01d133c66', '<p>VN 038 E 29JAN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; København – Ho Chi Minh&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; kl.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 1050 0430+1</p><p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</p><p>VN 039 T 13FEB&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Ho Chi Minh – København&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; kl.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 2245 0600+1</p>', 1995, 0, 2, 0, '<ul><li>Alle overnatninger med morgenmad</li><li>Alle transfer til/fra hotel/lufthavne</li><li>Indenrigsfly (max 23 kg - flere kg kan tilkøbes)</li><li>Alle nævnte ture med engelsktalende guide</li><li>Alle ture med privat guide (undtaget Halong Bay)</li><li>Du får et simkort med på rejsen, så du 24/7 kan komme i kontakt med vores danske og vietnamesiske medarbejdere på destinationen.</li><li>Infomøde ved ankomst til Hanoi</li><li>Måltider: B= Breakfast L= Lunch D= Dinner</li></ul>', '<ul><li>Fly til/fra Danmark/Vietnam</li><li>Rejseforsikring</li><li>Drikkepenge</li><li>Andet der ikke er nævnt under \"Turen inkluderer\"</li><li>Evt tillæg for jul/nytår</li></ul>', NULL),
(11, 9, 'Sommer idyl', '2025-07-26 15:52:22', '68548b4e8235c3995ccc04e9d4d9a8c44e3ec784246df5359bf75fc0b34fc727', '', 1265, 1100, 4, 2, '<ul><li>Alle overnatninger med morgenmad</li><li>Alle transfer til/fra hotel/lufthavne</li><li>Indenrigsfly (max 23 kg - flere kg kan tilkøbes)</li><li>Alle nævnte ture med engelsktalende guide</li><li>Du får et simkort med på rejsen, så du 24/7 kan komme i kontakt med vores danske og vietnamesiske medarbejdere på destinationen.</li><li>Infomøde ved ankomst til Hanoi</li><li>Måltider: B= Breakfast L= Lunch D= Dinner</li></ul>', '<ul><li>Fly til/fra Danmark/Vietnam</li><li>Rejseforsikring</li><li>Drikkepenge</li><li>Andet der ikke er nævnt under \"Turen inkluderer\"</li><li>Evt tillæg for jul/nytår</li></ul>', '2025-05-20');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_days`
--

CREATE TABLE `itinerary_days` (
  `id` int(11) NOT NULL,
  `itinerary_id` int(11) DEFAULT NULL,
  `day_range` varchar(20) DEFAULT NULL,
  `day_title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `overnight` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `meals` varchar(255) DEFAULT NULL,
  `calendar_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itinerary_days`
--

INSERT INTO `itinerary_days` (`id`, `itinerary_id`, `day_range`, `day_title`, `description`, `overnight`, `sort_order`, `meals`, `calendar_date`) VALUES
(8, 7, '3-4', 'Halong Bay (Bai Tu Long Bay) 2 dage - Signature Cruise - (dag 1 af 2)', '<p><strong>08:00-08:30:&nbsp;</strong>Du hentes på dit hotel i Hanoi Old Quarter.</p><p><strong>11:30:</strong> Du ankommer til Signature check-in lounge-</p><p><strong>12:00: CHECK-IN</strong><br>Du bydes velkommen af bådens personale og gør klar til at gå ombord. Personalet tager sig af din bagage, og sørger for at den kommer med ombord.&nbsp;</p><p><strong>12:45: OMBORD</strong><br>Du transporteres til Signature Cruise af en mindre båd. Cruise Manageren og resten af personalet byder dig velkommen ombord. Sikkerhedsinstruks og dagens program gennemgås mens du nyder din velkomstdrink.</p><p><strong>13:00: FROKOST</strong><br>Frokost serveres i restauranten, mens du stille sejler ud mod Bai Tu Long Bay. En mindre turistet bugt end den populære Halong Bay. Nyd det flotte landskab.</p><p><strong>14:45: AKTIVITETER</strong><br>Første stop er den flydende fiskerlandsby Vung Vieng Floating fishing village, der også er kendt for dyrkning af perler. Du har mulighed for at låne en kajak og påegen hånd selje ud i landsbyen. Er du ikke til kajak, kan du for 50.000 VND/person blive sejlet ud i en bambusbåd af en lokal.</p><p><strong>16:00: TID TIL AFSLAPNING</strong><br>Tilbage på båden har du mulighed for at slappe af, nyde en kop kaffe eller en drink (for egen regning). Du kan også få en omgang massage - det skal bookes på forhånd..</p><p><strong>18:00 - 19:00: HAPPY HOUR</strong><br>Mens solen går ned, har du mulighed for at nyde en drink, når der er “Happy hour” med “Buy 1 get 1 free”.</p><p><strong>18:30: SUNSET PARTY</strong><br>Køkkenchefen viser hvordan man laver vietnamesiske forårsruller. Du har mulighed for at deltage.</p><p><strong>19:00: AFTENSMAD</strong><br>Efter en lang dag er det tid til aftensmad, der består af en “Set-Menu” med inspiration fra både lokale og vestlige retter. Forvent en del seafood.</p><p><strong>20:30: AFTEN UNDERHOLDNING ELLER AFSLAPNING</strong><br>Du har aftenen til fri disposition. Baren er åben, så der er rig mulighed for at at slappe af med en eksotisk cocktail, en øl, et glas vin eller en kop kaffe - der er selvfølgelig også juice og smoothies på menuen. Du kan også booke en massage på værelset. Af andre aktiviteter kan nævnes squid ﬁshing, nyd en film eller slap af på dækket.</p>', 'Privat kahyt på båden', 0, 'Breakfast, Breakfast, Lunch, Dinner', NULL),
(9, 8, '1', 'Ankomst til Hanoi', '<p>Du ankommer til Hanoi, hvor du mødes af din chauffør, der i ankomsthallen står med et skilt med dit navn. Han kører dig til dit hotel i Hanoi. Du har resten af dagen fri i hovedstaden. Kommer du først eller midt på dagen kommer en af Vietnam Rejsers medarbejdere forbi og byder dig velkommen, holder et lille velkomstmøde, her får du lidt praktisk information og et SIM-kort. Så kan du altid kan komme i kontakt med os. (Kommer du sidst på dagen, ringer vi til dig via chaufføren, og aftaler at mødes næste dag).</p>', '<p><a href=\"https://shiningcentralhotel.com/\">Shining Central hotel - Executive room</a></p>', 1, '', NULL),
(10, 8, '2', 'Hanoi city tour', '<p><strong>Kl. 08.00:&nbsp;</strong>Du bliver hentet på dit hotel og taget med på en spædende tur rundt i Hanoi. Dagen starter med et besøg ved Ho Chi Minh-komplekset, hvor du ser hans mausoleum, hus på pæle og den smukke have.</p><p>Herefter besøger du En-søjle-pagode, og fortsætter til Vietnams Etnologiske Museum. Her lærer du om landets rige kulturelle mangfoldighed gennem udstillinger af kostumer, arbejdsredskaber, &nbsp;og boliger fra nogle af de 54 etniske grupper der lever i Vietnam.</p><p>Efterfølgende tager du til Hoan Kiem-sø og Ngoc Son-templet, der hylder general Tran Hung Dao.&nbsp;</p><p>Slap af og hvil mens du nyder en lækker frokost i Hanois gamle bydel.</p><p>Om eftermiddagen besøger du Litteraturtemplet og Quoc Tu Giam, Vietnams første universitet. Hoa Lo-fængslet, også kendt som \"Hanoi Hilton,\" som nu er et museum.&nbsp;</p><p>Er tiden til det besøger du også den imponerende keramik-mosaikvæg, inden dagen slutter med et besøg på den historiske Long Bien-bro fra 1899.</p><p>Du er tilbage på dit hotel ca. kl. 15.30</p><p><strong>BEMÆRK:&nbsp;</strong></p><ol><li><i>Mandag og fredag er Ho Chi Minh\'s Mausoleum lukket og kan kun ses udefra.&nbsp;</i></li><li><i>Mandag og fredag er Etnologiske Museum lukket og erstattes af keramiklandsbyen Bat Trang.</i></li><li><i>Shorts og korte kjoler/nederdel er ikke tilladt ved besøg i mausoleum og templer. Skuldre og knæ skal som udgangspunkt være dækket.</i></li><li><i>Rækkefølgen på ovenstående besøg kan være ændret.</i></li></ol>', '<p>Solare De Monte</p>', 2, 'Breakfast, Lunch', NULL),
(11, 8, '3-4', 'Halong Bay (Bai Tu Long Bay) 2 dage - Signature Cruise - (dag 1 af 2)', '<p><strong>08:00-08:30:&nbsp;</strong>Du hentes på dit hotel i Hanoi Old Quarter.</p><p><strong>11:30:</strong> Du ankommer til Signature check-in lounge-</p><p><strong>12:00: CHECK-IN</strong><br>Du bydes velkommen af bådens personale og gør klar til at gå ombord. Personalet tager sig af din bagage, og sørger for at den kommer med ombord.&nbsp;</p><p><strong>12:45: OMBORD</strong><br>Du transporteres til Signature Cruise af en mindre båd. Cruise Manageren og resten af personalet byder dig velkommen ombord. Sikkerhedsinstruks og dagens program gennemgås mens du nyder din velkomstdrink.</p><p><strong>13:00: FROKOST</strong><br>Frokost serveres i restauranten, mens du stille sejler ud mod Bai Tu Long Bay. En mindre turistet bugt end den populære Halong Bay. Nyd det flotte landskab.</p><p><strong>14:45: AKTIVITETER</strong><br>Første stop er den flydende fiskerlandsby Vung Vieng Floating fishing village, der også er kendt for dyrkning af perler. Du har mulighed for at låne en kajak og påegen hånd selje ud i landsbyen. Er du ikke til kajak, kan du for 50.000 VND/person blive sejlet ud i en bambusbåd af en lokal.</p><p><strong>16:00: TID TIL AFSLAPNING</strong><br>Tilbage på båden har du mulighed for at slappe af, nyde en kop kaffe eller en drink (for egen regning). Du kan også få en omgang massage - det skal bookes på forhånd..</p><p><strong>18:00 - 19:00: HAPPY HOUR</strong><br>Mens solen går ned, har du mulighed for at nyde en drink, når der er “Happy hour” med “Buy 1 get 1 free”.</p><p><strong>18:30: SUNSET PARTY</strong><br>Køkkenchefen viser hvordan man laver vietnamesiske forårsruller. Du har mulighed for at deltage.</p><p><strong>19:00: AFTENSMAD</strong><br>Efter en lang dag er det tid til aftensmad, der består af en “Set-Menu” med inspiration fra både lokale og vestlige retter. Forvent en del seafood.</p><p><strong>20:30: AFTEN UNDERHOLDNING ELLER AFSLAPNING</strong><br>Du har aftenen til fri disposition. Baren er åben, så der er rig mulighed for at at slappe af med en eksotisk cocktail, en øl, et glas vin eller en kop kaffe - der er selvfølgelig også juice og smoothies på menuen. Du kan også booke en massage på værelset. Af andre aktiviteter kan nævnes squid ﬁshing, nyd en film eller slap af på dækket.</p>', '<p>På båden i egen kahyt</p>', 3, 'Breakfast, Lunch, Dinner', NULL),
(12, 9, '1', 'Ankomst til Hanoi', 'Du ankommer til Hanoi, hvor du mødes af din chauffør, der i ankomsthallen står med et skilt med dit navn. Han kører dig til dit hotel i Hanoi. Du har resten af dagen fri i hovedstaden.\r\n\r\nKommer du først eller midt på dagen kommer en af Vietnam Rejsers medarbejdere forbi og byder dig velkommen, holder et lille velkomstmøde, her får du lidt praktisk information og et SIM-kort. Så kan du altid kan komme i kontakt med os. (Kommer du sidst på dagen, ringer vi til dig via chaufføren, og aftaler at mødes næste dag).', 'Solare De Monte', 0, 'No meals included', NULL),
(13, 10, '1', 'Ankomst til Hanoi', '<p>Du ankommer til Hanoi, hvor du mødes af din chauffør, der i ankomsthallen står med et skilt med dit navn. Han kører dig til dit hotel i Hanoi. Du har resten af dagen fri i hovedstaden. Kommer du først eller midt på dagen kommer en af Vietnam Rejsers medarbejdere forbi og byder dig velkommen, holder et lille velkomstmøde, her får du lidt praktisk information og et SIM-kort. Så kan du altid kan komme i kontakt med os. (Kommer du sidst på dagen, ringer vi til dig via chaufføren, og aftaler at mødes næste dag).</p>', '<p><a href=\"https://shiningcentralhotel.com/\">Shining Central hotel - Executive room</a></p>', 0, 'Ingen måltider inkluderet', NULL),
(14, 10, '2', 'Hanoi city tour', '<p><strong>Kl. 08.00:&nbsp;</strong>Du bliver hentet på dit hotel og taget med på en spædende tur rundt i Hanoi. Dagen starter med et besøg ved Ho Chi Minh-komplekset, hvor du ser hans mausoleum, hus på pæle og den smukke have.</p><p>Herefter besøger du En-søjle-pagode, og fortsætter til Vietnams Etnologiske Museum. Her lærer du om landets rige kulturelle mangfoldighed gennem udstillinger af kostumer, arbejdsredskaber, &nbsp;og boliger fra nogle af de 54 etniske grupper der lever i Vietnam.</p><p>Efterfølgende tager du til Hoan Kiem-sø og Ngoc Son-templet, der hylder general Tran Hung Dao.&nbsp;</p><p>Slap af og hvil mens du nyder en lækker frokost i Hanois gamle bydel.</p><p>Om eftermiddagen besøger du Litteraturtemplet og Quoc Tu Giam, Vietnams første universitet. Hoa Lo-fængslet, også kendt som \"Hanoi Hilton,\" som nu er et museum.&nbsp;</p><p>Er tiden til det besøger du også den imponerende keramik-mosaikvæg, inden dagen slutter med et besøg på den historiske Long Bien-bro fra 1899.</p><p>Du er tilbage på dit hotel ca. kl. 15.30</p><p><strong>BEMÆRK:&nbsp;</strong></p><ol><li><i>Mandag og fredag er Ho Chi Minh\'s Mausoleum lukket og kan kun ses udefra.&nbsp;</i></li><li><i>Mandag og fredag er Etnologiske Museum lukket og erstattes af keramiklandsbyen Bat Trang.</i></li><li><i>Shorts og korte kjoler/nederdel er ikke tilladt ved besøg i mausoleum og templer. Skuldre og knæ skal som udgangspunkt være dækket.</i></li><li><i>Rækkefølgen på ovenstående besøg kan være ændret.</i></li></ol>', '<p><a href=\"https://shiningcentralhotel.com/\">Shining Central hotel - Executive room</a></p>', 0, 'Breakfast, Lunch', NULL),
(15, 11, '1', 'Ankomst til Hanoi', '<p>Du ankommer til Hanoi, hvor du mødes af din chauffør, der i ankomsthallen står med et skilt med dit navn. Han kører dig til dit hotel i Hanoi. Du har resten af dagen fri i hovedstaden. Kommer du først eller midt på dagen kommer en af Vietnam Rejsers medarbejdere forbi og byder dig velkommen, holder et lille velkomstmøde, her får du lidt praktisk information og et SIM-kort. Så kan du altid kan komme i kontakt med os. (Kommer du sidst på dagen, ringer vi til dig via chaufføren, og aftaler at mødes næste dag).</p>', '<p><a href=\"https://shiningcentralhotel.com/\">Shining Central hotel - Executive room</a></p>', 1, 'Ingen måltider inkluderet', NULL),
(16, 11, '2', 'Hanoi city tour', '<p><strong>Kl. 08.00:&nbsp;</strong>Du bliver hentet på dit hotel og taget med på en spædende tur rundt i Hanoi. Dagen starter med et besøg ved Ho Chi Minh-komplekset, hvor du ser hans mausoleum, hus på pæle og den smukke have.</p><p>Herefter besøger du En-søjle-pagode, og fortsætter til Vietnams Etnologiske Museum. Her lærer du om landets rige kulturelle mangfoldighed gennem udstillinger af kostumer, arbejdsredskaber, &nbsp;og boliger fra nogle af de 54 etniske grupper der lever i Vietnam.</p><p>Efterfølgende tager du til Hoan Kiem-sø og Ngoc Son-templet, der hylder general Tran Hung Dao.&nbsp;</p><p>Slap af og hvil mens du nyder en lækker frokost i Hanois gamle bydel.</p><p>Om eftermiddagen besøger du Litteraturtemplet og Quoc Tu Giam, Vietnams første universitet. Hoa Lo-fængslet, også kendt som \"Hanoi Hilton,\" som nu er et museum.&nbsp;</p><p>Er tiden til det besøger du også den imponerende keramik-mosaikvæg, inden dagen slutter med et besøg på den historiske Long Bien-bro fra 1899.</p><p>Du er tilbage på dit hotel ca. kl. 15.30</p><p><strong>BEMÆRK:&nbsp;</strong></p><ol><li><i>Mandag og fredag er Ho Chi Minh\'s Mausoleum lukket og kan kun ses udefra.&nbsp;</i></li><li><i>Mandag og fredag er Etnologiske Museum lukket og erstattes af keramiklandsbyen Bat Trang.</i></li><li><i>Shorts og korte kjoler/nederdel er ikke tilladt ved besøg i mausoleum og templer. Skuldre og knæ skal som udgangspunkt være dækket.</i></li><li><i>Rækkefølgen på ovenstående besøg kan være ændret.</i></li></ol>', '<p><a href=\"https://shiningcentralhotel.com/\">Shining Central hotel - Executive room</a></p>', 2, 'Breakfast, Lunch', NULL),
(17, 11, '2-3', 'Halong Bay (Bai Tu Long Bay) 2 dage - Signature Cruise - (dag 1 af 2)', '<p>Dag 1</p><p><strong>08:00-08:30:&nbsp;</strong>Du hentes på dit hotel i Hanoi Old Quarter.</p><p><strong>11:30:</strong> Du ankommer til Signature check-in lounge-</p><p><strong>12:00: CHECK-IN</strong><br>Du bydes velkommen af bådens personale og gør klar til at gå ombord. Personalet tager sig af din bagage, og sørger for at den kommer med ombord.&nbsp;</p><p><strong>12:45: OMBORD</strong><br>Du transporteres til Signature Cruise af en mindre båd. Cruise Manageren og resten af personalet byder dig velkommen ombord. Sikkerhedsinstruks og dagens program gennemgås mens du nyder din velkomstdrink.</p><p><strong>13:00: FROKOST</strong><br>Frokost serveres i restauranten, mens du stille sejler ud mod Bai Tu Long Bay. En mindre turistet bugt end den populære Halong Bay. Nyd det flotte landskab.</p><p><strong>14:45: AKTIVITETER</strong><br>Første stop er den flydende fiskerlandsby Vung Vieng Floating fishing village, der også er kendt for dyrkning af perler. Du har mulighed for at låne en kajak og påegen hånd selje ud i landsbyen. Er du ikke til kajak, kan du for 50.000 VND/person blive sejlet ud i en bambusbåd af en lokal.</p><p><strong>16:00: TID TIL AFSLAPNING</strong><br>Tilbage på båden har du mulighed for at slappe af, nyde en kop kaffe eller en drink (for egen regning). Du kan også få en omgang massage - det skal bookes på forhånd..</p><p><strong>18:00 - 19:00: HAPPY HOUR</strong><br>Mens solen går ned, har du mulighed for at nyde en drink, når der er “Happy hour” med “Buy 1 get 1 free”.</p><p><strong>18:30: SUNSET PARTY</strong><br>Køkkenchefen viser hvordan man laver vietnamesiske forårsruller. Du har mulighed for at deltage.</p><p><strong>19:00: AFTENSMAD</strong><br>Efter en lang dag er det tid til aftensmad, der består af en “Set-Menu” med inspiration fra både lokale og vestlige retter. Forvent en del seafood.</p><p><strong>20:30: AFTEN UNDERHOLDNING ELLER AFSLAPNING</strong><br>Du har aftenen til fri disposition. Baren er åben, så der er rig mulighed for at at slappe af med en eksotisk cocktail, en øl, et glas vin eller en kop kaffe - der er selvfølgelig også juice og smoothies på menuen. Du kan også booke en massage på værelset. Af andre aktiviteter kan nævnes squid ﬁshing, nyd en film eller slap af på dækket.</p><p>Dag 2</p>', '<p>1 nat på båden i egen kahyt&nbsp;</p>', 3, 'Breakfast, Lunch, Dinner', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_day_images`
--

CREATE TABLE `itinerary_day_images` (
  `id` int(11) NOT NULL,
  `day_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itinerary_day_images`
--

INSERT INTO `itinerary_day_images` (`id`, `day_id`, `image_path`) VALUES
(16, 10, 'uploads/days/img_687e65c70b8288.26060475.jpg'),
(17, 10, 'uploads/days/img_687e65c70c2be2.17134285.jpg'),
(18, 10, 'uploads/days/img_687e65c70cb5e2.30890090.jpg'),
(19, 11, 'uploads/days/img_687e65c70e09e8.63406148.jpg'),
(20, 11, 'uploads/days/img_687e65c70e7ed9.46446620.jpg'),
(21, 11, 'uploads/days/img_687e65c70ef4f4.85860785.jpg'),
(22, 14, 'uploads/tours/687e46cdcacca_20240517_102831.jpg'),
(23, 14, 'uploads/tours/687e46cdcb131_20240520_101019.jpg'),
(24, 14, 'uploads/tours/687e46cdcb3ec_hanoi-city-tour-pagoda.jpg'),
(25, 16, 'uploads/tours/687e46cdcacca_20240517_102831.jpg'),
(26, 16, 'uploads/tours/687e46cdcb131_20240520_101019.jpg'),
(27, 16, 'uploads/tours/687e46cdcb3ec_hanoi-city-tour-pagoda.jpg'),
(28, 17, 'uploads/tours/687e4c69e2e57_signature-cruise (1).jpg'),
(29, 17, 'uploads/tours/687e4c69e335e_signature-cruise (3).jpg'),
(30, 17, 'uploads/tours/687e4c69e36ad_signature-cruise (8).jpg');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_flights`
--

CREATE TABLE `itinerary_flights` (
  `id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `airline_name` varchar(255) DEFAULT NULL,
  `price` float DEFAULT NULL,
  `content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itinerary_flights`
--

INSERT INTO `itinerary_flights` (`id`, `itinerary_id`, `airline_name`, `price`, `content`) VALUES
(1, 11, 'Qatar Airways', 7250, '<p>EK 152 X 04AUG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; København – Dubai&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; kl. 1600 0020+1</p><p>EK 394 X 05AUG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Dubai – Hanoi&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; kl.&nbsp; 0330 1315&nbsp;&nbsp;</p><p>&nbsp;</p><p>EK 393 L 22AUG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Ho Chi Minh – Dubai&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; kl.&nbsp; 2350 0400+1</p><p>&nbsp;EK 151 L 23AUG&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Dubai – København&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; kl.&nbsp; 0820 1315</p>');

-- --------------------------------------------------------

--
-- Table structure for table `tours`
--

CREATE TABLE `tours` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image1` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tours`
--

INSERT INTO `tours` (`id`, `title`, `description`, `image1`, `image2`, `image3`, `created_at`) VALUES
(1, 'Hanoi City Tour full day', '<p><strong>08:00:</strong> Tour guide and car pick you at your hotel then start the trip around the historical city. You visit Ho Chi Minh Complex with his Mausoleum, his house-on-stilt and garden.</p><p><strong>09:30:</strong> Visit One Pillar Pagoda built in 1049, the structure has become an important symbol for Hanoi today.</p><p><strong>10:00:</strong> Proceed to the Vietnam Museum of Ethnology to learn about Vietnam\'s diversity in cultures. You see the display of costumes, clothes, hunting tools, and life activities of 54 Ethnic groups in Vietnam.</p><p><strong>11:00:</strong> Leave for a legendary Hoan Kiem lake “turtle lake” and Ngoc Son Temple dedicated to a former general commander Tran Hung Dao for his struggle against Mongolians</p><p><strong>12:00:</strong> Go to have a rest and enjoy lunch in a local restaurant in Hanoi Old Quarter. Time for relaxing.</p><p><strong>13:00:</strong> Continue to visit the Literature Temple and Quoc Tu Giam – Vietnam’s first University. The temple is dedicated to Confucius, sages and talented scholars. It is the most famous temple in Hanoi.</p><p><strong>14:00:</strong> Visit Hoa Lo Prison built by French colonists for political prisoners. During the Vietnam War, it was used to detain American prisoners and sarcastically known as the \"Hanoi Hilton\". Half of the area remains as a museum today.</p><p><strong>15:00:</strong> Enjoy ceramic mosaic wall. Vietnam’s first public collective work of art, the Hanoi Ceramic Mosaic Mural is on the wall of the dyke system. The work on the mosaic began in 2007 and was completed in 2010 to celebrate the millennial anniversary of Hanoi.</p><p><strong>15:30:</strong> Tour ends after a visit to Long Bien historical bridge. The one was French legacy and was designed by a man designing the Eiffel tower in Paris. This bridge was built by the French in 1899 and finished in 1902. Walking on it for a photo is a good choice in Hanoi.</p>', 'uploads/tours/687285915d5cf_20240515_122841.jpg', 'uploads/tours/687285915d97c_20240517_102831.jpg', 'uploads/tours/687285915dc48_20240517_104129.jpg', '2025-07-12 15:53:55'),
(3, 'Ankomst til Hanoi', 'Du ankommer til Hanoi, hvor du mødes af din chauffør, der i ankomsthallen står med et skilt med dit navn. Han kører dig til dit hotel i Hanoi. Du har resten af dagen fri i hovedstaden.\r\n\r\nKommer du først eller midt på dagen kommer en af Vietnam Rejsers medarbejdere forbi og byder dig velkommen, holder et lille velkomstmøde, her får du lidt praktisk information og et SIM-kort. Så kan du altid kan komme i kontakt med os. (Kommer du sidst på dagen, ringer vi til dig via chaufføren, og aftaler at mødes næste dag).', NULL, NULL, NULL, '2025-07-21 08:34:08'),
(4, 'Hanoi city tour', '<p><strong>Kl. 08.00:&nbsp;</strong>Du bliver hentet på dit hotel og taget med på en spædende tur rundt i Hanoi. Dagen starter med et besøg ved Ho Chi Minh-komplekset, hvor du ser hans mausoleum, hus på pæle og den smukke have.</p><p>Herefter besøger du En-søjle-pagode, og fortsætter til Vietnams Etnologiske Museum. Her lærer du om landets rige kulturelle mangfoldighed gennem udstillinger af kostumer, arbejdsredskaber, &nbsp;og boliger fra nogle af de 54 etniske grupper der lever i Vietnam.</p><p>Efterfølgende tager du til Hoan Kiem-sø og Ngoc Son-templet, der hylder general Tran Hung Dao.&nbsp;</p><p>Slap af og hvil mens du nyder en lækker frokost i Hanois gamle bydel.</p><p>Om eftermiddagen besøger du Litteraturtemplet og Quoc Tu Giam, Vietnams første universitet. Hoa Lo-fængslet, også kendt som \"Hanoi Hilton,\" som nu er et museum.&nbsp;</p><p>Er tiden til det besøger du også den imponerende keramik-mosaikvæg, inden dagen slutter med et besøg på den historiske Long Bien-bro fra 1899.</p><p>Du er tilbage på dit hotel ca. kl. 15.30</p><p><strong>BEMÆRK:&nbsp;</strong></p><ol><li><i>Mandag og fredag er Ho Chi Minh\'s Mausoleum lukket og kan kun ses udefra.&nbsp;</i></li><li><i>Mandag og fredag er Etnologiske Museum lukket og erstattes af keramiklandsbyen Bat Trang.</i></li><li><i>Shorts og korte kjoler/nederdel er ikke tilladt ved besøg i mausoleum og templer. Skuldre og knæ skal som udgangspunkt være dækket.</i></li><li><i>Rækkefølgen på ovenstående besøg kan være ændret.</i></li></ol>', 'uploads/tours/687e46cdcacca_20240517_102831.jpg', 'uploads/tours/687e46cdcb131_20240520_101019.jpg', 'uploads/tours/687e46cdcb3ec_hanoi-city-tour-pagoda.jpg', '2025-07-21 13:55:25'),
(5, 'Hanoi Food Tour', '<p><strong>Kl. 17.30:</strong> Din aften starter, når vores guide møder dig på dit hotel og tager dig på en spændende gåtur gennem den gamle bydel, også kendt som \"De 36 Gader.\" Her besøger du lokale street-food køkkener, hvor du får mulighed for at smage det autentiske Vietnam.</p><p>Hanoi\'s gamle bydel har på trods af en historie på over tusind år, bevaret en rig madkultur. Hver gade har sit eget navn efter en varekategori der tidligere blev solgt i den pågældende gade. Selvom der oprindeligt var 36 gader, er der i dag mere end 50.</p><p>At finde de gode lokale retter i de skjulte gyder og på fortovene kan være en udfordring, men med vores madtur får du en let adgang til det vietnamesiske køkken, kultur og livsstil. Din guide sikrer, at du får en unik oplevelse ind i det Nordvietnamesisk køkken.</p><p><strong>Kl. 20.30:</strong> Når turen slutter, vil din guide sørge for, at du får en god afslutning på aftenen, ved at vise dig vej eller hjælpe med at bestille en taxa tilbage til dit hotel.</p>', 'uploads/tours/687e4713d816e_banh-xeo.jpg', 'uploads/tours/687e4713d842c_bun-cha.jpg', 'uploads/tours/687e4713d8643_eating-on-the-street-hanoi.jpg', '2025-07-21 13:56:07'),
(6, 'Halong Bay (Bai Tu Long Bay) 2 dage - Signature Cruise - (dag 1 af 2)', '<p><strong>08:00-08:30:&nbsp;</strong>Du hentes på dit hotel i Hanoi Old Quarter.</p><p><strong>11:30:</strong> Du ankommer til Signature check-in lounge-</p><p><strong>12:00: CHECK-IN</strong><br>Du bydes velkommen af bådens personale og gør klar til at gå ombord. Personalet tager sig af din bagage, og sørger for at den kommer med ombord.&nbsp;</p><p><strong>12:45: OMBORD</strong><br>Du transporteres til Signature Cruise af en mindre båd. Cruise Manageren og resten af personalet byder dig velkommen ombord. Sikkerhedsinstruks og dagens program gennemgås mens du nyder din velkomstdrink.</p><p><strong>13:00: FROKOST</strong><br>Frokost serveres i restauranten, mens du stille sejler ud mod Bai Tu Long Bay. En mindre turistet bugt end den populære Halong Bay. Nyd det flotte landskab.</p><p><strong>14:45: AKTIVITETER</strong><br>Første stop er den flydende fiskerlandsby Vung Vieng Floating fishing village, der også er kendt for dyrkning af perler. Du har mulighed for at låne en kajak og påegen hånd selje ud i landsbyen. Er du ikke til kajak, kan du for 50.000 VND/person blive sejlet ud i en bambusbåd af en lokal.</p><p><strong>16:00: TID TIL AFSLAPNING</strong><br>Tilbage på båden har du mulighed for at slappe af, nyde en kop kaffe eller en drink (for egen regning). Du kan også få en omgang massage - det skal bookes på forhånd..</p><p><strong>18:00 - 19:00: HAPPY HOUR</strong><br>Mens solen går ned, har du mulighed for at nyde en drink, når der er “Happy hour” med “Buy 1 get 1 free”.</p><p><strong>18:30: SUNSET PARTY</strong><br>Køkkenchefen viser hvordan man laver vietnamesiske forårsruller. Du har mulighed for at deltage.</p><p><strong>19:00: AFTENSMAD</strong><br>Efter en lang dag er det tid til aftensmad, der består af en “Set-Menu” med inspiration fra både lokale og vestlige retter. Forvent en del seafood.</p><p><strong>20:30: AFTEN UNDERHOLDNING ELLER AFSLAPNING</strong><br>Du har aftenen til fri disposition. Baren er åben, så der er rig mulighed for at at slappe af med en eksotisk cocktail, en øl, et glas vin eller en kop kaffe - der er selvfølgelig også juice og smoothies på menuen. Du kan også booke en massage på værelset. Af andre aktiviteter kan nævnes squid ﬁshing, nyd en film eller slap af på dækket.</p>', 'uploads/tours/687e4c69e2e57_signature-cruise (1).jpg', 'uploads/tours/687e4c69e335e_signature-cruise (3).jpg', 'uploads/tours/687e4c69e36ad_signature-cruise (8).jpg', '2025-07-21 14:19:21'),
(7, 'Halong Bay (Bai Tu Long Bay) 2 dage - Signature Cruise - (dag 2 af 2)', '<p><strong>06:30: TAI CHI&nbsp;</strong><br>Den bedste tid i Halong er om morgenen. Gå op på dækket og deltag i en Tai Chi klasse. Nyd en kop, te, kaffe eller juice mens båden stille sejler gennem det smukke landskab.</p><p><strong>07:00: MORGENMAD</strong><br>Let morgenmad serveres i restauranten (brød, te, kaffe).</p><p><strong>08:00: GROTTE BESØG</strong><br>Sidste aktivitet på turen er et besøg i Thien Canh Son grotten.</p><p><strong>09:00: CHECK-OUT</strong><br>Tilbage på båden er det tid til at slappe af og pakke bagagen sammen. Stil bagagen udenfor din dør og personalet sørger for den kommer med på land.</p><p><strong>09:30: FROKOST-BRUNCH</strong><br>Nyd en brunch buffet i restauranten. Tjek ud og mens du sejler i havn.</p><p><strong>10:45: TILBAGE TIL HANOI</strong><br>Du sejles tilbage til land, hvor du finder din bus. Transfer tilbage til Hanoi.</p><p>Du ankommer tilbage til det samme hotel hvor du blev hentet mellem kl. 15.00-17.00</p>', NULL, NULL, NULL, '2025-07-21 14:20:08'),
(8, 'Hanoi Street Food Tour', '<p><strong>17.30:</strong> Our tour guide will meet you at your hotel, then start a walking tour around the old quarter “36 streets” to visit some famous and specialized- food families or stalls, shops. You can see and taste some daily normal dishes that Vietnamese enjoy. The Old Quarter in Hanoi has its history more than one thousand years and it retains much of the old flavor that made the area special. Each street has a name of one merchandise (guilds) that was sold before. So there are streets of blacksmiths, silver shops, paper shops, headstone makers and more. Originally there were just 36 streets in the old quarter but today there are more than 50 streets. It is very difficult to find some local food on your own in hidden alleys or sidewalks. So the best way to make it easy is to join in our special food tour to learn about Vietnamese cuisine, culture, and lifestyle.</p><p>Your tour guide also gives you a unique experience to try the foods of the local people. We ensure the food is made hygienically. The benefit also goes back to the vendors or restaurants/ families as we buy directly from them. And so our Food tour creates dual benefits: giving you good experience and food and bringing benefits for Vietnamese people.</p><p><strong>20.30:</strong> Our tour guide says goodbye to you and shows you directions (or helps you call a taxi) back to your hotel. See you again!</p>', 'uploads/tours/688f80282156e_banh-mi.jpg', 'uploads/tours/688f802821f5f_bun-cha.jpg', 'uploads/tours/688f802822b81_pho-ga.jpg', '2025-08-03 15:28:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('agent','customer') DEFAULT 'agent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`, `photo`) VALUES
(5, 'Mike', 'test@test.dk', '', '$2y$10$s4NY1C8VpZpgHnmiGU8V7uU9waz.C.4V8Ig0K6KNOdGMHY0XGYc86', 'agent', '2025-07-13 02:45:25', 'uploads/agents/agent_6883af98e9e273.83206265.jpg'),
(19, 'Kenneth', 'kenneth.vietnamrejser@gmail.com', NULL, '$2y$10$ZrYJQj6JBaKbmGjWn8fZ.O5UVJ8OJq2DbCYa9qwb8eJK3Al.JnR2u', 'agent', '2025-07-21 08:11:32', NULL),
(20, 'Michael Martensen', 'michael.vietnamrejser@gmail.com', '0084931135143', '$2y$10$Cs5VmwDqcKt7RRzyHmUnAuH5Xd0JMc05YwUD93QMA3/gGj545fesy', 'agent', '2025-07-25 15:43:38', 'uploads/agents/agent_6883a62a32fa48.97588885.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `customer_files`
--
ALTER TABLE `customer_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `public_token` (`public_token`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `itinerary_days`
--
ALTER TABLE `itinerary_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `itinerary_id` (`itinerary_id`);

--
-- Indexes for table `itinerary_day_images`
--
ALTER TABLE `itinerary_day_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `day_id` (`day_id`);

--
-- Indexes for table `itinerary_flights`
--
ALTER TABLE `itinerary_flights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `itinerary_id` (`itinerary_id`);

--
-- Indexes for table `tours`
--
ALTER TABLE `tours`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `customer_files`
--
ALTER TABLE `customer_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `itineraries`
--
ALTER TABLE `itineraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `itinerary_days`
--
ALTER TABLE `itinerary_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `itinerary_day_images`
--
ALTER TABLE `itinerary_day_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `itinerary_flights`
--
ALTER TABLE `itinerary_flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tours`
--
ALTER TABLE `tours`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_files`
--
ALTER TABLE `customer_files`
  ADD CONSTRAINT `customer_files_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD CONSTRAINT `itineraries_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_days`
--
ALTER TABLE `itinerary_days`
  ADD CONSTRAINT `itinerary_days_ibfk_1` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_day_images`
--
ALTER TABLE `itinerary_day_images`
  ADD CONSTRAINT `itinerary_day_images_ibfk_1` FOREIGN KEY (`day_id`) REFERENCES `itinerary_days` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_flights`
--
ALTER TABLE `itinerary_flights`
  ADD CONSTRAINT `itinerary_flights_ibfk_1` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
