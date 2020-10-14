# laravel-gii
类似于yii2 的Gii工具, 可以创建CURD逻辑及验证规则

安装
-------

```
composer require --prefer-dist kaykay012/laravel-gii 
```
使用示例
-------

创建 Model, 包含验证规则
```
php artisan make:model-rule Models/User --table=users --cut
```

创建 CURD 逻辑
```
php artisan make:curd CurdController --model=App\\Models\\User
```

创建 api Wiki 文档.

    --doc MinDoc的documents表document_name字段名称 (使用此属性表中至少保留一条数据)

    --book MinDoc的md_book表主键id (使用此属性表中至少保留一条数据) 
```
php artisan make:wiki Admin/CurdController --model=App\\Models\\User --force --doc=article --book=2
```