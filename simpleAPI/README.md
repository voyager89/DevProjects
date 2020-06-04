# Simple API

**Released on 24 September 2018**

***

## Features - version 1.1

**Released on 04 June 2020**

- Requires key for authentication

- Has these built-in tools:
	1. Get Md5 of string
	2. Encode string to base64
	3. Decode string from base64

- Timezone functions:
	1. Get date and time of current timezone
	2. Get date and time of user selected timezone

- Does not require provided UI to work above functions
- Above functions can be utilized by using your browser to access the following URL: https://projects.voyager89.net/en/api/
	- [/timezone/Australia__Sydney/getTimeAndDate?key=c2ltcGxlQVBJMTIz&format=json](https://projects.voyager89.net/en/api/timezone/Australia__Sydney/getTimeAndDate?key=c2ltcGxlQVBJMTIz&format=json)
	- [/timezone/currentTimeZone/getTimeAndDate?key=c2ltcGxlQVBJMTIz&format=xml](https://projects.voyager89.net/en/api/timezone/currentTimeZone/getTimeAndDate?key=c2ltcGxlQVBJMTIz&format=xml)
	- [/tools/md5/RandomString?key=c2ltcGxlQVBJMTIz&format=json](https://projects.voyager89.net/en/api/tools/md5/RandomString?key=c2ltcGxlQVBJMTIz&format=json)
	- [/tools/base64Encode/Hello?key=c2ltcGxlQVBJMTIz&format=xml](https://projects.voyager89.net/en/api/tools/base64Encode/Hello?key=c2ltcGxlQVBJMTIz&format=xml)
	- [/tools/base64Decode/VEVE?key=c2ltcGxlQVBJMTIz&format=json](https://projects.voyager89.net/en/api/tools/base64Decode/VEVE?key=c2ltcGxlQVBJMTIz&format=json)


- When inputting user-set timezone it must follow this format: e.g. Australia__Sydney, America__Argentina__Buenos_Aires

- When inputting a custom string for the md5 and base64 encode/decode functions, the string cannot exceed 512 characters

- When using the base64 decode function the input string must be a valid base64-encoded string, or an error will be returned



**Warning**

It is not recommended to pass html tags as string, for e.g. */tools/base64Encode/<script>window.alert("hello");</script>*.

Most modern browsers will reject to send this as part of security policy.

However, main.html has a built-in function that disguises certain special characters to allow them to be passed as arguments.


Say, for example, you wanted to pass this html string and encode it to base64: `<a href="#">ha</a>` - if you type it in the relevant field in the main.html interface the following will be queried:
[tools/base64Encode/[14]a+href[16][01][05][01][15]ha[14][17]a[15]?key=c2ltcGxlQVBJMTIz&format=json](https://projects.voyager89.net/en/api/tools/base64Encode/[14]a+href[16][01][05][01][15]ha[14][17]a[15]&key=c2ltcGxlQVBJMTIz&format=json)

...and decoded...

[tools/base64Decode/Jmx0O2EgaHJlZj0mcXVvdDsjJnF1b3Q7Jmd0O2hhJmx0Oy9hJmd0Ow==?key=c2ltcGxlQVBJMTIz&format=json](https://projects.voyager89.net/en/api/tools/base64Decode/Jmx0O2EgaHJlZj0mcXVvdDsjJnF1b3Q7Jmd0O2hhJmx0Oy9hJmd0Ow==?key=c2ltcGxlQVBJMTIz&format=json)

Although, once again, passing html or php tags, or special characters as URL arguments is highly discouraged. Consider encoding them first.

***

## Future features - version 1.2

- To be determined at a later date.
	
***

Developed using `PHP`