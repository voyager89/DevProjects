const fs = require("fs"); // FileSystem
const https = require("https"); // Securing the protocol
const mysql = require("mysql"); // Database connection
const WebSocket = require("ws"); // WebSocket for connecting client to server

let lastMessageReceived = 0; // UNIX timestamp of when the last message was received

// The SSL certificate and key (file paths omitted)
const server = https.createServer(
{
	cert : fs.readFileSync(), // path omitted
	key : fs.readFileSync() // path omitted
});

// Create the WebSocket object
const wss = new WebSocket.Server({ server });

// Create MySQL (database) connection pool (details omitted)
const pool = mysql.createPool({
	port				: '/var/run/mysqld/mysqld.sock',
	host    			: 'localhost',
	user    			: '',
	password			: '',
	database			: '',
	multipleStatements	: true
});

// Using the JSON API to stringify and occasionally parse received data
function toJsonStr(data, parse = false)
{
	const jsonString = JSON.stringify(data);

	return (parse == true ? JSON.parse(jsonString) : jsonString);
}

function base64Data(data, task)
{
	const input = data.trim();

	switch(task)
	{
		case "decode": return Buffer.from(input, "base64").toString();
		case "encode": return Buffer.from(input).toString("base64");
		default: return `Error: unknown task - ${task}`;
	}
}

// Send back any relevant error messages
function logError(errorMessage, ws_link)
{
	ws_link.send(toJsonStr(["ERROR", errorMessage]));
	ws_link = null;
}

// Querying the MySQL database 
function queryDatabase(category, query_data, ws_conn)
{
	pool.getConnection((err, connection) =>
	{
		if (err)
		{
			console.error(err);
			logError(err, ws_conn);

			return;
		}

		connection.query(query_data, (err, rows) =>
		{
			connection.release(); // return the connection to pool

			if (err)
			{
				console.error(err);
				logError(err, ws_conn);

				return;
			}

			const result = JSON.parse(toJsonStr(rows));

			const categoryIndex = category.split(":");

			if (Array.isArray(result) && result.length > 0)
			{
				if (categoryIndex[1] != "NORETURN")
				{
					ws_conn.send(toJsonStr([categoryIndex.join("-"), result]));
				}
			}
		});
	});
}

// Remove quotes from string
function stripQuotes(data)
{
	return data.replace(/\'/g,"").replace(/\"/g,"");
}

// Remove slashes from string
function stripSlashes(data)
{
	return data.replace(/\//g, "").replace(/\\/g,"");
}

// Check if username is valid
function isUsernameValid(username)
{
	// This will be updated to use a regular expression in the future
	let isValid = true;	

	const acceptedChars = "1234567890-_abcdefghijklmnopqrstuvwxyzабвгдежзийклмнопрстуфхцчшщъьюя";
	
	for (let i = 0; i < username.length; ++i)
		if (!acceptedChars.includes(username.charAt(i).toLowerCase()))
			isValid = false;
	
	return isValid;
}

// Returns the required chatroom's name in the database
function getChatRoom(index)
{
	switch (index)
	{
		case "1": return "CarEngines";
		case "2": return "LivingInSydney";
		case "3": return "ScubaDivingInCairns";
		case "4": return "SurfingInByronBay";
		case "5": return "CarEngines_BG";
		case "6": return "LivingInSydney_BG";
		case "7": return "ScubaDivingInTropics_BG";
		case "8": return "JupiterSatellites_BG";
		default:  return false;
	}
}

// Checks if the input string contains prohibited language
function filterBadLingo(data, output = false)
{
	// restricted list - omitted; add your own
	const restricted = [
		"",
		"",
		""
	];

	if (output == false)
	{
		let isProfane = false;

		for (let i = 0; i < restricted.length; ++i)
			if (data.includes(restricted[i]))
				isProfane = true;

		return isProfane;
	}
	else if (output == true)
	{
		let input = data.split(" ");

		for (let i = 0; i < input.length; ++i)
			for (let j = 0; j < restricted.length; ++j)
				if (input[i].toLowerCase().includes(restricted[j]))
					input[i] = "*beep*";

		return input.join(" ");
	}
}

// Once the connection to the WebSocket client-side has been established...
wss.on('connection', function connection(ws, req)
{
	if (req.headers.origin == "https://projects.voyager89.net") // We will only accept requests originating from below-mentioned URL
	{
		// The incoming WebSocket message, and how we deal with it
		ws.on('message', function incoming(data)
		{
			if (data.includes(":"))
			{
				const message = data.split(":");

				if (Array.isArray(message) && message.length > 1)
				{
					// Here we check the username
					// accepted characters: 1234567890-_abcdefghijklmnopqrstuvwxyzабвгдежзийклмнопрстуфхцчшщъьюя
					// username minimum 3 characters, maximum 15 characters
					if (message.length == 2 && message[0].indexOf("Username") > -1 && message[1].length > 0)
					{
						const userName = stripSlashes(stripQuotes(message[1])).trim();

						if (userName.length < 3 || userName.length > 15 || !isUsernameValid(userName))
						{
							logError("Bad username or improper request!##Неправилно потребителско име!", ws);
						}
						else if (filterBadLingo(userName))
						{
							ws.send(toJsonStr(["checkUsernameAndInsert", 1, "No. Pick another one.##Не. Изберете си друго потребителско име."]));
						}
						else {
							let runQuery = "";

							switch (message[0])
							{
								case "updateUsername":
									runQuery = `UPDATE CurrentUsersOnline SET LastActive=NOW() WHERE UserName='${userName}';`;
								break;
								case "checkUsernameAndInsert":
									runQuery = `SELECT insertUserName('${userName}') AS InsertUserName;`;
								break;
							}

							queryDatabase(`${message[0]}:${userName}`, runQuery, ws);
						}
					}
					else if (message.length == 3 && message[0] == "request" && message[1].length > 0 && message[2].length > 0)
					{
						// Here we handle all other requests
						switch (message[1])
						{
							case "displayEraseCountdown":
								queryDatabase(`REQUEST:${message[1]}`, `SELECT ChatRoomErasureCountdown() AS TimeRemaining;`, ws);
							break;
							case "listAllUsers":
								queryDatabase(`REQUEST:${message[1]}`, `SELECT * FROM CurrentUsersOnline;`, ws);
							break;
						}
					}
					else if (message.length == 2 && message[0] == "loadChatRoom" && false != getChatRoom(message[1]))
					{
						// Here we fetch requested chatroom's messages
						queryDatabase(`CHATROOM:${message[1]}`, `SELECT * FROM ${getChatRoom(message[1])} ORDER BY MessageTimeDate ASC;`, ws);
					}
					else if (message.length == 3 && message[0].indexOf("postMessage-") == 0)
					{
						// Here we post messages to selected chatroom
						const currentTimestamp = Date.now();

						// Prevent chain-posting
						if (!lastMessageReceived || currentTimestamp - lastMessageReceived >= 200)
						{
							lastMessageReceived = currentTimestamp;

							const currentUser = (filterBadLingo(message[1]) ? "RudeUser" : message[1]); // Just in case someone circumnavigates client rules!
							const messageToPost = filterBadLingo(message[2], true).substring(0, 300); // Just in case anyone gets creative client-side!

							const chatRoomIndex = message[0].substring(message[0].indexOf("-") + 1, message[0].length);
							const chatRoom = getChatRoom(chatRoomIndex);

							if (currentUser.toLowerCase() != "v89" && messageToPost.length > 3 && chatRoom != false)
							{
								let postQuery = `INSERT INTO ${chatRoom}(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES('${currentUser}',`;
								postQuery += `NOW(),'${base64Data(messageToPost, "encode")}', '192.168.0.1');`;
								postQuery += `UPDATE CurrentUsersOnline SET LastActive=NOW() WHERE UserName='${currentUser}';`;

								queryDatabase(`POSTMESSAGE-${chatRoomIndex}`, postQuery, ws);
							}
						}
					}
				}
				else {
					logError("Request too short!##Заявление твърде кратко!", ws);
				}
			}
			else {
				logError("Bad request!##Неправилно заявление!", ws);
			}
		});
	}
	else {
		// Requests from unknown origins will be rejected
		ws.send(`Unauthorized: ${req.headers.origin} - URL: ${req.url}`);
		ws.close(1000, "WebSocket unavailable");
	}
});

server.listen(); // Set the server to listen to this port - omitted for obvious reasons