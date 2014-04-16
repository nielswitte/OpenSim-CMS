INSERT INTO `users` (`id`, `username`, `firstName`, `lastName`, `email`, `password`, `lastLogin`) VALUES
	(0, 'OpenSim', 'Open', 'Sim', NULL, '$2y$10$6Ypy3i9luoc01qUTi3wnCe9SPiOYVWdVO0GgiPXpN.Mc3V.wjMlfS', NULL),
	(1, 'admin', 'John', 'Doe', 'john@doe.com0', '$2y$10$chCX.iTGaLF7v/2aQY8qZe3.BGhguBTcuqXupCL/XrN32PC5Ee9pq', NULL);

INSERT INTO `user_permissions` (`userId`, `auth`, `chat`, `comment`, `document`, `file`, `grid`, `meeting`, `meetingroom`, `presentation`, `user`) VALUES
	(0, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7),
	(1, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7),
