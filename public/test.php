<?php

error_log("Summat");

$null_var = null;

if (!$null_var) {
	error_log("Not null_var");
} 
if (is_null($null_var)) {
	error_log("null_var is null");
}

$false_var = false;

if (!$false_var) {
	error_log("Not false_var");
} 
if (is_null($false_var)) {
	error_log("false_var is null");
}

/*
Summat
Not null_var
null_var is null

So null is equal to 
falsey (!var)
null

false is equal only to falsey
NOT null

Not false_var


*/
