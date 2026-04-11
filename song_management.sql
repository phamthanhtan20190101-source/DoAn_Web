-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th4 11, 2026 lúc 08:30 AM
-- Phiên bản máy phục vụ: 5.7.25
-- Phiên bản PHP: 7.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `song_management`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `account`
--

CREATE TABLE `account` (
  `AccountID` int(11) NOT NULL,
  `Username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Avatar_URL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Role` enum('admin','user') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `Status` tinyint(1) DEFAULT '1',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `account`
--

INSERT INTO `account` (`AccountID`, `Username`, `Password`, `Email`, `Avatar_URL`, `Role`, `Status`, `CreatedAt`) VALUES
(1, 'admin', '$2y$10$ntlFwwxDe/8inJsV9VF9e.DuKrX/7UneIw84auVr5VTew24ACJYf6', 'admin@lyrx.vn', 'images/admin_avatar.png', 'admin', 1, '2026-04-08 19:37:38'),
(2, 'user', '$2y$10$ierCpJMWgywsmt9D5butVOU.kQlUWBcqyIPPUBSYynQmIxDqR1aB2', 'fan@gmail.com', 'images/user_avatar.png', 'user', 1, '2026-04-08 19:37:38');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `albums`
--

CREATE TABLE `albums` (
  `AlbumID` int(11) NOT NULL,
  `Title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ReleaseYear` int(11) DEFAULT NULL,
  `CoverImage_URL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `artists`
--

CREATE TABLE `artists` (
  `ArtistID` int(11) NOT NULL,
  `Name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Image_URL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Bio` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `artists`
--

INSERT INTO `artists` (`ArtistID`, `Name`, `Image_URL`, `Country`, `Bio`) VALUES
(1, 'hhhh', NULL, 'Mỹ', 'no'),
(2, 'Đan Nguyên', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

CREATE TABLE `banners` (
  `BannerID` int(11) NOT NULL,
  `Title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ImageURL` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LinkURL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `OrderIndex` int(11) DEFAULT '0',
  `IsActive` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banners`
--

INSERT INTO `banners` (`BannerID`, `Title`, `ImageURL`, `LinkURL`, `OrderIndex`, `IsActive`) VALUES
(5, 'Bùng nổ giai điệu EDM 2026', 'uploads/banners/banner_69d9ae165a278_photo-1511671782779-c97d3d27a1d4.jpg', '', 0, 1),
(6, 'Top 100 Nhạc Dance Gây Nghiện', 'uploads/banners/banner_69d9ae311c1bb_photo-1459749411175-04bf5292ceea.jpg', '', 0, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `comments`
--

CREATE TABLE `comments` (
  `CommentID` int(11) NOT NULL,
  `AccountID` int(11) NOT NULL,
  `SongID` int(11) NOT NULL,
  `Content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `Status` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `genres`
--

CREATE TABLE `genres` (
  `GenreID` int(11) NOT NULL,
  `Name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `genres`
--

INSERT INTO `genres` (`GenreID`, `Name`) VALUES
(2, 'ballad'),
(3, 'Bolero');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `playlists`
--

CREATE TABLE `playlists` (
  `PlaylistID` int(11) NOT NULL,
  `Title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `IsPublic` tinyint(1) DEFAULT '1',
  `IsAdmin` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `playlist_song`
--

CREATE TABLE `playlist_song` (
  `PlaylistID` int(11) NOT NULL,
  `SongID` int(11) NOT NULL,
  `AddedAt` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `ConfigKey` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ConfigValue` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`ConfigKey`, `ConfigValue`) VALUES
('footer_info', '© 2026 Lyrx Music - Đồ án Công nghệ thông tin AGU'),
('maintenance_mode', '0'),
('site_name', 'Lyrx Music');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `songs`
--

CREATE TABLE `songs` (
  `SongID` int(11) NOT NULL,
  `Title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `CoverImage_URL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Duration` int(11) DEFAULT NULL,
  `Country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ReleaseDate` date DEFAULT NULL,
  `FilePath_URL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `PlayCount` int(11) DEFAULT '0',
  `AlbumID` int(11) DEFAULT NULL,
  `GenreID` int(11) DEFAULT NULL,
  `AccountID` int(11) DEFAULT NULL,
  `Lyrics` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `songs`
--

INSERT INTO `songs` (`SongID`, `Title`, `CoverImage_URL`, `Duration`, `Country`, `ReleaseDate`, `FilePath_URL`, `PlayCount`, `AlbumID`, `GenreID`, `AccountID`, `Lyrics`) VALUES
(1, '50 năm về sau', NULL, 299, NULL, '2026-03-31', 'uploads/songs/song_69d779eb734580.66153730.mp3', 40, NULL, 2, NULL, NULL),
(5, 'Mùa xuân đó có em', NULL, 275, NULL, '2026-04-07', 'uploads/songs/song_69d87eeac06385.46105012.mp3', 100, NULL, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `song_artist`
--

CREATE TABLE `song_artist` (
  `SongID` int(11) NOT NULL,
  `ArtistID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `song_artist`
--

INSERT INTO `song_artist` (`SongID`, `ArtistID`) VALUES
(5, 2);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_favorites`
--

CREATE TABLE `user_favorites` (
  `Username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SongID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_history`
--

CREATE TABLE `user_history` (
  `ID` int(11) NOT NULL,
  `Username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SongID` int(11) DEFAULT NULL,
  `ListenedAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`AccountID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Chỉ mục cho bảng `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`AlbumID`);

--
-- Chỉ mục cho bảng `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`ArtistID`);

--
-- Chỉ mục cho bảng `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`BannerID`);

--
-- Chỉ mục cho bảng `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `FK_Comments_Account` (`AccountID`),
  ADD KEY `FK_Comments_Songs` (`SongID`);

--
-- Chỉ mục cho bảng `genres`
--
ALTER TABLE `genres`
  ADD PRIMARY KEY (`GenreID`);

--
-- Chỉ mục cho bảng `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`PlaylistID`),
  ADD KEY `FK_Playlists_Account` (`AccountID`);

--
-- Chỉ mục cho bảng `playlist_song`
--
ALTER TABLE `playlist_song`
  ADD PRIMARY KEY (`PlaylistID`,`SongID`),
  ADD KEY `FK_PlaylistSong_Songs` (`SongID`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`ConfigKey`);

--
-- Chỉ mục cho bảng `songs`
--
ALTER TABLE `songs`
  ADD PRIMARY KEY (`SongID`),
  ADD KEY `FK_Songs_Albums` (`AlbumID`),
  ADD KEY `FK_Songs_Genres` (`GenreID`),
  ADD KEY `FK_Songs_Account` (`AccountID`);

--
-- Chỉ mục cho bảng `song_artist`
--
ALTER TABLE `song_artist`
  ADD PRIMARY KEY (`SongID`,`ArtistID`),
  ADD KEY `FK_SongArtist_Artists` (`ArtistID`);

--
-- Chỉ mục cho bảng `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`Username`,`SongID`);

--
-- Chỉ mục cho bảng `user_history`
--
ALTER TABLE `user_history`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `account`
--
ALTER TABLE `account`
  MODIFY `AccountID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `albums`
--
ALTER TABLE `albums`
  MODIFY `AlbumID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `artists`
--
ALTER TABLE `artists`
  MODIFY `ArtistID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `banners`
--
ALTER TABLE `banners`
  MODIFY `BannerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `comments`
--
ALTER TABLE `comments`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `genres`
--
ALTER TABLE `genres`
  MODIFY `GenreID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `playlists`
--
ALTER TABLE `playlists`
  MODIFY `PlaylistID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `songs`
--
ALTER TABLE `songs`
  MODIFY `SongID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `user_history`
--
ALTER TABLE `user_history`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `FK_Comments_Account` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_Comments_Songs` FOREIGN KEY (`SongID`) REFERENCES `songs` (`SongID`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `FK_Playlists_Account` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `playlist_song`
--
ALTER TABLE `playlist_song`
  ADD CONSTRAINT `FK_PlaylistSong_Playlists` FOREIGN KEY (`PlaylistID`) REFERENCES `playlists` (`PlaylistID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_PlaylistSong_Songs` FOREIGN KEY (`SongID`) REFERENCES `songs` (`SongID`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `songs`
--
ALTER TABLE `songs`
  ADD CONSTRAINT `FK_Songs_Account` FOREIGN KEY (`AccountID`) REFERENCES `account` (`AccountID`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_Songs_Albums` FOREIGN KEY (`AlbumID`) REFERENCES `albums` (`AlbumID`) ON DELETE SET NULL,
  ADD CONSTRAINT `FK_Songs_Genres` FOREIGN KEY (`GenreID`) REFERENCES `genres` (`GenreID`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `song_artist`
--
ALTER TABLE `song_artist`
  ADD CONSTRAINT `FK_SongArtist_Artists` FOREIGN KEY (`ArtistID`) REFERENCES `artists` (`ArtistID`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_SongArtist_Songs` FOREIGN KEY (`SongID`) REFERENCES `songs` (`SongID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
