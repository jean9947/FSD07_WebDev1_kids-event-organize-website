-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2023 at 03:50 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `playroom`
--

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `eventId` int(11) NOT NULL,
  `eventName` varchar(100) NOT NULL,
  `smallPhotoPath` varchar(200) NOT NULL,
  `largePhotoPath` varchar(200) NOT NULL,
  `date` date NOT NULL,
  `startTime` time NOT NULL,
  `endTime` time NOT NULL,
  `eventDescription` varchar(1000) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `organizer` varchar(100) NOT NULL,
  `venue` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `attendeesCount` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`eventId`, `eventName`, `smallPhotoPath`, `largePhotoPath`, `date`, `startTime`, `endTime`, `eventDescription`, `price`, `organizer`, `venue`, `capacity`, `attendeesCount`) VALUES
(1, 'Arts & Crafts', '/images/h1-eventlist-img-1-1-800x593.jpg', '/images/event-img-1.jpg', '2023-12-19', '12:30:00', '13:30:00', 'Modus doctus electram ius an, oratio iisque temporibus sed ad, porro interesset mea in. Nullam tempor at vim, usu eu denique hendrerit, impedit suscipiantur ex sit. Nec ne prompta liberavisse. Vis volumus delectus electram ne, eam omnes everti pertinacia ad, stet nonumy nusquam his at.Te sint tincidunt accommodare quo, per appetere inciderint cu, electram corrumpit mei et. Mea ea omnesque dignissim theophrastus, modo alterum honestatis quo id.', '120.00', 'Lance & Stacy Winer', 'Kids room No.01', 10, 0),
(2, 'Trampolines', '/images/h1-eventlist-img-2-800x593.jpg', '/images/event-img-2.jpg', '2023-11-17', '08:00:00', '10:00:00', 'Graecis corrumpit comprehensam eum ut, dolores accusamus salutatus ea ius. Mel assum tincidunt vituperatoribus ex, enim consectetuer ea eam, et ludus affert pertinax mei. Impedit ceteros te his, meis eirmod eum an. Te sit legimus albucius. Vivendo ocurreret at pri, lobortis antiopam pro at. Vitae oratio ex est, vim cu animal intellegam ullamcorper, apeirian dissentiet an cum. Cum habeo facilis postulant ne. Meliore recteque pri, debet urbanitas necessitatibus.', '45.00', 'Melani & Mark Hamp', 'Kids room Garden', 10, 0),
(3, 'Halloween Party', '/images/h1-eventlist-img-3-800x593.jpg', '/images/event-img-3.jpg', '2023-10-30', '12:00:00', '22:00:00', 'Lorem ipsum dolor sit amet, cum te decore alterum admodum. Te qui doctus voluptaria inciderint, in mel probo neglegentur. Causae corrumpit laboramus sed at. Usu hinc dolore erroribus ut, eos id bonorum propriae torquatos. Aliquam expetenda reformidans in mei. Cum te legere tamquam explicari. Has feugiat veritus eu, eum tritani laores ancillae at, mundi homero domerum in est. Vix at nonumy honestatis, ne ego lorem facilisis pertinacia his expetenda propriae torquatos.', '180.00', 'Angela & Nate Lopez', 'Kids room No.03', 10, 0),
(4, 'Slides', '/images/h1-eventlist-img-4-800x593.jpg', '/images/event-img-4.jpg', '2023-09-18', '15:00:00', '18:00:00', 'Vis tota vivendo disputationi ei. Ius veri lorem sanctus ne. Sed te malis eruditi, his in affert delicata, cetero quaestio an ius. Te aeque laoreet ius. Hinc soleat vix ad, eum id tale etiam salutandi. Te sint tincidunt accommodare quo, per appetere inciderint cu, electram corrumpit mei et. Mea ea omnesque dignissim theophrastus, modo alterum honestatis quo id. Vix possit torquatos vulputate ad, et vix tota definitiones. Ut dicant disputationi placerat concludaturque vix.', '45.00', 'Jon & Natasha Shade', 'Kids room Garden', 10, 0),
(5, 'Science', '/images/h1-eventlist-img-5-800x593.jpg', '/images/event-img-5.jpg', '2023-08-22', '08:00:00', '16:00:00', 'Nullam tempor at vim, usu eu denique hendrerit, impedit suscipiantur ex sit. Nec ne prompta liberavisse. Vis volumus delectus electram ne, eam omnes evertim pertinacia ad, stet nonumy nusquam his at. Ei clita saperet argumentum mea, qui ipsum homero reprimique in. Suas quas venimam at eam, vix an vide deleniti, oblique placerat pri no. Vel id prima reprimique dissentiet, eam cu semper albucius. Sed no posse semper. In popul legimus vocibus, doctus animal eu.', '285.00', 'Lance & Stacy Winer', 'Kids room No.04', 10, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`eventId`),
  ADD UNIQUE KEY `eventName` (`eventName`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `eventId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
