<?php
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пример работы класса CargaDes</title>
    <script>
        function test(ejemplo) {
            var url = document.getElementById("url").value;
            var path = document.getElementById("path").value;
            document.getElementById("result").innerHTML = "<iframe name='truncframe' id='truncframe' width='100%' height='600px' src='http://" + url + ejemplo + "?url_=" + url + "&path_=" + path + "' frameborder='0' marginwidth='0' marginheight='0' allowtransparency='true' align='center'></iframe>";
        }
    </script>
    <style>
        .link {
            color: darkgray;
        }

        .link:hover {
            color: blue;
            text-decoration: underline;
            cursor: pointer;
        }
    </style>
</head>
<body style="text-align: center">
<table style="width: 70%;margin: auto;">
    <tr>
        <td style="border-bottom: 1px solid black;">
            Пример работы класса CargaDes
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1px solid black;">
            Если адрес папки с примерами не совпадает, то впишите правильный адрес:
            http://<input type="text" id="url" style="width: 250px"
                          value="<?= $_SERVER['SERVER_NAME'] . str_replace('index.php', '', $_SERVER['REQUEST_URI']) ?>">
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1px solid black;white-space: nowrap;">
            Если файлы класса находятся не в этом месте, то впишите правильный путь:
            <?= $_SERVER['DOCUMENT_ROOT'] ?><input type="text" id="path" style="width: 200px"
                                                   value="/vendor/borivit/cargades/rama_2/">
        </td>
    </tr>
    <tr>
        <td class="link">
            <div onclick="test('ejemplo_1.php')">Отдаем файл пользователю через браузер</div>
        </td>
    </tr>
    <tr>
        <td class="link">
            <div onclick="test('ejemplo_2.php')">Загрузка файлов на сервер через браузер с индикацией прогресса</div>
        </td>
    </tr>
    <tr>
        <td class="link">
            <div onclick="test('ejemplo_3.php')">Забераем файл с удаленного сервера на свой сервер</div>
        </td>
    </tr>
    <tr>
        <td class="link">
            <div onclick="test('ejemplo_4.php')">Отдаем файл на удаленный сервер со своего сервера</div>
        </td>
    </tr>
    <tr>
        <td style="border-bottom: 1px solid black;border-top: 1px solid black">
            Результат выполнения примеров (файлы записываются в папку test)
        </td>
    </tr>
    <tr>
        <td style="padding: 10px;">
            <div id="result"></div>
        </td>
    </tr>
</table>
</body>
</html>