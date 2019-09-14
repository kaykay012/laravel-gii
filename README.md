# laravel-gii
类似于yii2 的Gii工具,可以创建CURD逻辑及验证规则

安装
-------

```
composer require --prefer-dist kaykay012/laravel-gii 
```
使用示例
_______
```
#创建 CURD 逻辑
php artisan make:curd CurdController --model=App\\Models\\User

#创建 Model, 包含验证规则
php artisan make:modelk Models/User --table=users
```