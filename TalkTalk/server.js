const fs = require("fs");
const https = require("https");
const mysql = require("mysql");
const WebSocket = require("ws");

const server = https.createServer( // we're using a secure websocket layer - not strictly necessary, but a good idea
{
	cert		: fs.readFileSync(''), // file.crt
	key			: fs.readFileSync('')  // keyfile.key
});

const wss = new WebSocket.Server({ server });

const pool = mysql.createPool({ // data omitted for obvious reasons
	port				: '/var/run/mysqld/mysqld.sock',
	host    			: '',
	user    			: '',
	password			: '',
	database			: '',
	multipleStatements	: true
});

function toJsonStr(data, parse = false)
{
	const jsonString = JSON.stringify(data);

	return (parse == true ? JSON.parse(jsonString) : jsonString);
}

function logError(errorMessage, ws_link)
{
	ws_link.send(toJsonStr(["ERROR", errorMessage]));
	ws_link = null;
}

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

		connection.query(query_data, (err, rows, fields) =>
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
				if (result[0].CheckCheck !== undefined)
				{
					// username checking
					if (result[0].CheckCheck == 1)
					{
						ws_conn.send(toJsonStr(["checkUsernameAndInsert", result[0].CheckCheck, "This username is taken!"]));
					}
					else {
						ws_conn.send(toJsonStr(["checkUsernameAndInsert", result[0].CheckCheck, categoryIndex[1]]));
					}
				}
				else if (result[0].Logged !== undefined)
				{
					ws_conn.send(toJsonStr(["checkUsername", result[0].Logged, categoryIndex[1]]));
				}
				else // everything else
				{
					if (categoryIndex[1] != "NORETURN")
					{
						ws_conn.send(toJsonStr([categoryIndex.join("-"), result]));
					}
				}
			}
		});
	});
}

function stripQuotes(data)
{
	return data.replace(/\'/g,"").replace(/\"/g,"");
}

function stripSlashes(data)
{
	return data.replace(/\//g, "").replace(/\\/g,"");
}

function isUsernameValid(username)
{
	// This will be updated to use a regular expression in the future
	let isValid = true;	

	const acceptedChars = "1234567890-_abcdefghijklmnopqrstuvwxyz";
	
	for (let i = 0; i < username.length; ++i)
		if (!acceptedChars.includes(username.charAt(i).toLowerCase()))
			isValid = false;
	
	return isValid;
}

function getChatRoom(index)
{
	switch (index)
	{
		case "1":
			return "CarEngines";
		case "2":
			return "LivingInSydney";
		case "3":
			return "ScubaDivingInCairns";
		case "4":
			return "SurfingInByronBay";
		default:
			return false;
	}
}

function runFunc(customFunction)
{
	if (typeof customFunction == "function")
		customFunction();
}

function filterBadLingo(data, output = false)
{
	// add your own list of off-colour lingo here
	const restricted = [
		"...",
		"...",
		"...",
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

wss.on('connection', function connection(ws)
{
	ws.on('message', function incoming(data)
	{
		if (data.includes(":"))
		{
			const message = data.split(":");
			
			// CHECK USERNAME
			// check username: checkUsername FerrariFan86 (no space or special characters allowed;
			// accepted characters: 1234567890-_abcdefghijklmnopqrstuvwxyz
			// username minimum 3 characters, maximum 15 characters

			if (Array.isArray(message) && message.length > 1)
			{
				if (message.length == 2 && message[0].includes("checkUsername") && message[1].length > 0) // CHECK USERNAME
				{
					/*
					console.log(Buffer.from('Hello World!').toString('base64'));
					Reverse (assuming the content you're decoding is a utf8 string):
					console.log(Buffer.from(b64Encoded, 'base64').toString());
					*/
					const userName = stripSlashes(stripQuotes(message[1])).trim();

					if (userName.length < 3 || userName.length > 15 || !isUsernameValid(userName))
					{
						logError("Bad username or improper request!", ws);
					}
					else if (filterBadLingo(userName))
					{
						ws.send(toJsonStr(["checkUsernameAndInsert", 1, "No. Pick another one."]));
					}
					else {
						let runQuery = "";

						switch (message[0]) 
						{
							case "checkUsername":
								runQuery = `SELECT IF(EXISTS(SELECT * FROM CurrentUsersOnline WHERE UserName='${userName}'), 1, 0) AS Logged;`;
							break;
							case "checkUsernameAndInsert":
								runQuery = `SELECT IF(isUserLogged('${userName}'), 1, 0) AS CheckCheck;`;
							break;
						}

						queryDatabase(`USERNAME:${userName}`, runQuery, ws);
					}
				}
				else if (message.length == 3 && message[0] == "request" && message[1].length > 0 && message[2].length > 0) // HANDLE REQUESTS
				{
					switch (message[1])
					{
						case "displayEraseCountdown":
							queryDatabase(`REQUEST:${message[1]}`, `SELECT ChatRoomErasureCountdown() AS TimeRemaining;`, ws);
						break;
						case "listAllUsers":
							queryDatabase(`REQUEST:${message[1]}`, `SELECT * FROM CurrentUsersOnline;`, ws);
						break;
						case "logOff":
							const user = message[2].toLowerCase() == "v89" ? "NonV89" : message[2];
							queryDatabase(`REQUEST:NORETURN`, `DELETE FROM CurrentUsersOnline WHERE UserName='${user}';`, ws);
						break;
					}
				}
				else if (message.length == 2 && message[0] == "loadChatRoom" && false != getChatRoom(message[1])) // FETCH CHATROOM MESSAGES
				{
					queryDatabase(`CHATROOM:${message[1]}`, `SELECT * FROM ${getChatRoom(message[1])};`, ws);
				}
				else if (message.length == 3 && message[0].includes("postMessage-")) // POST MESSAGE TO CHATROOM
				{
					// ["postMessage-1", "user", "message"] (postMessage-{chatroom number}
					const currentUser = (filterBadLingo(message[1]) ? "RudeUser" : message[1]); // Just in case someone circumnavigates client rules!
					const messageToPost = filterBadLingo(message[2], true).substring(0, 300); // Just in case anyone gets creative client-side!

					const chatRoomIndex = message[0].substring(message[0].indexOf("-")+1, message[0].length);
					const chatRoom = getChatRoom(chatRoomIndex);

					if (currentUser.toLowerCase() != "v89" && messageToPost.length > 3 && chatRoom != false)
					{
						let postQuery = `INSERT INTO ${chatRoom}(MessageUser,MessageTimeDate,MessageData,MessageUserIP) VALUES('${currentUser}',`;
						postQuery += `NOW(),'${Buffer.from(messageToPost).toString("base64")}', '192.168.0.1');`;
						postQuery += `UPDATE CurrentUsersOnline SET LastActive=NOW() WHERE UserName='${currentUser}';`;

						queryDatabase(`POSTMESSAGE-${chatRoomIndex}`, postQuery, ws);
					}
				}
			}
			else {
				logError("Request too short!", ws);
			}
		}
		else {
			logError("Bad request!", ws);
		}
	});
});

server.listen(/*port number goes here!*/);