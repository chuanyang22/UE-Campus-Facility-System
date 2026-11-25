-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 05, 2026 at 03:25 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `campus_facility_db`
--
CREATE DATABASE IF NOT EXISTS `campus_facility_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `campus_facility_db`;

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

DROP TABLE IF EXISTS `booking`;
CREATE TABLE `booking` (
  `BookingID` varchar(10) NOT NULL,
  `UserID` varchar(10) NOT NULL,
  `FacilityID` varchar(10) NOT NULL,
  `BookingDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `Purpose` varchar(200) NOT NULL,
  `BookingStatus` enum('Confirmed','Checked-In','Cancelled','No-Show','Completed') DEFAULT 'Confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`BookingID`, `UserID`, `FacilityID`, `BookingDate`, `StartTime`, `EndTime`, `Purpose`, `BookingStatus`) VALUES
('BK-3001', 'U1001', 'FAC-01', '2026-11-05', '10:00:00', '12:00:00', 'Mini Project Group Discussion', 'Completed'),
('BK-3002', 'U1002', 'FAC-03', '2026-11-05', '14:00:00', '16:00:00', 'Weekly Badminton Practice', 'Confirmed'),
('BK-3003', 'U1003', 'FAC-02', '2026-11-05', '16:00:00', '18:00:00', 'Faculty Committee Meeting', 'Confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
CREATE TABLE `equipment` (
  `EquipmentID` varchar(10) NOT NULL,
  `BookingID` varchar(10) NOT NULL,
  `ItemName` varchar(100) NOT NULL,
  `Category` enum('Sports Gear','AV Accessories','Stationery') NOT NULL,
  `BorrowedQty` int(11) NOT NULL DEFAULT 1,
  `BorrowStatus` enum('Borrowed','Returned','Damaged','Lost') DEFAULT 'Borrowed',
  `ConditionOnReturn` varchar(100) DEFAULT 'Good'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`EquipmentID`, `BookingID`, `ItemName`, `Category`, `BorrowedQty`, `BorrowStatus`, `ConditionOnReturn`) VALUES
('EQ-4001', 'BK-3001', 'Type-C to HDMI Converter', 'AV Accessories', 1, 'Returned', 'Good'),
('EQ-4002', 'BK-3001', 'Whiteboard Marker Set (4 colors)', 'Stationery', 1, 'Returned', 'Good'),
('EQ-4003', 'BK-3002', 'Badminton Racket Pair', 'Sports Gear', 2, 'Borrowed', 'Pending Return');

-- --------------------------------------------------------

--
-- Table structure for table `facility`
--

DROP TABLE IF EXISTS `facility`;
CREATE TABLE `facility` (
  `FacilityID` varchar(10) NOT NULL,
  `FacilityName` varchar(100) NOT NULL,
  `Category` enum('Sports','Academic') NOT NULL,
  `LocationBlock` varchar(50) NOT NULL,
  `Capacity` int(11) NOT NULL,
  `AvailabilityStatus` enum('Available','Under Maintenance','Closed') DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facility`
--

INSERT INTO `facility` (`FacilityID`, `FacilityName`, `Category`, `LocationBlock`, `Capacity`, `AvailabilityStatus`) VALUES
('FAC-01', 'Library Discussion Room 1', 'Academic', 'Library Block A, Level 2', 6, 'Available'),
('FAC-02', 'Library Discussion Room 2', 'Academic', 'Library Block A, Level 2', 8, 'Available'),
('FAC-03', 'Badminton Court 1', 'Sports', 'Sports Complex Hall 1', 4, 'Available'),
('FAC-04', 'Badminton Court 2', 'Sports', 'Sports Complex Hall 1', 4, 'Under Maintenance'),
('FAC-05', 'Futsal Court A', 'Sports', 'Outdoor Sports Zone', 10, 'Available');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE `feedback` (
  `FeedbackID` varchar(10) NOT NULL,
  `BookingID` varchar(10) NOT NULL,
  `CleanlinessRating` decimal(2,1) NOT NULL CHECK (`CleanlinessRating` between 1.0 and 5.0),
  `ConditionRating` decimal(2,1) NOT NULL CHECK (`ConditionRating` between 1.0 and 5.0),
  `OverallScore` decimal(2,1) NOT NULL,
  `UserComments` text DEFAULT NULL,
  `AdminRemarks` varchar(300) DEFAULT NULL,
  `SubmissionDate` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`FeedbackID`, `BookingID`, `CleanlinessRating`, `ConditionRating`, `OverallScore`, `UserComments`, `AdminRemarks`, `SubmissionDate`) VALUES
('FB-6001', 'BK-3001', 5, 4, 4.5, 'Room was very clean and quiet. Projector HDMI port flickered occasionally.', 'Reported to maintenance under ticket MT-5002. Thank you!', '2026-11-05 16:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

DROP TABLE IF EXISTS `maintenance`;
CREATE TABLE `maintenance` (
  `MaintenanceID` varchar(10) NOT NULL,
  `FacilityID` varchar(10) NOT NULL,
  `ReportedByUserID` varchar(10) NOT NULL,
  `IssueTitle` varchar(100) NOT NULL,
  `IssueDescription` text NOT NULL,
  `UrgencyLevel` enum('Low','Medium','High','Critical') NOT NULL,
  `ScheduledDate` date NOT NULL,
  `ResolutionDate` date DEFAULT NULL,
  `MaintenanceStatus` enum('Pending','In-Progress','Completed','Archived') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`MaintenanceID`, `FacilityID`, `ReportedByUserID`, `IssueTitle`, `IssueDescription`, `UrgencyLevel`, `ScheduledDate`, `ResolutionDate`, `MaintenanceStatus`) VALUES
('MT-5001', 'FAC-04', 'U1004', 'Damaged Net Post', 'Badminton net post fastener is loose and sagging in the middle.', 'High', '2026-11-05', NULL, 'In-Progress'),
('MT-5002', 'FAC-01', 'U1001', 'HDMI Display Flickering', 'The wall projector HDMI cable cuts out intermittently.', 'Medium', '2026-11-05', '2026-11-05', 'Completed');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `UserID` varchar(10) NOT NULL,
  `FullName` varchar(100) NOT NULL,
  `CampusEmail` varchar(100) NOT NULL,
  `PhoneNo` varchar(15) NOT NULL,
  `UserRole` enum('Student','Staff','Admin','Technician') NOT NULL,
  `AccountStatus` enum('Active','Suspended','Inactive') DEFAULT 'Active',
  `BookingBanUntil` datetime DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`UserID`, `FullName`, `CampusEmail`, `PhoneNo`, `UserRole`, `AccountStatus`, `PasswordHash`) VALUES
('U1001', 'Alex Tan', 'alextan@student.campus.edu', '012-3456789', 'Student', 'Active', '$2y$10$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm'),
('U1002', 'Sarah Lee', 'sarahlee@student.campus.edu', '013-8899776', 'Student', 'Active', '$2y$10$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm'),
('U1003', 'Dr. Farhan Azman', 'farhan@staff.campus.edu', '017-4455661', 'Staff', 'Active', '$2y$10$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm'),
('U1004', 'Admin Facilities', 'admin@campus.edu', '019-9988770', 'Admin', 'Active', '$2y$10$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm'),
('T0001', 'Encik Hafiz Rahman', 'hafiz.tech@campus.edu', '012-7788990', 'Technician', 'Active', '$2y$10$e8wFwO4q49tX6iV0v3cQyeC.kR11GfZo23YhZz6Lqm8.W4E2Z9jKm');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `fk_booking_user` (`UserID`),
  ADD KEY `fk_booking_facility` (`FacilityID`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`EquipmentID`),
  ADD KEY `fk_equipment_booking` (`BookingID`);

--
-- Indexes for table `facility`
--
ALTER TABLE `facility`
  ADD PRIMARY KEY (`FacilityID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`FeedbackID`),
  ADD UNIQUE KEY `BookingID` (`BookingID`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`MaintenanceID`),
  ADD KEY `fk_maintenance_facility` (`FacilityID`),
  ADD KEY `fk_maintenance_reporter` (`ReportedByUserID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `CampusEmail` (`CampusEmail`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `fk_booking_facility` FOREIGN KEY (`FacilityID`) REFERENCES `facility` (`FacilityID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`UserID`) REFERENCES `user` (`UserID`) ON UPDATE CASCADE;

--
-- Constraints for table `equipment`
--
ALTER TABLE `equipment`
  ADD CONSTRAINT `fk_equipment_booking` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_booking` FOREIGN KEY (`BookingID`) REFERENCES `booking` (`BookingID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `fk_maintenance_facility` FOREIGN KEY (`FacilityID`) REFERENCES `facility` (`FacilityID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maintenance_reporter` FOREIGN KEY (`ReportedByUserID`) REFERENCES `user` (`UserID`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
