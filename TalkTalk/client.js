let documentWidth = 0;
let loadNewMessages = null;

let errorFlag = 0; // If this is enabled, the WebSocket is inoperable - basically, the entire application is dead.
let isLoggingOff = false;
let isUnavailable = false;

let serverBusy = false;
let scrollChatWindow = true;
let currentUser = "";
let currentChatRoom = null;
let currentChatRoomIndex = 1;

let chatroomCarEngines = [];
let chatroomLivingInSydney = [];
let chatroomScubaDivingInCairns = [];
let chatroomSurfingInByronBay = [];

function getObject(identifier)
{
	return document.querySelector(identifier);
}

function getUserLogName()
{
	return sessionStorage.getItem("TalkTalkChatLogName");
}

function loadChatRoom(element, index)
{
	currentChatRoomIndex = index;
	let chatRoomLinks = document.getElementsByTagName("a");

	for (let j = 0; j < chatRoomLinks.length; ++j)
		if (chatRoomLinks[j].className.includes(" selected"))
			chatRoomLinks[j].className = chatRoomLinks[j].className.replace(" selected", "");

	element.className += " selected";

	ServerProcess.send(`loadChatRoom:${index}`);
}

function getCurrentChatRoom()
{
	return currentChatRoomIndex;
}

function runUserLogCheck()
{
	window.setInterval(function()
	{
		if (getUserLogName() != null)
		{
			ServerProcess.send(`checkUsername:${getUserLogName()}`);
		}
	}, 1000);
}

function sendMessage(event, element, category)
{
	let serverQuery = "";
	const currentUser = getUserLogName();

	if (event && element && category)
	{
		if (event.key == "Enter" && typeof element == "object" && category.length > 0)
		{
			switch (category)
			{
				case "checkUsernameAndInsert":
					serverQuery = `checkUsernameAndInsert:${element.value.trim()}`;
				break;
				case "postMessage":
					if (element.value.trim().length > 0 && currentUser != null)
					{
						serverQuery = `postMessage-${getCurrentChatRoom()}:${currentUser}:${element.value.trim()}`;
					}
				break;
			}

			if (serverQuery.length > 0)
			{
				ServerProcess.send(serverQuery);
			
				window.setTimeout(function()
				{
					element.value = "";
				}, 100);
			}
		}
	}
}

function erasureCountdown()
{
	window.setInterval(function()
	{
		ServerProcess.send(`request:displayEraseCountdown:none`);
	}, 60000); // 1 minute
}

function analyzeOutput(data)
{
	switch (data[0].toLowerCase())
	{
		case "checkusername":
			if (window.parseInt(data[1]) == 0)
			{
				sessionStorage.removeItem("TalkTalkChatLogName");
				window.alert("WARNING:\n\nDue to inactivity for more than 30 minutes you have been automatically logged off.\n\nPress OK to restart this application.");
				window.location.reload();
			}
			else if (window.parseInt(data[1]) == 1)
			{
				currentUser = data[2];

				window.erasureCountdown();
				getObject(".error").innerText = "";
				getObject(".logScreen").style.display = "none";
				getObject(".chatWindow").style.display = "block";
				getObject(".notice").style.visibility = "visible";
				getObject("#lnk_logoff_right").style.visibility = "visible";

				ServerProcess.send(`request:listAllUsers:none`);
				ServerProcess.send(`loadChatRoom:${getCurrentChatRoom()}`);
			}
		break;
		case "checkusernameandinsert":
			if (window.parseInt(data[1]) == 1)
			{
				getObject(".error").innerText = data[2];
			}
			else
			{
				currentUser = data[2];

				window.erasureCountdown();
				window.setSession(currentUser);								
				window.runUserLogCheck();
				
				getObject(".error").innerText = "";
				getObject(".logScreen").style.display = "none";
				getObject(".chatWindow").style.display = "block";
				getObject(".notice").style.visibility = "visible";
				
				getObject("#lnk_logoff_right").style.visibility = "visible";

				ServerProcess.send(`request:listAllUsers:none`);
				ServerProcess.send(`loadChatRoom:${getCurrentChatRoom()}`);
			}
		break;
		case "chatroom-1":
			chatroomCarEngines = [];

			for (let i = 0; i < data[1].length; ++i)
				chatroomCarEngines.push(data[1][i]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-2":
			chatroomLivingInSydney = [];

			for (let j = 0; j < data[1].length; ++j)
				chatroomLivingInSydney.push(data[1][j]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-3":
			chatroomScubaDivingInCairns = [];

			for (let k = 0; k < data[1].length; ++k)
				chatroomScubaDivingInCairns.push(data[1][k]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-4":
			chatroomSurfingInByronBay = [];

			for (let l = 0; l < data[1].length; ++l)
				chatroomSurfingInByronBay.push(data[1][l]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "postmessage-1":
		case "postmessage-2":
		case "postmessage-3":
		case "postmessage-4":
			if (data[1].affectedRows !== undefined && data[1].affectedRows == 0)
			{
				let reason = `WARNING:\n\nMessage posting failed.`;

				if (data[1].message !== undefined && data[1].message.length > 0)
					reason += `\nMessage: ${data[1].message}`;

				window.alert(reason);
			}
		break;
		case "request-displayerasecountdown":
			getObject("#erase").innerText = data[1][0].TimeRemaining;
		break;
		case "request-listallusers":
			let onlineUsers = [];

			for (let m = 0; m < data[1].length; ++m)
			{
				const username = data[1][m].UserName;
				let isSelf = getUserLogName() == username ? ' (YOU)' : '';

				onlineUsers.push(username + isSelf);
			}

			getObject("#onlineUserListMOBILE").innerHTML = onlineUsers.join("<br/>\n");
			getObject("#onlineUserListDESKTOP").innerHTML = onlineUsers.join("<br/>\n");
		break;
		case "error":
			let message = "";

			if (data.includes("UNAVAILABLE:"))
			{
				isUnavailable = true;
				message = data[1].split(":")[1];
				ServerProcess.close(1000, "WebSocket unavailable due to maintenance.");
			}
			else
			{
				message = data[1];
			}

			window.alert(message);
		break;
	}
}

function doLogOff()
{
	if (getUserLogName() && window.confirm("Are you sure you want to log off?"))
	{
		isLoggingOff = true;
		sessionStorage.removeItem("TalkTalkChatLogName");
		ServerProcess.send(`request:logOff:${currentUser}`);
		window.location.reload();
	}
}

function setSession(username)
{
	sessionStorage.setItem("TalkTalkChatLogName", username);

	window.setInterval(function()
	{
		if (getUserLogName() == null)
		{
			window.location.reload();
		}
	}, 500);
}

function showAboutBox()
{
	if (documentWidth < 500)
	{
		window.alert(`Chatting Application "Talk-Talk"\nVersion 1.0 - Released on 07 July 2020\n\nWritten in JavaScript, Node JS, MySQL.`);
	}
	else {
		getObject(".about").style.visibility = "visible";
	}
}

function hideChatGroups()
{
	const group = getObject(".chatGroupsMOBILE");
	
	const hide = window.setInterval(function()
	{
		if (group.style.opacity == 0)
		{
			group.style.visibility = "hidden";
			window.clearInterval(hide);
		}
		else {
			group.style.opacity -= 0.25;
		}
	}, 50);
}

function loadChatData(chatRoom)
{
	serverBusy = false;

	let chatRoomData = "";
	let currentChatRoom = [];

	currentChatRoomIndex = chatRoom;

	switch (chatRoom)
	{
		case 1: currentChatRoom = [...chatroomCarEngines]; 				break;
		case 2: currentChatRoom = [...chatroomLivingInSydney]; 			break;
		case 3: currentChatRoom = [...chatroomScubaDivingInCairns]; 	break;
		case 4: currentChatRoom = [...chatroomSurfingInByronBay]; 		break;
	}

	for (let i = 0; i < currentChatRoom.length; ++i)
	{
		const msg = currentChatRoom[i];

		chatRoomData += `
	<div class="message">
		<div class="poster">
			<strong>${msg.MessageUser}</strong> - <span class="postTime">${msg.MessageTimeDate}</span>
		</div>
		<div class="posterData">
			${atob(msg.MessageData)}
		</div>
	</div>`;
	}

	if (chatRoomData.length > 0)
	{
		const convoBox = getObject(".conversationBox");

		convoBox.innerHTML = chatRoomData;

		if (scrollChatWindow && window.screen.width > 1000)
		{
			convoBox.scrollTo(0, convoBox.children.length * convoBox.children[0].clientHeight);
		}

		if (loadNewMessages == null)
		{
			loadNewMessages = window.setInterval(function()
			{
				if (!serverBusy)
				{
					serverBusy = true;
					ServerProcess.send(`request:listAllUsers:none`);
					ServerProcess.send(`loadChatRoom:${getCurrentChatRoom()}`);
				}
			}, 1000);
		}
	}
}

function showChatGroups()
{
	const group = getObject(".chatGroupsMOBILE");
	
	group.style.visibility = "visible";	
	const show = window.setInterval(function()
	{
		if (window.parseInt(group.style.opacity) == 1)
		{
			window.clearInterval(show);
		}
		else {
			let opacityIncrement = window.parseFloat(group.style.opacity) + 0.25;
			group.style.opacity = opacityIncrement;
		}
	}, 50);
}

function alignAboutBox(docWidth, docHeight)
{
	const boxWidth = 400;
	const boxHeight = 200;
	const aboutBox = getObject(".about"); // 400px wide, 200px high
	
	const positionX = (docWidth / 2) - (boxWidth / 2) + "px";
	const positionY = (docHeight / 2) - (boxHeight / 2) + "px";
	
	aboutBox.style.left = positionX;
	aboutBox.style.top = positionY;
}

window.onload = function()
{
	const today = new Date();
	getObject("footer").innerHTML += `<hr/>2000 - ${today.getFullYear()} by Voyager 89`;
	
	window.alignAboutBox(document.body.offsetWidth, document.body.offsetHeight);
};

const ServerProcess = new WebSocket("wss://devcore-voyager89.net:80");
let output = null;

ServerProcess.onopen = function (evt)
{
	console.log("WebSocket open");

	//----LOAD CHAT if user logged on
	if (getUserLogName() != null)
	{
		ServerProcess.send(`checkUsername:${getUserLogName()}`);
	}
};

ServerProcess.onclose = function (evt)
{
	console.log("WebSocket closed.");
	//console.log(evt.data);
	errorFlag = 1;

	window.clearInterval(loadNewMessages);

	if (isLoggingOff == false && isUnavailable == false)
	{
		if (window.confirm("WARNING:\n\nThis application has experienced an unexpected interruption and has shut down.\n\nIf you wish to try again, click OK to reload the page."))
		{
			if (getUserLogName() != null)
				sessionStorage.removeItem("TalkTalkChatLogName");

			window.location.reload();
		}
	}
};

ServerProcess.onmessage = function (evt)
{
	//console.log("WebSocket message: ");
	//console.log(evt);
	output = JSON.parse(evt.data);

	window.analyzeOutput(JSON.parse(evt.data));
};

ServerProcess.onerror = function (evt)
{
	console.log("WebSocket error: ");
	console.log(evt.data);
	errorFlag = 1;

	window.clearInterval(window.loadNewMessages);

	if (isLoggingOff == false && isUnavailable == false)
	{
		window.alert("WARNING:\n\nThis application has experienced an unexpected interruption and will now be restarted.\n\nPress OK to restart, or Cancel to exit.");

		if (getUserLogName() != null)
		{
			sessionStorage.removeItem("TalkTalkChatLogName");
		}
	}

	window.location.reload();
};