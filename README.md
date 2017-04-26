# evoAutoFolder
Плагин для MODx custom by Dmi3yy

1
Текущая версия: evoAutoFolder 0.1

Версия тестировалась на сборке от Dmi3yy MODX EVO 1.1b-d7.1.1
Протестировал на сборке от Dmi3yy MODX EVO 1.1-d8.1.2

Изначально планировалось просто доработать плагин kcAutoFolder от Pathologic, но почти весь код и логика были переработаны, и поэтому родился новый плагин evoAutoFolder.

Осторожно! Лучше накатывать плагин на новый сайт, т.к. если на сайте есть TV с картинками или файлами, то при клике по этим TV KCFinder всё равно будет смотреть в папку созданную плагином.

Решения:
  1. Заранее на сервере организовать структуру уже созданных ресурсов по логике плагина в папке assets/uploads и перенести файлы.
  2. Пройтись по всем ресурсам и поновому залить файлы, тогда плагин закачает файлы уже в правильные папки 
  3. Возможно Вы сможете придумать более изящное решение, тогда сообщите мне :)

Основные возможности:
=======================================================
- В папке 'assets/uploads' создает папки по id ресурсов с учетом иерархии
- Для новых ресурсов создается папка в 'assets/uploads' по дате, при сохранении ресурса папка переименовывается на id и перемещается в папку родительского ресурса
- KCFinder смотрит только в созданную папку и папки других ресурсов в нём не видны (разделение доступа к папкам)
- При перемещении ресурса плагин перемещает папку ресурса с учетом вложенных папок дочерних ресурсов в папку нового родителя
- При перемещении ресурса плагин автоматически заменяет пути у всех TV ресурса и также заменяет пути у всех TV дочерних ресурсов
- При очистке корзины плагин удаляет папки удаленных ресурсов
- Плагин создает для каждого ресурса папки images, files и т.д.
- Превью изображений и файлов создаются в папке ресурса и подцепляются KCFinder'ом

Пример работы плагина
=======================================================
Имеется дерево ресурсов:
- Документ 1 (10)
  - Документ 2 (11)
  - Документ 3 (12)
  - Документ 4 (13)
    - Документ 5 (14)

Для всех ресурсов будут созданы папки по их id: 10, 11, 12, 13 и 14; но будет сохранена иерархия.

Так будет выглядеть структура файлов на сервере:
- assets/uploads/10/ - папка для документа 1
- assets/uploads/10/11/ - папка для документа 2
- assets/uploads/10/12/ - папка для документа 3
- assets/uploads/10/13/ - папка для документа 4
- assets/uploads/10/13/14/  - папка для документа 5

Если переместить "документ 4" в "документ 2" то дерево ресурсов изменится:
- Документ 1 (10)
  - Документ 2 (11)
    - Документ 4 (13)
      - Документ 5 (14)
  - Документ 3 (12)

Структура файлов на сервере после перемещения:
- assets/uploads/10/ - папка для документа 1
- assets/uploads/10/11/ - папка для документа 2
- assets/uploads/10/12/ - папка для документа 3
- assets/uploads/10/11/13/ - папка для документа 4
- assets/uploads/10/11/13/14/  - папка для документа 5

Требования
=======================================================
1. Установленный сниппет DocLister (начиная с версии MODX EVO 1.1RC-d7.1.6 он уже стоит из коробки)

Установка
=======================================================
1. Залить на сервер файлы плагина
2. Создать плагин "evoAutoFolder"
3. На вкладке "Общие" в поле "Код плагина (php)" вставить содержимое файла  'install/assets/plugins/evoautofolder.tpl'
4. На вкладке "Конфигурация" в поле "Конфигурация плагина" вставить "&lifetime=Время жизни записей в БД в часах;text;24" и нажать на кнопку "Обновить параметры"
5. На вкладке "Системные события" выбрать события: OnDocFormRender, OnDocFormSave, OnManagerPageInit, onBeforeMoveDocument, onAfterMoveDocument, OnBeforeEmptyTrash, OnManagerMainFrameHeaderHTMLBlock, OnDocDuplicate
6. Сохранить плагин
7. Открываем файл 'manager/media/browser/mcpuk/tpl/tpl_javascript.php'

    Находим строку (у меня 22-я):
    
    browser.dir = "<?php echo text::jsValue($this->session['dir']) ?>";
    
    Заменяем на:
    
    browser.dir = "<?php echo text::jsValue($_SESSION['KCFINDER']['browser_dir'] . $this->session['dir']) ?>";
    
    Находим строку (у меня 25-я):
    
    browser.thumbsURL = browser.assetsURL + "/<?php echo text::jsValue($this->config['thumbsDir']) ?>";
    
    Заменяем на:
    
    browser.thumbsURL = browser.assetsURL + "/<?php echo text::jsValue($_SESSION['KCFINDER']['browser_dir'] . $this->config['thumbsDir']) ?>";
