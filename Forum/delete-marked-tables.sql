/*
MySQL operations used by this project
*************************************
EVENT runs every 5 minutes by executing a stored procedure
created to delete any tables marked for deletion (deleted original posts, not replies).

Certain information has been changed.
*/


DELIMITER //

CREATE PROCEDURE DELETE_TABLES()
BEGIN
	DECLARE farewellList TEXT;

	CREATE TEMPORARY TABLE DELETION_LIST(tableName VARCHAR(100));

	INSERT INTO DELETION_LIST(tableName)
	SELECT table_name FROM information_schema.tables WHERE 'databaseName' = database() AND table_name LIKE 'DELETED%';

	SELECT CONCAT('DROP TABLE IF EXISTS ', GROUP_CONCAT(CONCAT('databaseName','.',tableName)),';') INTO @farewellList FROM DELETION_LIST;

	SELECT @farewellList;

	PREPARE delete_request FROM @farewellList;
	EXECUTE delete_request;
	DEALLOCATE PREPARE delete_request;
END //

DELIMITER ;

/********************************/

CREATE EVENT REMOVE_TABLES
ON SCHEDULE EVERY 5 MINUTE
STARTS CURRENT_TIMESTAMP
ENDS CURRENT_TIMESTAMP + INTERVAL 5 MINUTE /* ENDS means the EVENT will end in 5 minutes. Without ENDS the EVENT will continue indefinitely. */
DO
	CALL DELETE_TABLES()
