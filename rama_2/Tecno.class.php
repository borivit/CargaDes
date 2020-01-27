<?php

/*******************************************
 * Вспомогательный класс.
 *******************************************************************
 */
class Tecno extends Mensajes
{
    public $data = array();
    public $block_data = array();

    /*******************************************
     * Создает массив замены значений
     *******************************************************************
     * @param string $name -    Имя тега
     * @param string $var -    Значение переменной
     */
    public function set($name, $var)
    {
        $this->data[$name] = $var;
    }

    /*******************************************
     * Удаление тегов
     *******************************************************************
     * @param string $name -    Имя тегов
     */
    public function setBlockUnhide($name)
    {
        $this->set('[' . $name . ']', '');
        $this->set('[/' . $name . ']', '');
    }

    /*******************************************
     * Создает массив замены тегов
     *******************************************************************
     * @param string $name -    Имя тега
     */
    public function setBlockHide($name)
    {
        $this->block_data["'\\[" . $name . "\\](.*?)\\[/" . $name . "\\]'si"] = '';
    }

    /*******************************************
     * Удаление тегов и текста между ними
     *******************************************************************
     * @param string $txt -    Текст в котором производится замена
     * @return string
     */
    public function delBlock($txt)
    {
        $find_preg = $replace_preg = array();
        if (sizeof($this->block_data)) {
            foreach ($this->block_data as $key_find => $key_replace) {
                $find_preg[] = $key_find;
                $replace_preg[] = $key_replace;
            }

            return preg_replace($find_preg, $replace_preg, $txt);
        }
        return false;
    }

    /*******************************************
     * Замена значений
     *******************************************************************
     * @param string $txt -    Текст в котором производится замена
     * @return string
     */
    public function replaceVar($txt)
    {
        $find = $replace = array();
        foreach ($this->data as $key_find => $key_replace) {
            $find[] = $key_find;
            $replace[] = $key_replace;
        }

        return str_replace($find, $replace, $txt);
    }

    /*******************************************
     * Автоматизация удаления/замены тегов и значений
     *******************************************************************
     * @param string $txt -    Текст в котором производится замена
     * @return string
     */
    public function compilePl($txt)
    {
        $txt = $this->delBlock($txt);
        $txt = $this->replaceVar($txt);

        return $txt;
    }

    /*******************************************
     * Возвращает MIME-тип содержимого файла и проверка на его существование
     *******************************************************************
     * @param string $realFilePath - Путь к проверяемому файлу.
     * @return bool|string - тип файла
     */
    public function mime_type($realFilePath)
    {

        if (!file_exists($realFilePath)) {
            return false;
        }

        if (!function_exists('mime_content_type')) {

            $mime_types = array(

                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',

                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',

                // archives
                'zip' => 'application/x-zip-compressed',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',

                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',

                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',

                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',

                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            );

            $ext = strtolower(array_pop(explode('.', $realFilePath)));
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME);
                $mimetype = finfo_file($finfo, $realFilePath);
                finfo_close($finfo);
                return $mimetype;
            } else {
                return 'application/octet-stream';
            }
        } else {
            return mime_content_type($realFilePath);
        }
    }
}