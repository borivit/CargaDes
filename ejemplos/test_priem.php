<?php
define('ROOT_DIR', dirname(__FILE__));
echo '<br>test: ';
print_r($_FILES);
echo '<br>' . str_repeat('-', 80) . '<br>';

if (!is_dir(ROOT_DIR . '/test/')) mkdir(ROOT_DIR . '/test/', 0777);

if (!empty($_FILES['file']) and !is_array($_FILES['file']['tmp_name'])) {
    move_uploaded_file($_FILES['file']['tmp_name'], ROOT_DIR . '/test/' . $_FILES['file']['name']);
} elseif (!empty($_FILES['upload']) and !is_array($_FILES['upload']['tmp_name'])) {
    move_uploaded_file($_FILES['upload']['tmp_name'], ROOT_DIR . '/test/' . $_FILES['upload']['name']);
}