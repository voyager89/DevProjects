let appLang = "EN";

let documentWidth = 0;
let loadNewMessages = null;

let errorFlag = 0; // If this is enabled, the WebSocket is inoperable - basically, the entire application is dead.

let isLoggingOff = false;
let isUnavailable = false;

let serverBusy = false;
let scrollChatWindow = true;

let currentUser = "";
let userChecking = false;

let chatErasure = false;
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

// Return the username currently logged on
function getUserLogName()
{
	return sessionStorage.getItem("TalkTalkChatLogName");
}

// Send request to download the requested chatroom
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

// Ensure logged user is active
function runUserLogCheck()
{
	if (!userChecking)
	{	
		userChecking = true;

		window.setInterval(function()
		{
			if (getUserLogName() != null)
			{
				ServerProcess.send(`checkUsername:${getUserLogName()}`);
			}
		}, 1000);
	}
}

// Send message to server, whether to post or to log on
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

// When is the next chatroom clean up?
function erasureCountdown()
{
	if (!chatErasure)
	{
		chatErasure = true;

		window.setInterval(function()
		{
			ServerProcess.send(`request:displayEraseCountdown:none`);
		}, 60000); // 1 minute
	}
}

// Deal with output by server
function analyzeOutput(data)
{
	switch (data[0].toLowerCase())
	{
		case "checkusername":
			if (window.parseInt(data[1]) == 0)
			{
				sessionStorage.removeItem("TalkTalkChatLogName");

				let notice = "";
				switch (appLang)
				{
					case "BG":
						notice = "ВНИМАНИЕ:\n\nПоради бездействие за повече от 30 минути вие сте автоматично отписан/а.\n\nНатиснете OK за да започнете програмата отново.";
					break;
					case "EN":
						notice = "WARNING:\n\nDue to inactivity for more than 30 minutes you have been automatically logged off.\n\nPress OK to restart this application.";
					break;
				}

				window.alert(notice);
				window.location.reload();
			}
			else if (window.parseInt(data[1]) == 1)
			{
				currentUser = data[2];

				window.runUserLogCheck();
				window.erasureCountdown();
				window.setSession(currentUser);

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
				const output = data[2].split("##");
				getObject(".error").innerText = (appLang == "BG" ? output[1] : output[0]);
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
			chatroom_1 = [];

			for (let i = 0; i < data[1].length; ++i)
				chatroom_1.push(data[1][i]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-2":
			chatroom_2 = [];

			for (let j = 0; j < data[1].length; ++j)
				chatroom_2.push(data[1][j]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-3":
			chatroom_3 = [];

			for (let k = 0; k < data[1].length; ++k)
				chatroom_3.push(data[1][k]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-4":
			chatroom_4 = [];

			for (let l = 0; l < data[1].length; ++l)
				chatroom_4.push(data[1][l]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-5":
			chatroom_5 = [];

			for (let l = 0; l < data[1].length; ++l)
				chatroom_5.push(data[1][l]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-6":
			chatroom_6 = [];

			for (let l = 0; l < data[1].length; ++l)
				chatroom_6.push(data[1][l]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-7":
			chatroom_7 = [];

			for (let l = 0; l < data[1].length; ++l)
				chatroom_7.push(data[1][l]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
		break;
		case "chatroom-8":
			chatroom_8 = [];

			for (let l = 0; l < data[1].length; ++l)
				chatroom_8.push(data[1][l]);
			
			loadChatData(window.parseInt(data[0].substring(data[0].indexOf("-")+1, data[0].length)));
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
			getObject("#erase").innerText = data[1][0].TimeRemaining;
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

			if (data.includes("UNAVAILABLE:"))
			{
				let closedMsg = "";
				isUnavailable = true;
				message = data[1].split(":")[1];

				switch (appLang)
				{
					case "BG":
						closedMsg = "Уеб-контакта е в ремонт.";
					break;
					case "EN":
						closedMsg = "WebSocket unavailable due to maintenance.";
					break;
				}

				ServerProcess.close(1000, closedMsg);
			}
			else
			{
				if (data[1].includes("##"))
					message = (appLang == "BG" ? data[1].split("##")[1] : data[1].split("##")[0]);
				else
					message = data[1];
			}

			window.alert(message);
		break;
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
		isLoggingOff = true;
		sessionStorage.removeItem("TalkTalkChatLogName");
		ServerProcess.send(`request:logOff:${currentUser}`);
		window.location.reload();
	}
}

function setSession(username)
{
	sessionStorage.setItem("TalkTalkChatLogName", username);
}

function showAboutBox()
{
	let about = "";
	switch (appLang)
	{
		case "BG":
			about = 'Чат Програма „Ток-Ток"\nВерсия 1.0 - Публикувана на 7 Юли 2020\n\nСъздадена с JavaScript, Node JS, MySQL.';
		break;
		case "EN":
			about = 'Chatting Application "Talk-Talk"\nVersion 1.0 - Released on 07 July 2020\n\nWritten in JavaScript, Node JS, MySQL.';
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
	serverBusy = false;

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

		chatRoomData += `
	<div class="message">
		<div class="poster">
			<strong>${msg.MessageUser}</strong> - <span class="postTime">${msg.MessageTimeDate}</span>
		</div>
		<div class="posterData">
			${window.decodeURIComponent(msg.MessageData)}
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
	window.setTimeout(function()
	{
		const today = new Date();
		getObject("footer").innerHTML += `<hr/>2000 - ${today.getFullYear()} by Voyager 89`;

		window.alignAboutBox(document.body.offsetWidth, document.body.offsetHeight);

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
			chatRoomLinks[1].setAttribute("onclick", "window.loadChatRoom(this, 6); return false;"); // "Живеене в Сидни";
			chatRoomLinks[2].setAttribute("onclick", "window.loadChatRoom(this, 7); return false;") //"Гмуркане в Тропика";
			chatRoomLinks[3].setAttribute("onclick", "window.loadChatRoom(this, 8); return false;"); //"Спътници на Юпитер";
			chatRoomLinks[4].setAttribute("onclick", "window.loadChatRoom(this, 5); return false;"); //"Автомобилни Двигатели";
			chatRoomLinks[5].setAttribute("onclick", "window.loadChatRoom(this, 6); return false;"); //"Живеене в Сидни";
			chatRoomLinks[6].setAttribute("onclick", "window.loadChatRoom(this, 7); return false;"); //"Гмуркане в Тропика";
			chatRoomLinks[7].setAttribute("onclick", "window.loadChatRoom(this, 8); return false;"); //"Спътници на Юпитер";

			getObject("footer").innerHTML += `<hr/>2000 - ${today.getFullYear()} от Voyager 89`;

			getObject("h1").innerText = "„Ток-Ток“ Чат Програма";
			getObject("h2").innerText = "Избери потребителско име";
			getObject("textarea").setAttribute("placeholder", "Вашето съобщение...");
			getObject("span.title").innerText = "Чат Групи";
			getObject("#lnk_about").innerText = "Относно";
			getObject("#lnk_logoff_right").innerText = "Изпиши се";
			getObject("input[type='text']").setAttribute("placeholder", "Потребителско име...");
			getObject("#logOnMessage").innerHTML = "ТОВА Е ОБЩЕСТВЕНА ЧАТ ПРОГРАМА.<br/><br/>ПОТРЕБИТЕЛСКОТО ИМЕ КОЕТО ИЗБЕРЕТЕ СЕГА<br/>МОЖЕ ДА БЪДЕ ВЗЕТО ОТ НЯКОЙ ДРУГ В БЪДЕЩЕ.<br/><br/>АКО НЕ ИЗПОЛЗВАТЕ ПРОГРАМАТА ЗА ПОВЕЧЕ ОТ 30 МИНУТИ ЩЕ БЪДЕТЕ АВТОМАТИЧНО ИЗПИСАНИ.";
			getObject("section.about").innerHTML = `Чат Програма <strong>„Ток-Ток“</strong><br/>Версия 1.0 - Публикувана на 7 Юли 2020<br/><br/>Създадена с <em>JavaScript</em>, <em>Node JS</em>, <em>MySQL</em><br/><br/><a href="#" onclick="this.parentElement.style.visibility='hidden'; return false;">OK</a>`;
			getObject("#toErase").innerHTML = `ВСИЧКИ ЧАТ РАЗГОВОРИ СЕ ИЗТРИВАТ ВЕДНЪЖ ВСЕКИ 24 ЧАСА; СЛЕДВАЩОТО ИЗТРИВАНЕ ЩЕ БЪДЕ СЛЕД`;
		}
	}, 100);
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
	errorFlag = 1;

	window.clearInterval(loadNewMessages);

	if (isLoggingOff == false && isUnavailable == false)
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

	window.clearInterval(loadNewMessages);

	if (isLoggingOff == false && isUnavailable == false)
	{
		let notice = "";
		switch (appLang)
		{
			case "BG":
				notice = "ВНИМАНИЕ:\n\nВръзката със сървъра е прекъсната и тази програма ще започне отново.\n\nНатиснете ОК за да продължите.";
			break;
			case "EN":
				notice = "WARNING:\n\nThis application has experienced an unexpected interruption and will now be restarted.\n\nPress OK to restart, or Cancel to exit.";
			break;
		}

		window.alert(notice);

		if (getUserLogName() != null)
		{
			sessionStorage.removeItem("TalkTalkChatLogName");
		}
	}

	window.location.reload();
};