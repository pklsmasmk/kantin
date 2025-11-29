<?php
switch ($_GET["q"] ?? "") {
    case "login":
        include("../login/Login.php");
        break;
    case "logout":
        include("../login/Logout.php");
        break;
    case "full":
        include("../config/pilihanfullphp.php");
        break;
    default:
        require_once("../index.php");
}
