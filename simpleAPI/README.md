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
	-[/tools/base64Decode/VEVE?key=c2ltcGxlQVBJMTIz&format=json](https://projects.voyager89.net/en/api/tools/base64Decode/VEVE?key=c2ltcGxlQVBJMTIz&format=json)

- When inputting user-set timezone it must follow this format: e.g. Australia__Sydney, America__Argentina__Buenos_Aires
- When inputting a custom string for the md5 and base64 encode/decode functions, the string cannot exceed 512 characters
- When using the base64 decode function the input string must be a valid base64-encoded string, or an error will be returned

***

## Future features - version 1.2

- To be determined at a later date.
	
***

Developed using `PHP`