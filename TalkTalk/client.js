let appLang = "EN";

let documentWidth = 0;
let loadNewMessages = null;

let isUnavailable = false;

let scrollChatWindow = true;

let currentUser = null;
let userChecking = false;


let chatRestarts = 0;

let currentChatRoom = null;
let currentChatRoomIndex = 1;

// Chatroom storage
// English rooms
let chatroom_1 = [];
let chatroom_2 = [];
let chatroom_3 = [];
let chatroom_4 = [];

// Bulgarian rooms
let chatroom_5 = [];
let chatroom_6 = [];
let chatroom_7 = [];
let chatroom_8 = [];

let ServerProcess = new WebSocket("wss://devcore-voyager89.net:80");
let output = null;

// English by default; other languages must be set
const urlHash = window.location.hash;

if (urlHash.length > 0 && urlHash == "#bg")
{
	appLang = "BG";
	currentChatRoomIndex = 5;
}

// Query and return an object or a group of objects
function getObject(identifier, getAll = false)
{
	return getAll ? document.querySelectorAll(identifier) : document.querySelector(identifier);
}

function ChatData(data)
{
	if (ServerProcess.readyState == 1)
	{
		ServerProcess.send(data);
	}
}

// Return the username currently logged on
function getUserLogName()
{
	return currentUser;
}

// Send request to download the requested chatroom
function loadChatRoom(element, index)
{
	currentChatRoomIndex = index;
	let chatRoomLinks = getObject("a", true);

	for (let j = 0; j < chatRoomLinks.length; ++j)
		if (chatRoomLinks[j].className.includes(" selected"))
			chatRoomLinks[j].className = chatRoomLinks[j].className.replace(" selected", "");

	element.className += " selected";

	ChatData(`loadChatRoom:${index}`);
}

function getCurrentChatRoom()
{
	return currentChatRoomIndex;
}

// Ensure logged user is active
function runUserLogCheck()
{
	if (!userChecking)
	{	
		userChecking = window.setInterval(function()
		{
			if (getUserLogName() != null)
			{
				ChatData(`updateUsername:${getUserLogName()}`);
			}
		}, 5000);
	}
}

function setWebSocketEvents(initialStart = false)
{
	if (!initialStart)
	{
		chatRestarts++;
	}

	ServerProcess.onopen = function()
	{
		if (chatRestarts)
		{
			console.log("WebSocket restarted.");
		}
		else {
			console.log("WebSocket open");
		}
	};

	ServerProcess.onclose = function (event)
	{
		console.log("WebSocket closed.");

		if (
			event.code == 1000 && (
				event.reason.includes("Уеб-контакта") ||
				event.reason.includes("WebSocket unavailable")
			)
		)
		{
			window.clearInterval(loadNewMessages);
			window.clearInterval(userChecking);

			if (isUnavailable == false)
			{
				let question = "";

				switch (appLang)
				{
					case "BG":
						question = "ВНИМАНИЕ:\n\nВръзката със сървъра е прекъсната и тази програма спира да работи.\n\nАко искате да я използвате отново моля освежете тази страница (бутон F5).";
					break;
					case "EN":
						question = "WARNING:\n\nThis application has experienced an unexpected interruption and has shut down.\n\nIf you wish to try again, click OK to reload the page.";
					break;
				}

				if (window.confirm(question))
				{
					if (getUserLogName() != null)
						currentUser = null;

					window.location.reload();
				}
			}
		}
		else {
			console.log("WebSocket restarting.");

			window.setTimeout(function()
			{
				ServerProcess = new WebSocket("wss://devcore-voyager89.net:80");
				window.setWebSocketEvents();
			}, 2000);
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
		console.log(evt);
		console.log("Restarting websocket...");

		window.setTimeout(function()
		{
			ServerProcess = new WebSocket("wss://devcore-voyager89.net:80");
		}, 1000);
	};
}

// Send message to server, whether to post or to log on
function sendMessage(event, element, category)
{
	let serverQuery = "";

	if (event && element && category)
	{
		if (event.key == "Enter" && typeof element == "object" && category.length > 0)
		{
			const inputValue = element.value.trim();

			switch (category)
			{
				case "checkUsernameAndInsert":
					if (inputValue.length > 3 && getUserLogName() == null)
					{
						serverQuery = `checkUsernameAndInsert:${element.value.trim()}`;
					}
					else {
						getObject(".error").innerText = inputValue.length < 4 ? "Your username must be at least 4 characters long!" : "You're already logged on!";
					}
				break;
				case "postMessage":
					if (inputValue.length > 0 && currentUser != null)
					{
						serverQuery = `postMessage-${getCurrentChatRoom()}:${currentUser}:${element.value.trim()}`;
					}
				break;
			}

			if (serverQuery.length > 0)
			{
				ChatData(serverQuery);
			
				window.setTimeout(function()
				{
					element.value = "";
				}, 100);
			}
		}
	}
}

// When is the next chatroom clean up?
function erasureCountdown()
{
	ChatData(`request:displayEraseCountdown:none`);

	window.setInterval(function()
	{
		ChatData(`request:displayEraseCountdown:none`);
	}, 30000); // every 30 seconds
}

// Deal with output by server
function analyzeOutput(data)
{
	const userData = data[0];

	if (userData.includes("checkUsernameAndInsert-"))
	{
		if (Number(data[1][0].InsertUserName) == 0)
		{
			let notice = "";

			switch (appLang)
			{
				case "BG":
					notice = "ВНИМАНИЕ:\n\nПоради бездействие за повече от 5 минути вие сте автоматично отписан/а.\n\nНатиснете OK за да започнете програмата отново.";
				break;
				case "EN":
					notice = "WARNING:\n\nDue to inactivity for more than 5 minutes you have been automatically logged off.\n\nPress OK to restart this application.";
				break;
			}

			window.alert(notice);
			window.location.reload();
		}
		else if (Number(data[1][0].InsertUserName) == 1)
		{
			currentUser = userData.split("-")[1];

			window.runUserLogCheck();
			window.erasureCountdown();

			getObject(".error").innerText = "";
			getObject(".logScreen").style.display = "none";
			getObject(".chatWindow").style.display = "block";
			getObject(".notice").style.visibility = "visible";
			getObject("#lnk_logoff_right").style.visibility = "visible";
			getObject(".textInput").innerHTML = `<textarea maxlength="100" onfocus="scrollChatWindow=true;" onkeydown="window.sendMessage(event, this, 'postMessage');" placeholder="Type in your message here..." title="Press Return to post"></textarea>`;

			ChatData(`request:listAllUsers:none`);
			ChatData(`loadChatRoom:${getCurrentChatRoom()}`);
		}
	}
	else {
		switch (userData.toLowerCase())
		{
			case "chatroom-1":
				chatroom_1 = [];

				for (let i = 0; i < data[1].length; ++i)
					chatroom_1.push(data[1][i]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-2":
				chatroom_2 = [];

				for (let j = 0; j < data[1].length; ++j)
					chatroom_2.push(data[1][j]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-3":
				chatroom_3 = [];

				for (let k = 0; k < data[1].length; ++k)
					chatroom_3.push(data[1][k]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-4":
				chatroom_4 = [];

				for (let l = 0; l < data[1].length; ++l)
					chatroom_4.push(data[1][l]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-5":
				chatroom_5 = [];

				for (let l = 0; l < data[1].length; ++l)
					chatroom_5.push(data[1][l]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-6":
				chatroom_6 = [];

				for (let l = 0; l < data[1].length; ++l)
					chatroom_6.push(data[1][l]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-7":
				chatroom_7 = [];

				for (let l = 0; l < data[1].length; ++l)
					chatroom_7.push(data[1][l]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "chatroom-8":
				chatroom_8 = [];

				for (let l = 0; l < data[1].length; ++l)
					chatroom_8.push(data[1][l]);

				loadChatData(Number(data[0].substring(data[0].indexOf("-") + 1, data[0].length)));
			break;
			case "postmessage-1":
			case "postmessage-2":
			case "postmessage-3":
			case "postmessage-4":
				if (data[1].affectedRows !== undefined && data[1].affectedRows == 0)
				{
					let reason = ``;

					switch (appLang)
					{
						case "BG":
							reason = `ВНИМАНИЕ:\n\nСъобщението ви не може да бъде публикувано.`;
						break;
						case "EN":
							reason = `WARNING:\n\nMessage posting failed.`;
						break;
					}

					if (data[1].message !== undefined && data[1].message.length > 0)
					{
						switch (appLang)
						{
							case "BG":
								reason += `\nСъобщение: ${data[1].message}`;
							break;
							case "EN":
								reason += `\nMessage: ${data[1].message}`;
							break;
						}
					}

					window.alert(reason);
				}
			break;
			case "request-displayerasecountdown":
				const timeData = data[1][0].TimeRemaining.split(":");
				const timeLabels = [(appLang == "BG") ? ["ЧАС", "ЧАСА"] : ["HOUR", "HOURS"], (appLang == "BG") ? ["МИНУТА", "МИНУТИ"] : ["MINUTE", "MINUTES"]];

				getObject("#erase").innerText = `${timeData[0]} ${Number(timeData[0] == 1) ? timeLabels[0][0] : timeLabels[0][1]} AND ${timeData[1]} ${Number(timeData[1]) == 1 ? timeLabels[1][0] : timeLabels[1][1]}.`;
			break;
			case "request-listallusers":
				let loggedUser = '';
				let onlineUsers = [];
				
				switch (appLang)
				{
					case "BG":
						loggedUser = `вие`;
					break;
					case "EN":
						loggedUser = `you`;
					break;
				}

				for (let m = 0; m < data[1].length; ++m)
				{
					const username = data[1][m].UserName;
					let isSelf = getUserLogName() == username ? ` (${loggedUser})` : ``;

					onlineUsers.push(username + isSelf);
				}

				getObject("#onlineUserListMOBILE").innerHTML = onlineUsers.join("<br/>\n");
				getObject("#onlineUserListDESKTOP").innerHTML = onlineUsers.join("<br/>\n");
			break;
			case "error":
				let message = "";

				if (data[1].includes("UNAVAILABLE:"))
				{
					let closedMsg = "";

					isUnavailable = true;
					message = data[1].split(":")[1];

					if (message.includes("##"))
					{
						message = message.replace("##", "\n---\n");
					}

					switch (appLang)
					{
						case "BG":
							closedMsg = "Уеб-контакта е в ремонт.";
						break;
						case "EN":
							closedMsg = "WebSocket unavailable due to maintenance.";
						break;
					}

					if (ServerProcess.readyState == 1)
					{
						ServerProcess.close(1000, closedMsg); // ServerProcess.close(1000, "WebSocket unavailable ");
					}
				}
				else
				{
					if (data[1].includes("##"))
					{
						message = (appLang == "BG" ? data[1].split("##")[1] : data[1].split("##")[0]);
					}
					else {
						message = data[1];
					}
				}

				window.alert(message);
			break;
		}
	}
}

function doLogOff()
{
	let question = "";

	switch (appLang)
	{
		case "BG":
			question = "Сигурни ли сте че искате да се изпишете?";
		break;
		case "EN":
			question = "Are you sure you want to log off?";
		break;
	}

	if (getUserLogName() && window.confirm(question))
	{
		currentUser = null;

		window.clearInterval(loadNewMessages);
		window.clearInterval(userChecking);

		window.location.reload();
	}
}

function showAboutBox()
{
	let about = "";

	switch (appLang)
	{
		case "BG":
			about = 'Чат Програма „Ток-Ток"\nВерсия 1.0 - Публикувана на 7 Юли 2020\n\nСъздадена от Пътешественик 89 с JavaScript, Node JS, MySQL.';
		break;
		case "EN":
			about = 'Chatting Application "Talk-Talk"\nVersion 1.0 - Released on 07 July 2020\n\nWritten by Voyager 89 in JavaScript, Node JS, MySQL.';
		break;
	}

	if (documentWidth < 500)
	{
		window.alert(about);
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
	let chatRoomData = "";
	let currentChatRoom = [];

	currentChatRoomIndex = chatRoom;

	switch (chatRoom)
	{
		case 1: currentChatRoom = [...chatroom_1]; break;
		case 2: currentChatRoom = [...chatroom_2]; break;
		case 3: currentChatRoom = [...chatroom_3]; break;
		case 4: currentChatRoom = [...chatroom_4]; break;
		case 5: currentChatRoom = [...chatroom_5]; break;
		case 6: currentChatRoom = [...chatroom_6]; break;
		case 7: currentChatRoom = [...chatroom_7]; break;
		case 8: currentChatRoom = [...chatroom_8]; break;
	}

	for (let i = 0; i < currentChatRoom.length; ++i)
	{
		const msg = currentChatRoom[i];
		const isOwnMessage = msg.MessageUser.toLowerCase() == currentUser.toLowerCase() ? "messageSelf" : "message";

		chatRoomData += `
	<div class="${isOwnMessage}">
		<div class="position">
			<div class="poster">
				<strong>${msg.MessageUser}</strong> - <span class="postTime">${new Date(msg.MessageTimeDate).toLocaleDateString("au")}</span>
			</div>
			<div class="posterData">
				${window.atob(msg.MessageData)}
			</div>
		</div>
	</div>`;
	}

	if (chatRoomData.length > 0)
	{
		const convoBox = getObject(".conversationBox");

		convoBox.innerHTML = chatRoomData;

		if (scrollChatWindow) // && window.screen.width > 1000)
		{
			convoBox.scrollTo(0, convoBox.scrollHeight);
		}

		if (loadNewMessages == null)
		{
			loadNewMessages = window.setInterval(function()
			{
				ChatData(`request:listAllUsers:none`);
				ChatData(`loadChatRoom:${getCurrentChatRoom()}`);
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
		if (Number(group.style.opacity) == 1)
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
	window.setWebSocketEvents(true);

	window.setTimeout(function()
	{
		const today = new Date();

		//getObject("footer").innerHTML += `<hr/>2000 - ${today.getFullYear()} by Voyager 89`;

		window.alignAboutBox(document.body.offsetWidth, document.body.offsetHeight);

		getObject(".conversationBox").setAttribute("style", `height:${window.screen.height/2}px;`);

		if (appLang == "BG")
		{
			document.title = "„ТокТок“ - V89 Чат Програма";
			
			if (getObject("span.title").innerText.toLowerCase() == "online users")
				getObject("span.title").innerText = "Потребители на линия";

			const chatRoomLinks = getObject("a.room", true);

			chatRoomLinks[0].innerText = "Автомобилни Двигатели";
			chatRoomLinks[1].innerText = "Живеене в Сидни";
			chatRoomLinks[2].innerText = "Гмуркане в Тропика";
			chatRoomLinks[3].innerText = "Спътници на Юпитер";
			chatRoomLinks[4].innerText = "Автомобилни Двигатели";
			chatRoomLinks[5].innerText = "Живеене в Сидни";
			chatRoomLinks[6].innerText = "Гмуркане в Тропика";
			chatRoomLinks[7].innerText = "Спътници на Юпитер";

			chatRoomLinks[0].setAttribute("onclick", "window.loadChatRoom(this, 5); return false;");
			chatRoomLinks[1].setAttribute("onclick", "window.loadChatRoom(this, 6); return false;"); //"Живеене в Сидни"
			chatRoomLinks[2].setAttribute("onclick", "window.loadChatRoom(this, 7); return false;"); //"Гмуркане в Тропика"
			chatRoomLinks[3].setAttribute("onclick", "window.loadChatRoom(this, 8); return false;"); //"Спътници на Юпитер"
			chatRoomLinks[4].setAttribute("onclick", "window.loadChatRoom(this, 5); return false;"); //"Автомобилни Двигатели"
			chatRoomLinks[5].setAttribute("onclick", "window.loadChatRoom(this, 6); return false;"); //"Живеене в Сидни"
			chatRoomLinks[6].setAttribute("onclick", "window.loadChatRoom(this, 7); return false;"); //"Гмуркане в Тропика"
			chatRoomLinks[7].setAttribute("onclick", "window.loadChatRoom(this, 8); return false;"); //"Спътници на Юпитер"

			//getObject("footer").innerHTML += `<hr/>2000 - ${today.getFullYear()} от Voyager 89`;

			getObject("h1").innerText = "„Ток-Ток“ Чат Програма";
			getObject("h2").innerText = "Избери потребителско име";
			
			if (getObject("textarea"))
			{
				getObject("textarea").setAttribute("placeholder", "Вашето съобщение...");
			}

			getObject("span.title").innerText = "Чат Групи";
			getObject("#lnk_about").innerText = "Относно";
			getObject("#lnk_logoff_right").innerText = "Изпиши се";
			getObject("input[type='text']").setAttribute("placeholder", "Потребителско име...");
			getObject("#logOnMessage").innerHTML = "ТОВА Е ОБЩЕСТВЕНА ЧАТ ПРОГРАМА.<br/><br/>ПОТРЕБИТЕЛСКОТО ИМЕ КОЕТО ИЗБЕРЕТЕ СЕГА<br/>МОЖЕ ДА БЪДЕ ВЗЕТО ОТ НЯКОЙ ДРУГ В БЪДЕЩЕ.";
			getObject("section.about").innerHTML = `Чат Програма <strong>„Ток-Ток“</strong><br/>Версия 1.0 - Публикувана на 7 Юли 2020<br/><br/>Създадена с <em>JavaScript</em>, <em>Node JS</em>, <em>MySQL</em><br/><br/><a href="#" onclick="this.parentElement.style.visibility='hidden'; return false;">OK</a>`;
			getObject("#toErase").innerHTML = `ВСИЧКИ ЧАТ РАЗГОВОРИ СЕ ИЗТРИВАТ ВЕДНЪЖ ВСЕКИ 24 ЧАСА; СЛЕДВАЩОТО ИЗТРИВАНЕ ЩЕ БЪДЕ СЛЕД`;
		}
	}, 100);
};