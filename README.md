# EasyCacheService

### Простой написанный на чистом PHP кеш сервис, который хранит кеш в памяти компьютера.

## Установка сервиса

### Для установки сервиса потребуется:

- Добавить код из этого репозитория в вашу локальную директорию.

    Это можно сделать с помошью ```git``` или просто скачать ```архивом``` отсюда;

- Установить ```Composer```, если он у Вас не установлен;
- Запустить из вашей локальной директории с ```EasyCacheService``` команду:
  ```
    composer install
  ```
  После этого шага можно запустить сервис.

## Запуск сервиса

### Для запуска сервиса потребуется:

- Создать файл ```config.php``` из файла ```config.php.example```
- PHP-CLI >= 8.1
- Открыть терминал в корне директории сервиса и запустить команду: 
  ```
  php index.php
  ```
  После этого сервис будет прослушивать все подключения к нему.

### Для выключения сервиса потребуется в терминале нажать сочетание клавиш: ```CTRL + C```

## Использование сервиса

- Для использования сервиса необходимо к нему подключиться.
- Подключение зависит от настроек в файле ```config.php```

### Если ```config.php``` не отличается от ```config.php.example```, то 
### можно воспользоваться готовым решением - [EasyCacheClient](https://github.com/savrasov4dev/EasyCacheClient)
