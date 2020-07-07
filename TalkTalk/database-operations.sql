CREATE TABLE `CarEngines`(
	MessageID INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	MessageUser VARCHAR(30) NOT NULL,
	MessageTimeDate DATETIME NOT NULL,
	MessageData TEXT NOT NULL,
	MessageUserIP VARCHAR(20) NOT NULL
);
CREATE TABLE `LivingInSydney`(
	MessageID INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	MessageUser VARCHAR(30) NOT NULL,
	MessageTimeDate DATETIME NOT NULL,
	MessageData TEXT NOT NULL,
	MessageUserIP VARCHAR(20) NOT NULL
);
CREATE TABLE `ScubaDivingInCairns`(
	MessageID INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	MessageUser VARCHAR(30) NOT NULL,
	MessageTimeDate DATETIME NOT NULL,
	MessageData TEXT NOT NULL,
	MessageUserIP VARCHAR(20) NOT NULL
);
CREATE TABLE `SurfingInByronBay`(
	MessageID INT PRIMARY KEY AUTO_INCREMENT NOT NULL,
	MessageUser VARCHAR(30) NOT NULL,
	MessageTimeDate DATETIME NOT NULL,
	MessageData TEXT NOT NULL,
	MessageUserIP VARCHAR(20) NOT NULL
);

CREATE TABLE `CurrentUsersOnline`(
	UserName VARCHAR(30) NOT NULL,
	UserIP VARCHAR(20) NOT NULL,
	LastActive DATETIME NOT NULL DEFAULT NOW()
);

/**************************************/
INSERT INTO `CarEngines`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64('The Ferrari 488 GTB engine documentary - <a href="https://www.youtube.com/watch?v=oXlPXKc8Cbg">https://www.youtube.com/watch?v=oXlPXKc8Cbg</a>'),
	'unknown'
);

INSERT INTO `CarEngines`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64('Jaguar F-TYPE | Introducing the New Four-Cylinder Engine documentary - <a href="https://www.youtube.com/watch?v=TCFjrjiEKHc">https://www.youtube.com/watch?v=TCFjrjiEKHc</a>'),
	'unknown'
);

INSERT INTO `LivingInSydney`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64('I think we can all agree that any kind of real estate is very expensive in the Sydney area in general...'),
	'unknown'
);

INSERT INTO `LivingInSydney`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64('With all the infrastructure projects taking place across Sydney nowadays, one wonders about the fast train link between Sydney and Melbourne. Will it happen?'),
	'unknown'
);

INSERT INTO `ScubaDivingInCairns`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64("Swimming in Cairns is dangerous because of those crocs, but at least these dinosaurs don't go as far out as the diving spots."),
	'unknown'
);

INSERT INTO `ScubaDivingInCairns`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64("It's one of the best places in Australia for diving..."),
	'unknown'
);

INSERT INTO `SurfingInByronBay`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64("The most eastern point in all of Australia..."),
	'unknown'
);

INSERT INTO `SurfingInByronBay`(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES(
	'V89',
	NOW(),
	TO_BASE64("A lot of shark sightings and attacks have occurred here as well..."),
	'unknown'
);

/**************************************/
SET @userName="";
SELECT UserName INTO @userName FROM `CurrentUsersOnline` WHERE UserName='V89';
SELECT IF(LENGTH(@userName) > 0, "Username taken!", "Username available!") AS CheckCheck;

/***********************/

DELIMITER $$

CREATE FUNCTION isUserLogged(userNameInput CHAR(30)) RETURNS BOOLEAN
BEGIN
	IF (EXISTS(SELECT * FROM `CurrentUsersOnline` WHERE UserName=userNameInput)) THEN
		RETURN TRUE;
	ELSE
		INSERT INTO `CurrentUsersOnline` VALUES(userName, 'localhost', NOW());
		RETURN FALSE;
	END IF;
END$$

DELIMITER ;

SELECT IF(isUserLogged('V89'), "YES", "NO") AS `UserLogged`;

/*************************/

DELIMITER //

CREATE PROCEDURE DeleteInactiveUsers()
BEGIN
	DELETE FROM `CurrentUsersOnline` WHERE UserName <> 'V89' AND CONVERT(DATE_FORMAT(TIMEDIFF(NOW(), LastActive), '%i'), UNSIGNED INTEGER) > 30;
END //

DELIMITER ;
/*************************/
/* SET EVENT TO CALL STORED PROCEDURE */

CREATE EVENT `RemoveInactiveUsers` ON SCHEDULE EVERY 2 MINUTE STARTS '2020-07-04 00:00:00.000000' ENDS '2020-07-04 00:02:00.000000' ON COMPLETION NOT PRESERVE ENABLE DO CALL DeleteInactiveUsers()

CREATE EVENT REMOVE_INACTIVE_USERS
ON SCHEDULE EVERY 2 MINUTE
STARTS CURRENT_TIMESTAMP
ENDS CURRENT_TIMESTAMP + INTERVAL 2 MINUTE /* ENDS means the EVENT will end in 2 minutes. Without ENDS the EVENT will continue indefinitely. */
DO
	CALL DeleteInactiveUsers()


SELECT TIMEDIFF(TIME(NOW()), '01:00:00') AS diff;

/*************************/
DELIMITER //

CREATE PROCEDURE CleanChatRooms()
BEGIN
	DELETE FROM `CarEngines` WHERE MessageUser <> 'V89';
	DELETE FROM `LivingInSydney` WHERE MessageUser <> 'V89';
	DELETE FROM `ScubaDivingInCairns` WHERE MessageUser <> 'V89';
	DELETE FROM `SurfingInByronBay` WHERE MessageUser <> 'V89';
END //

DELIMITER ;
/*************************/

/*
if hour is 4 or later and minute is 30 or later - then show time until the next time (tomorrow) it's 4:30AM
else if hour is less than 4 and minute is less than 30 then show remaining time until 4:30AM
*/

DELIMITER $$

CREATE FUNCTION ChatRoomErasureCountdown() RETURNS VARCHAR(10)
BEGIN
	DECLARE today VARCHAR(10) DEFAULT TIME(NOW());
	DECLARE tomorrow VARCHAR(10);
	DECLARE fullTomorrow VARCHAR(20);

	DECLARE timeRemaining VARCHAR(10);

	SET tomorrow = SUBSTRING(DATE_ADD(NOW(), INTERVAL 1 DAY), 1, 10);
	SET fullTomorrow = CONCAT(tomorrow, ' 04:30:00');

	IF (HOUR(today) >= 4 AND MINUTE(today) >= 30) THEN
		SET timeRemaining = TIMEDIFF(fullTomorrow, NOW());
	ELSEIF (HOUR(today) >= 4 AND MINUTE(today) < 30) THEN
		SET timeRemaining = TIMEDIFF(fullTomorrow, NOW());
	ELSE
		SET timeRemaining = TIMEDIFF('04:30:00', today);
	END IF;

	RETURN timeRemaining;
END$$

DELIMITER ;

SELECT ChatRoomErasureCountdown() AS FinalDiff;
/*************************/