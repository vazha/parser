<?php
/* 
 * Скрипт получает на входе URL адрес сайта, находит все оригинальные внутренние
 * ссылки и проходит по очереди по ним. На каждой странице подсчитывается 
 * количество тегов <img> и сохраняется время обработки страницы. 
 * Результат сохраняется в виде HTML файла: report_dd.mm.yyyy.html
 * Результат отсортрован по количеству тегов на страницах.
 * Author Vazha.B.
 * Version 1.0
 */

namespace vaja;

function user_log ($errno, $errmsg, $file, $line) {
    /*глушим ошибку сервера 404 для несуществующих страниц*/
}
set_error_handler('vaja\user_log',E_WARNING);

spl_autoload_register(function ($class) {
    include './lib/'.$class.'.php';
});
 
$url = $argv[1]; /*Входной параметр - URL, получаем из командной строки*/
if(!$url){ /*Если URL не был задан в параметрах, просим */
    echo "\n Please, enter site URL (with http://) \n";
    echo "or pass it in commnd line parameters \n";
    $url = trim(fgets(STDIN));
}

if (filter_var($url, FILTER_VALIDATE_URL) === false) {
    die("$url Is NOT a valid URL, please specify full URL (HTTP://...)");
} 

$tag = "img"; /*Тег который будем искать*/
$root = __DIR__;

if (!is_writable ($root)){ /*Репорт файл будет сохранен в корневую директорию*/
    die("Directory $root is not writable for report file");    
}

$site = new ParserClass($url); /*Создаем объект для работы с сайтами*/

if($site){
    echo "working... \n";
    $pages = $site->findPages(); /*Метод для нахождения всех ссылок на главной*/
    echo count($pages)." links collected. Start parsing:"." \n";
    
    foreach($pages as $key=>$page){ /*Проходимся по всем найденным страницам*/
        $start = microtime(true);
        $site = new ParserClass($page);
        if($site){ /*Если страница доступна, сохраняем содержимое и парсим*/
            $tags[$key] = $site->findTags($tag);
            $time[$key] = round(microtime(true) - $start, 6);
            echo $key." "; /*Выводим номер текущей обрабатываемой страницы*/
        }
    }
    
    /*Сортируем массивы по количеству тегов*/
    $data = array_multisort($tags, SORT_NUMERIC, $time, $pages);
    $total =array($pages,$tags,$time); /*И создаем один общий массив*/
    $render = new ReportCretor($root); /*Объект для генерации отчета*/
    if ($render) { /*Проверяем доступен ли шаблон report HTML*/
        $result = $render->renderReport($total); /*Передаем массив на рендер*/
        if ($result){
            echo "\nDone. Report created: $root/$result";
        }else{
            echo "Report has not been created";
        }    
    }else{
        $error = "Templeate file $time/temletes/emptypage.php is not accassable";
    }
    
}else{
    $error = "URL not accassable or not correct";
}

if($error){
    echo $error;    
}

