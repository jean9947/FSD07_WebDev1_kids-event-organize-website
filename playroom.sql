-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2023 at 08:30 PM
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
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `bookingId` int(11) NOT NULL,
  `eventId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `childId` int(11) NOT NULL,
  `bookingTimeStamp` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`bookingId`, `eventId`, `userId`, `childId`, `bookingTimeStamp`) VALUES
(8, 2, 9, 9, '2023-03-29 12:37:22'),
(9, 3, 9, 10, '2023-03-29 12:54:41'),
(10, 1, 9, 11, '2023-03-29 13:59:31');

-- --------------------------------------------------------

--
-- Table structure for table `children`
--

CREATE TABLE `children` (
  `childId` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `DOB` date NOT NULL,
  `gender` enum('boy','girl') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `children`
--

INSERT INTO `children` (`childId`, `userId`, `firstName`, `lastName`, `DOB`, `gender`) VALUES
(1, 9, 'Annie', 'Smith', '2019-12-21', 'girl'),
(8, 9, 'Mia', 'Musk', '2012-11-12', 'boy'),
(9, 9, 'Mia', 'Musk', '2012-11-12', 'girl'),
(10, 9, 'Andy', 'Dust', '2019-01-01', 'boy'),
(11, 9, 'Sally', 'Chen', '2017-12-21', 'girl');

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

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userId` int(11) NOT NULL,
  `role` enum('admin','parent') NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `phoneNumber` varchar(20) NOT NULL,
  `email` varchar(320) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userId`, `role`, `username`, `password`, `firstName`, `lastName`, `phoneNumber`, `email`) VALUES
(1, 'admin', 'johnabbott', 'H9X_3l9', 'John', 'Abbott', '514-457-5036', 'johnabbott@email.com'),
(2, 'admin', 'jeanchen', 'H2R_1z1', 'Jean', 'Chen', '438-389-5676', 'jean7499@hotmail.com'),
(3, 'admin', 'siyichen', 'H4A_1w1', 'Siyi', 'Chen', '123-456-7890', 'siyi@email.com'),
(4, 'parent', 'eliarsenault', 'H9P_2s2', 'Elizabeth', 'Arsenault', '777-555-3333', 'elizabeth@email.com'),
(5, 'parent', 'marggendron', 'N0G_9e7', 'Marg', 'Gendron', '567-890-1234', 'marg@email.com'),
(6, 'parent', 'muhammadlarsen', 'V9M_7x2', 'Muhammad', 'Larsen', '666-111-8888', 'muhammad@email.com'),
(7, 'parent', 'vernonanderson', 'E3N_6h0', 'Vernon', 'Anderson', '555-999-0000', 'vernon@email.com'),
(8, 'parent', 'juanitadubuc', 'X0C_9g3', 'Juanita', 'Dubuc', '321-987-0123', 'juanita@email.com'),
(9, 'parent', 'johnsmith', 'Js123456', 'John', 'Smith', '123-456-7788', 'JohnSmith@gmail.com'),
(10, 'parent', 'annmusk', 'Am123456', 'Ann', 'Musk', '123-456-7788', 'annmusk@gmail.com'),
(11, 'parent', 'tylorswift', 'Ts123456', 'Tylor', 'Swift', '112-233-4455', 'ts@gmail.com'),
(12, 'parent', 'aronlake', 'Al123456', 'Aron', 'Lake', '123-123-1234', 'al@email.com'),
(13, 'parent', 'georgewest', 'Gw123456', 'George', 'West', '123-123-1234', 'gw@email.com'),
(14, 'parent', 'aronnlaker', 'Al123456', 'Aronn', 'Laker', '123-123-1234', 'all@email.com'),
(15, 'parent', 'juliahill', 'Jh123456', 'Julia', 'Hill', '123-123-1234', 'jh@email.com'),
(16, 'parent', 'dannielstone', 'Ds123456', 'Danniel', 'Stone', '123-456-7890', 'ds@email.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`bookingId`),
  ADD KEY `eventId` (`eventId`),
  ADD KEY `userId` (`userId`),
  ADD KEY `childId` (`childId`);

--
-- Indexes for table `children`
--
ALTER TABLE `children`
  ADD PRIMARY KEY (`childId`),
  ADD KEY `userId` (`userId`) USING BTREE;

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`eventId`),
  ADD UNIQUE KEY `eventName` (`eventName`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `bookingId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `children`
--
ALTER TABLE `children`
  MODIFY `childId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `eventId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_bookings_children` FOREIGN KEY (`childId`) REFERENCES `children` (`childId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_events` FOREIGN KEY (`eventId`) REFERENCES `events` (`eventId`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bookings_user` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `children`
--
ALTER TABLE `children`
  ADD CONSTRAINT `fk_children_user` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
