-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2023 at 04:16 PM
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
-- Database: `webdev1_playroom`
--

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
(8, 'parent', 'juanitadubuc', 'X0C_9g3', 'Juanita', 'Dubuc', '321-987-0123', 'juanita@email.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
