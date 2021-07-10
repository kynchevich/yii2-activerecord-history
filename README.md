Yii2 ActiveRecord History behavior for Yii 2
=========================


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require kirillemko/yii2-activerecord-history
```

or add

```
"kirillemko/yii2-activerecord-history": "*"
```

to the require section of your composer.json.

Run migration

```
php yii migrate --migrationPath=@vendor/kirillemko/yii2-activerecord-history/migrations
```


Использование
-----
Поведение позволяет писать историю изменения модели.
Просто добавьте ее в модель в метод behaviors:

```
public function behaviors() {
    return [
        ...
        [ 'class' => ActiveRecordHistoryBehavior::class ]
```

Возможны следующие параметры конфигурации:
```
Поставить в true, если при создании модели нужно записать все поля в историю. 
В противном случае пишется только сам факт создания модели
'saveFieldsOnInsert' => false

Массив полей, изменение которых не нужно писать.
Например при использовании 'created_at'
'ignoreFields' => []

Конфигурация отслеживаемых евентов. 
Можно отключить евент, если не нужно писать
'watchInsertEvent' => true,
'watchUpdateEvent' => true,
'watchDeleteEvent' => true
```


Также поведение предоставляет метод для получения истории изменений

```
$someModel->getChangesHistory();
```

Будет возвращен массив объектов kirillemko\activeRecordHistory\models\ActiveRecordHistory

При приведении объекта к массиву будут два дополнительных свойства type_full_name и field_full_name для удобства подстановки на фронте. Пример:

```
{
    "id": 10,
    "user_id": 1,
    "type": 2,
    "model": "App\\domain\\ACL\\models\\AclGroupsPermissions",
    "model_id": "21",
    "field_name": "desc",
    "old_value": "Описание объекта",
    "new_value": "Новое описание объекта",
    "created_at": 1625908836,
    "field_full_name": "Описание",
    "type_full_name": "Редактирование"
}
```

В данном объекте в свойстве field_full_name реализовано получение человеческого названия из attributesLabel таргет модели


TODO
-----
У объекта kirillemko\activeRecordHistory\models\ActiveRecordHistory
дополнительное свойство type_full_name сделать через i18n, а не хардкод


Credits
-------

Authors: Kirill Emelianenko

Email: kirill.emko@mail.ru

