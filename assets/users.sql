INSERT INTO `users` (`id`, `username`, `firstName`, `lastName`, `email`, `password`, `lastLogin`) VALUES
	(1, 'OpenSim', 'Open', 'Sim', 'root@localhost', '$2y$10$6Ypy3i9luoc01qUTi3wnCe9SPiOYVWdVO0GgiPXpN.Mc3V.wjMlfS', NULL),
	(2, 'admin', 'John', 'Doe', 'john@doe.com0', '$2y$10$chCX.iTGaLF7v/2aQY8qZe3.BGhguBTcuqXupCL/XrN32PC5Ee9pq', NULL);

INSERT INTO `user_permissions` (`userId`, `auth`, `chat`, `comment`, `document`, `file`, `grid`, `meeting`, `meetingroom`, `presentation`, `user`) VALUES
	(1, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7),
	(2, 7, 7, 7, 7, 7, 7, 7, 7, 7, 7);
