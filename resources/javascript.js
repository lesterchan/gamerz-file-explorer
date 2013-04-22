/*
+----------------------------------------------------------------+
|																							|
|	GaMerZ File Explorer Version 1.20											|
|	Copyright (c) 2004-2008 Lester "GaMerZ" Chan							|
|																							|
|	File Written By:																	|
|	- Lester "GaMerZ" Chan															|
|	- http://www.lesterchan.net													|
|																							|
|	File Information:																	|
|	- GFE JavaScript File	 															|
|	- resources/javascript.js														|
|																							|
+----------------------------------------------------------------+
*/


// Function: Trigger Show HTML Code
function show_htmlcode() {
	document.getElementById('DisplayHTML').style.display = 'none';
	document.getElementById('DisplaySource').style.display = 'block';
}


// Function: Trigger Show HTML View
function show_htmlview() {	
	document.getElementById('DisplayHTML').style.display = 'block';
	document.getElementById('DisplaySource').style.display = 'none';
}