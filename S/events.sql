-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2023 at 04:09 PM
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
(1, 'Arts & Crafts', './images/h1-eventlist-img-1-1-800x593.jpg', './images/event-img-1.jpg', '2023-12-19', '12:30:00', '13:30:00', 'Modus doctus electram ius an, oratio iisque temporibus sed ad, porro interesset mea in. Nullam tempor at vim, usu eu denique hendrerit, impedit suscipiantur ex sit. Nec ne prompta liberavisse. Vis volumus delectus electram ne, eam omnes everti pertinacia ad, stet nonumy nusquam his at.Te sint tincidunt accommodare quo, per appetere inciderint cu, electram corrumpit mei et. Mea ea omnesque dignissim theophrastus, modo alterum honestatis quo id.', '120.00', 'Lance & Stacy Winer', 'Kids room No.01', 10, 0);

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
  MODIFY `eventId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
