# Описание платежного плагина ИнвойсБокс для CMS osCommerce 2.3

Платёжный модуль для интеграции платёжной системы «ИнвойсБокс» и osCommerce 2.3. Реализована поддержка платёжного API. Протестировано на CMS osCommerce 2.3.4.1.

## Установка плагина

1. Скопируйте папки "ext" и "includes" в корень сайта.
1. В админ-панели пройдите в "Модули" —> "Оплата" —> "Установить модуль". Найдите в списке "invoicebox" -> выберите его -> нажмите кнопку "Установить модуль".

## Настройка модуля
1. В админ-панели пройдите в "Модули" —> "Оплата";
1. Выберите модуль "Invoicebox" нажмите "Редактировать";
1. В окне введите следующие настройки:
    - "Идентификатор магазина" (Shop ID) 
    - "Региональный код магазина" (Region shop ID)
    - "Ключ безопасности магазина" (API Code)
1. Нажмите на кнопку "Сохранить".

### Специфические настройки 

Тестовый режим (Test Mode) - включите его для проведения тестовых платежей, при включении этого режима, вы пройдете все шаги в платежном терминале ИнвойсБокс, но деньги с вашей карты списаны не будут. 
Для включения тестового режима выберите "Test". Переключатель в положении "Real" включает боевой режим.

Статус не оплаченного заказа (Set Preparing Order Status) - выберите статус заказа, который присваивается до момента оплаты.

Статус оплаченного заказа (Set Acknowledged Order Status) - этот статус присваивается уже оплаченному заказу.

Порядок сортировки модулей оплаты (Sort order of display) - в этом поле укажите цифру, 0 - модуль оплаты выводится первым, 1,2,3 в порядке возрастания.



### Настройка панели ИнвойсБокс:

1. Для настройки панели управления ИнвойсБокс пройдите по url - https://login.invoicebox.ru/ ;
1. Авторизуйтесь и пройдите в раздел "Мои магазины". "Начало работы" -> "Настройки" -> "Мои магазины";
1. Пройдите по вкладку "Уведомления по протоколу" -> выберите "Тип уведомления" "Оплата/HTTP/Post (HTTP POST запрос с данными оплаты в переменных)"
1. В поле "URL уведомления" укажите:

    `<домен_сайта>/ext/modules/payment/invoicebox/callback.php`

1. Сохраните изменения.