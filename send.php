<?php
// send.php - обработчик форм обратной связи (совместим с PHP 7.1)
// Для работы требуется настроенная функция mail() на хостинге

// Отключаем вывод ошибок в браузер (для безопасности)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Устанавливаем заголовок JSON для ответа
header('Content-Type: application/json; charset=utf-8');

// Настройки получателя
$to_email = "info@advokat-pisarev.ru";  // Замените на нужный email
$subject_prefix = "Новое обращение с сайта Pisarev-Top";

// Функция для отправки письма
function sendEmail($to, $subject, $message, $headers) {
    // Дополнительные параметры для mail()
    $additional_params = "-f noreply@pisarev-top.ru";
    
    // Попытка отправки через mail()
    $result = @mail($to, $subject, $message, $headers, $additional_params);
    
    // Логируем результат для отладки
    if(!$result) {
        error_log("Mail send failed to: " . $to);
    }
    
    return $result;
}

// Логирование ошибок в файл (для отладки)
function logError($message) {
    $log_file = __DIR__ . '/mail_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Определяем тип формы и обрабатываем данные
$input_data = array_merge($_POST, json_decode(file_get_contents('php://input'), true) ?: array());

// Обработка основной формы (форма обратной связи #mainForm)
if(isset($_POST['name']) && isset($_POST['phone']) && !isset($_POST['exit_phone'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : 'Не указано';
    $consent = isset($_POST['consent']) ? 'Да' : 'Нет';
    
    $subject = $subject_prefix . " - Форма связи";
    
    $email_message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Georgia', serif; background: #f5f0e6; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fffef7; border: 1px solid #dacaa2; padding: 20px; }
            h2 { color: #800020; border-bottom: 2px solid #b8860b; padding-bottom: 10px; }
            .field { margin: 15px 0; }
            .label { font-weight: bold; color: #2c241c; }
            .value { margin-top: 5px; padding: 8px; background: #f9f5e8; border-left: 3px solid #b8860b; }
            .footer { margin-top: 20px; font-size: 12px; color: #888; text-align: center; border-top: 1px solid #e0d5c0; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>📩 Новое обращение с сайта</h2>
            <div class='field'>
                <div class='label'>👤 Имя:</div>
                <div class='value'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</div>
            </div>
            <div class='field'>
                <div class='label'>📞 Телефон:</div>
                <div class='value'>" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</div>
            </div>
            <div class='field'>
                <div class='label'>📝 Сообщение:</div>
                <div class='value'>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</div>
            </div>
            <div class='field'>
                <div class='label'>✅ Согласие на обработку:</div>
                <div class='value'>" . $consent . "</div>
            </div>
            <div class='field'>
                <div class='label'>🕐 Время отправки:</div>
                <div class='value'>" . date('d.m.Y H:i:s') . "</div>
            </div>
            <div class='field'>
                <div class='label'>🌐 IP адрес:</div>
                <div class='value'>" . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown')) . "</div>
            </div>
            <div class='footer'>
                Письмо сгенерировано автоматически с сайта pisarev-top.ru
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode("Сайт Писаревъ А.А.") . "?= <noreply@pisarev-top.ru>\r\n";
    $headers .= "Reply-To: " . $to_email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    try {
        if(sendEmail($to_email, $subject, $email_message, $headers)) {
            echo json_encode(array('status' => 'success', 'message' => 'Сообщение отправлено'));
        } else {
            logError("Ошибка отправки основной формы");
            echo json_encode(array('status' => 'error', 'message' => 'Ошибка отправки. Пожалуйста, позвоните по телефону.'));
        }
    } catch (Exception $e) {
        logError("Exception: " . $e->getMessage());
        echo json_encode(array('status' => 'error', 'message' => 'Техническая ошибка'));
    }
    exit;
}

// Обработка формы "Узнать цену" (#priceForm)
elseif(isset($_POST['name']) && isset($_POST['phone']) && isset($_POST['details'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $details = isset($_POST['details']) ? trim($_POST['details']) : 'Не указано';
    $consent = isset($_POST['consent']) ? 'Да' : 'Нет';
    
    $subject = $subject_prefix . " - Запрос стоимости";
    
    $email_message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Georgia', serif; background: #f5f0e6; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fffef7; border: 1px solid #dacaa2; padding: 20px; }
            h2 { color: #800020; border-bottom: 2px solid #b8860b; padding-bottom: 10px; }
            .field { margin: 15px 0; }
            .label { font-weight: bold; color: #2c241c; }
            .value { margin-top: 5px; padding: 8px; background: #f9f5e8; border-left: 3px solid #b8860b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>💰 Запрос стоимости дела</h2>
            <div class='field'>
                <div class='label'>👤 Имя:</div>
                <div class='value'>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</div>
            </div>
            <div class='field'>
                <div class='label'>📞 Телефон:</div>
                <div class='value'>" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</div>
            </div>
            <div class='field'>
                <div class='label'>📋 Описание дела:</div>
                <div class='value'>" . nl2br(htmlspecialchars($details, ENT_QUOTES, 'UTF-8')) . "</div>
            </div>
            <div class='field'>
                <div class='label'>✅ Согласие на обработку:</div>
                <div class='value'>" . $consent . "</div>
            </div>
            <div class='field'>
                <div class='label'>🕐 Время отправки:</div>
                <div class='value'>" . date('d.m.Y H:i:s') . "</div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode("Сайт Писаревъ А.А.") . "?= <noreply@pisarev-top.ru>\r\n";
    $headers .= "Reply-To: " . $to_email . "\r\n";
    
    try {
        if(sendEmail($to_email, $subject, $email_message, $headers)) {
            echo json_encode(array('status' => 'success', 'message' => 'Запрос отправлен'));
        } else {
            logError("Ошибка отправки формы запроса цены");
            echo json_encode(array('status' => 'error', 'message' => 'Ошибка отправки'));
        }
    } catch (Exception $e) {
        echo json_encode(array('status' => 'error', 'message' => 'Техническая ошибка'));
    }
    exit;
}

// Обработка exit-popup формы
elseif(isset($_POST['exit_phone'])) {
    $phone = isset($_POST['exit_phone']) ? trim($_POST['exit_phone']) : '';
    
    $subject = $subject_prefix . " - Exit-попап (перезвонить)";
    
    $email_message = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Georgia', serif; background: #f5f0e6; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: #fffef7; border: 1px solid #dacaa2; padding: 20px; }
            h2 { color: #800020; }
            .field { margin: 15px 0; }
            .phone-num { font-size: 20px; font-weight: bold; color: #b8860b; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>📞 Запрос звонка (Exit-попап)</h2>
            <div class='field'>
                <div class='phone-num'>" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</div>
            </div>
            <div class='field'>
                <div class='label'>🕐 Время отправки:</div>
                <div class='value'>" . date('d.m.Y H:i:s') . "</div>
            </div>
            <div class='field'>
                <div class='label'>🌐 IP адрес:</div>
                <div class='value'>" . (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown')) . "</div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: =?UTF-8?B?" . base64_encode("Сайт Писаревъ А.А.") . "?= <noreply@pisarev-top.ru>\r\n";
    
    try {
        if(sendEmail($to_email, $subject, $email_message, $headers)) {
            echo json_encode(array('status' => 'success', 'message' => 'OK'));
        } else {
            logError("Ошибка отправки exit-попап формы");
            echo json_encode(array('status' => 'error'));
        }
    } catch (Exception $e) {
        echo json_encode(array('status' => 'error'));
    }
    exit;
}

// Если ничего не подошло -> возвращаем ошибку
else {
    http_response_code(400);
    echo json_encode(array('status' => 'error', 'message' => 'Неверные данные формы'));
    exit;
}
?>