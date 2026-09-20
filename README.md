# TomNut PHP 文档

## 一、简介

### 1.1 框架定位

本框架是一个**教学级 / 轻量级**的 PHP MVC 框架，目标是：

- 代码量小、结构清晰，便于阅读和二次开发
- 覆盖 Web 开发最核心的能力：路由、视图、缓存、数据库
- 不追求大而全，只保留必要模块

适合：小型项目、内部工具、学习框架原理、作为自研框架起点。

### 1.2 特性一览

| 模块 | 能力 |
|------|------|
| 自动加载 | PSR-4 命名空间映射（Composer 接管） |
| 路由 | 四种 HTTP 方法、动态参数、参数正则限定 |
| 视图 | Twig 模板，支持省略 `.twig` 后缀 |
| 缓存 | 文件缓存，支持 TTL |
| 数据库 | PDO 单例 + 链式查询构造器 + 事务 |
| 工具类 | 通用辅助函数目录 `utils/` |

### 1.3 环境要求

- PHP >= 7.4（用到 `str_starts_with` 需 8.0，可自行替换）
- Composer
- 扩展：`pdo`、`pdo_mysql`（使用数据库时）
- 依赖：`twig/twig ^3.0`

---

## 二、快速开始

### 2.1 安装依赖

```bash
composer install
```

`composer.json` 中的关键配置：

```json
{
    "require": {
        "php": ">=7.4",
        "twig/twig": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Core\\": "core/",
            "Utils\\": "utils/"
        }
    }
}
```

### 2.2 启动服务器

```bash
php -S localhost:8000 -t public
```

`public/` 是对外暴露的 Web 根目录，`index.php` 是唯一入口。

### 2.3 第一个页面

1. 在 `routes/web.php` 注册一条路由：

```php
$router->get('/', [HomeController::class, 'index']);
```

2. 在 `app/Controllers/HomeController.php` 写方法：

```php
public function index(): string
{
    return View::render('home', ['name' => 'World']);
}
```

3. 在 `views/home.twig` 写模板：

```twig
<h1>Hello {{ name }}</h1>
```

访问 `http://localhost:8000/`，页面显示 `Hello World`。

### 2.4 目录结构总览

```
my-framework/
├── app/
│   └── Controllers/           # 控制器
├── config/
│   └── config.php             # 全局配置
├── core/
│   ├── Autoloader.php         # 自动加载（备用）
│   ├── Cache.php              # 文件缓存
│   ├── Router.php             # 路由
│   ├── View.php               # Twig 封装
│   ├── Database.php           # PDO 封装
│   └── App.php                # 应用入口
├── routes/
│   └── web.php                # 路由定义
├── views/                     # Twig 模板
│   └── home.twig
├── utils/                     # 工具类
│   └── Helper.php
├── storage/
│   └── cache/                 # 缓存 + Twig 编译
├── public/
│   └── index.php              # 唯一入口
├── vendor/
└── composer.json
```

---

## 三、自动加载

### 3.1 命名空间与目录映射

框架通过 Composer 的 PSR-4 规则加载类：

| 命名空间 | 对应目录 |
|---------|---------|
| `App\` | `app/` |
| `Core\` | `core/` |
| `Utils\` | `utils/` |

规则：`App\Controllers\HomeController` → `app/Controllers/HomeController.php`。

### 3.2 新增类的方式

1. 文件名与类名一致
2. 命名空间与目录层级一致
3. 首字母大写（遵循 PSR-4）

例如新增 `app/Models/User.php`：

```php
<?php

namespace App\Models;

class User
{
}
```

即可在任意位置 `use App\Models\User;`。

### 3.3 手动 Autoloader（备用）

若不想依赖 Composer，可用 `core/Autoloader.php`：

```php
Core\Autoloader::register();
Core\Autoloader::add('App', __DIR__ . '/../app');
Core\Autoloader::add('Utils', __DIR__ . '/../utils');
```

一般项目直接使用 Composer 即可，此类仅作兜底。

---

## 四、配置

### 4.1 配置文件位置

所有配置集中在 `config/config.php`，返回一个数组。

### 4.2 配置项说明

```php
return [
    // 应用
    'debug'      => true,                              // 调试模式
    'view_path'  => __DIR__ . '/../views',             // 模板目录
    'cache_path' => __DIR__ . '/../storage/cache',     // 缓存目录
    'cache_ttl'  => 3600,                              // 默认缓存 TTL（秒）

    // 数据库
    'db' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'test',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],
];
```

| 键 | 说明 |
|----|------|
| `debug` | `true` 时 Twig 不缓存、错误可见 |
| `view_path` | Twig 加载模板的根目录 |
| `cache_path` | 文件缓存与 Twig 编译产物目录 |
| `cache_ttl` | 缓存默认过期秒数 |
| `db` | PDO 连接参数 |

### 4.3 读取配置

配置在 `App` 构造函数中读入并分发给各模块，业务层一般**不需要直接读配置**。若确实需要，可在 `App` 中暴露一个 `config()` 方法，或直接把配置存入容器。

---

## 五、路由

### 5.1 路由文件位置与作用

`routes/web.php` 在 `App::run()` 中被 `require` 加载，文件内可直接使用 `$router`。作用是把 **HTTP 方法 + URL** 映射到 **控制器方法** 或 **闭包**。

文件结构：

```php
<?php

use App\Controllers\HomeController;
use App\Controllers\UserController;

/** @var Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
```

### 5.2 四种 HTTP 方法

```php
$router->get('/user',    [UserController::class, 'index']);   // 查询
$router->post('/user',   [UserController::class, 'store']);   // 新增
$router->put('/user',    [UserController::class, 'update']);  // 更新
$router->delete('/user', [UserController::class, 'destroy']); // 删除
```

同一路径可注册多个方法，按请求方法匹配。

### 5.3 控制器写法

```php
use App\Controllers\UserController;

$router->get('/users/:id', [UserController::class, 'show']);
```

执行时 Router 会 `new UserController()` 并调用 `->show($id)`。业务逻辑推荐使用控制器。

### 5.4 闭包写法

```php
$router->get('/ping', fn() => 'pong');
$router->get('/time', function () {
    return date('Y-m-d H:i:s');
});
```

适合探针、健康检查等临时逻辑。

### 5.5 动态参数 `:name`

```php
$router->get('/users/:id', [UserController::class, 'show']);
```

默认规则：`:id` 匹配 `[^/]+`（非斜杠的一段）。控制器方法形参名**必须与占位符一致**：

```php
public function show($id) { ... }
```

### 5.6 参数正则限定 `:id(\d+)`

支持在参数后加括号写内联正则：

```php
$router->get('/orders/:id(\d+)',            [OrderController::class, 'show']);
$router->get('/posts/:slug([a-z0-9-]+)',    [PostController::class, 'show']);
$router->get('/archive/:year(\d{4})/:month(\d{2})', [PostController::class, 'archive']);
$router->get('/lang/:locale(en|zh|ja)',     [HomeController::class, 'lang']);
```

匹配对照：

| 定义 | 匹配 | 不匹配 |
|------|------|--------|
| `:id(\d+)` | `/orders/123` | `/orders/abc` |
| `:slug([a-z0-9-]+)` | `/posts/hello-world` | `/posts/Hello` |
| `:year(\d{4})` | `/archive/2024/09` 中的 `2024` | `24` |
| `:locale(en\|zh\|ja)` | `/lang/zh` | `/lang/fr` |

**注意**：括号内不要嵌套 `)`，否则会提前截断。`(en|zh)` 建议直接写 `en|zh`。

### 5.7 多参数与顺序

```php
$router->get('/posts/:year/:month', [PostController::class, 'archive']);
```

```php
public function archive($year, $month) { ... }
```

参数按**定义顺序**注入，名字需对应。

**路由顺序敏感**：匹配按注册顺序遍历，更具体的路径要写在更宽泛的路径之前：

```php
$router->get('/users/new', [UserController::class, 'create']);  // 先
$router->get('/users/:id', [UserController::class, 'show']);    // 后
```

### 5.8 尾斜杠规则

Router 内部使用 `rtrim($pattern, '/') . '/?$'`，所以尾斜杠**可选**：

| 注册 | 匹配 |
|------|------|
| `/user` | `/user` 和 `/user/` |
| `/user/` | `/user` 和 `/user/` |

不需要写两条。

### 5.9 404 处理

`dispatch()` 未匹配到路由时返回 404 纯文本。如需自定义页面，可修改 `Router::dispatch()` 中的 404 分支，改为渲染一个 Twig 模板。

### 5.10 常见坑与注意事项

| 坑 | 说明 |
|----|------|
| 顺序敏感 | `/users/new` 必须放在 `/users/:id` 之前 |
| 参数名一致 | `:id` 对应 `show($id)` |
| 不跨斜杠 | `:id` 不匹配含 `/` 的内容 |
| PUT/DELETE 取参 | PHP 不填 `$_POST`，需读 `php://input` |
| 正则括号嵌套 | 不支持，避免在 `(...)` 内再写 `)` |

---

## 六、控制器

### 6.1 目录与命名空间

- 目录：`app/Controllers/`
- 命名空间：`App\Controllers`
- 文件名 = 类名（PSR-4）

```php
<?php

namespace App\Controllers;

class UserController
{
}
```

### 6.2 返回值约定

控制器方法返回**字符串**即可，`App::run()` 会 `echo` 出去。

```php
public function index(): string
{
    return View::render('users/index', ['users' => $users]);
}
```

要返回 JSON，直接返回字符串：

```php
return json_encode($data, JSON_UNESCAPED_UNICODE);
```

### 6.3 接收路由参数

参数按名字注入：

```php
public function show($id) { ... }              // :id
public function archive($year, $month) { ... } // :year / :month
```

### 6.4 获取请求数据

| 方法 | 获取方式 |
|------|---------|
| GET | `$_GET['key']` |
| POST（表单） | `$_POST['key']` |
| PUT / DELETE | `parse_str(file_get_contents('php://input'), $data)` |
| JSON 请求体 | `json_decode(file_get_contents('php://input'), true)` |

示例（更新）：

```php
public function update($id): string
{
    parse_str(file_get_contents('php://input'), $data);
    Database::table('users')->where('id', $id)->update($data);
    return "Updated: {$id}";
}
```

### 6.5 设置 HTTP 状态码

在返回前调用 `http_response_code()`：

```php
public function show($id): string
{
    $user = Database::table('users')->where('id', $id)->first();
    if (!$user) {
        http_response_code(404);
        return View::render('errors/404');
    }
    return View::render('users/show', ['user' => $user]);
}
```

---

## 七、视图（Twig）

### 7.1 模板目录

所有模板放在 `views/`，路径由 `config.view_path` 指定。

```
views/
├── home.twig
└── users/
    ├── index.twig
    └── show.twig
```

### 7.2 渲染方法（省略 `.twig`）

`View::render()` 会自动补 `.twig` 后缀：

```php
View::render('home', $data);            // → home.twig
View::render('users/index', $data);     // → users/index.twig
View::render('home.twig', $data);       // 带后缀也支持
```

判断规则：若模板字符串**不含点**，则追加 `.twig`；含点则不追加（兼容 `xxx.html.twig`）。

### 7.3 传递变量

```php
View::render('home', ['name' => 'World', 'age' => 18]);
```

模板中直接使用：

```twig
<h1>Hello {{ name }}, {{ age }} years old</h1>
```

### 7.4 子目录模板

```php
View::render('users/index', ['users' => $users]);
```

对应 `views/users/index.twig`。

### 7.5 布局与继承

`views/layouts/base.twig`：

```twig
<!DOCTYPE html>
<html>
<head>
    <title>{% block title %}默认标题{% endblock %}</title>
</head>
<body>
    {% block content %}{% endblock %}
</body>
</html>
```

子模板 `views/home.twig`：

```twig
{% extends "layouts/base.twig" %}

{% block title %}首页{% endblock %}

{% block content %}
    <h1>Hello {{ name }}</h1>
{% endblock %}
```

### 7.6 常用 Twig 语法速查

**输出变量**

```twig
{{ name }}
{{ user.email }}
{{ user['email'] }}
{{ name|upper }}       {# 转大写 #}
{{ name|lower }}       {# 转小写 #}
{{ name|length }}      {# 长度 #}
{{ price|number_format(2) }}   {# 数字格式化 #}
{{ content|raw }}      {# 不转义，慎用 #}
```

**条件**

```twig
{% if user %}
    已登录
{% elseif guest %}
    游客
{% else %}
    未登录
{% endif %}
```

**循环**

```twig
{% for user in users %}
    <li>{{ user.name }}</li>
{% else %}
    <li>暂无数据</li>
{% endfor %}
```

循环变量：

```twig
{% for u in users %}
    {{ loop.index }} - {{ loop.first }} - {{ loop.last }}
{% endfor %}
```

**注释**

```twig
{# 这是注释，不会输出 #}
```

**包含模板**

```twig
{% include 'partials/header.twig' %}
{% include 'partials/header.twig' with {'title': '首页'} %}
```

**变量设置**

```twig
{% set total = 0 %}
{% set name = 'Tom' %}
```

**URL / 转义**

```twig
{{ url|e }}            {# 转义 HTML #}
```

> 建议：**默认不要用 `|raw`**，Twig 默认会 HTML 转义，是安全的。

### 7.7 模板缓存说明

- `debug = true`：Twig 不缓存，每次重新编译，改模板立即生效
- `debug = false`：编译产物写入 `storage/cache/`，性能更好

生产环境建议 `debug = false`，模板改动后清一次 `storage/cache/`。

---

## 八、缓存

### 8.1 缓存目录

文件缓存写入 `storage/cache/`，文件名 = `md5(key) . '.cache'`。

### 8.2 基本 API

```php
use Core\Cache;

Cache::set('key', $value);          // 写入，使用默认 TTL
Cache::set('key', $value, 600);     // 写入，600 秒
Cache::get('key');                  // 读取，未命中返回 null
Cache::get('key', 'default');       // 未命中返回默认值
Cache::has('key');                  // 是否存在
Cache::delete('key');               // 删除
Cache::clear();                     // 清空所有
```

### 8.3 TTL 设置

- 未传第三个参数时使用 `config.cache_ttl`（默认 3600 秒）
- 过期后再次 `get()` 会自动删除该缓存文件并返回默认值

### 8.4 典型使用场景

```php
$users = Cache::get('user_list');
if ($users === null) {
    $users = Database::table('users')->get();
    Cache::set('user_list', $users, 60);
}
return View::render('users/index', ['users' => $users]);
```

写操作后主动失效：

```php
Database::table('users')->insert($data);
Cache::delete('user_list');
```

### 8.5 与 Twig 编译缓存的区别

| 项目 | 文件缓存 `Cache` | Twig 编译缓存 |
|------|-----------------|--------------|
| 用途 | 业务数据缓存 | 模板编译产物 |
| 目录 | `storage/cache/` | 同一目录 |
| 管理 | `Cache::clear()` | `debug=false` 时自动管理 |
| 命中 | 手动判断 | 由 Twig 内部处理 |

两者目录相同但文件后缀不同，互不干扰。

---

## 九、数据库（PDO）

### 9.1 数据库配置

见 `config/config.php` 的 `db` 段。生产环境建议把账号密码放到 `.env` 或环境变量，再在配置里读取。

### 9.2 连接初始化

`App` 构造时调用 `Database::init($config['db'])`，整个请求只建立一次连接。业务代码直接用静态方法或 `Database::table()` 即可。

### 9.3 查询构造器

#### table / select

```php
Database::table('users')->get();                          // SELECT *
Database::table('users')->select(['id', 'name'])->get(); // 指定列
```

#### where / whereIn

```php
Database::table('users')->where('id', 1)->first();             // = 简写
Database::table('users')->where('age', '>', 18)->get();        // 带操作符
Database::table('users')
    ->where('age', '>', 18)
    ->where('status', 'active')
    ->get();                                                    // 多个 AND

Database::table('users')->whereIn('id', [1, 2, 3])->get();
```

#### orderBy / limit

```php
Database::table('users')
    ->orderBy('id', 'DESC')
    ->limit(0, 10)
    ->get();

Database::table('users')->limit(5)->get();   // 前 5 条
```

#### get / first / count

```php
$all   = Database::table('users')->get();                 // 数组
$one   = Database::table('users')->where('id', 1)->first();// 单条 or null
$total = Database::table('users')->where('status', 'active')->count();
```

### 9.4 写入操作

#### insert

```php
$id = Database::table('users')->insert([
    'name'  => 'Alice',
    'email' => 'alice@example.com',
]);
```

返回自增主键。

#### update

```php
Database::table('users')
    ->where('id', $id)
    ->update(['name' => 'Alice2']);
```

返回受影响行数。**必须带 `where`**，否则会更新全表。

#### delete

```php
Database::table('users')->where('id', $id)->delete();
```

同样**必须带 `where`**。

### 9.5 原生 SQL

```php
$rows = Database::query('SELECT * FROM users WHERE age > ?', [18]);

$affected = Database::execute(
    'UPDATE users SET status = ? WHERE id = ?',
    ['active', 1]
);
```

`query()` 返回数组，`execute()` 返回受影响行数。

### 9.6 事务处理

```php
try {
    Database::begin();
    Database::table('accounts')->where('id', 1)->update(['balance' => 900]);
    Database::table('accounts')->where('id', 2)->update(['balance' => 1100]);
    Database::commit();
} catch (\Throwable $e) {
    Database::rollback();
}
```

### 9.7 安全性说明

- 所有值都通过 `?` 占位符绑定，**避免 SQL 注入**
- 不要手动拼接用户输入到 SQL
- 列名、表名无法用占位符，应来自代码常量或白名单

### 9.8 常见坑

| 坑 | 说明 |
|----|------|
| update/delete 不带 where | 会作用于全表 |
| `first()` 返回 null | 未命中时为 `null`，需判空 |
| 链式复用 | `Database::table()` 每次返回新实例，可安全链式调用 |
| 时间字段 | 建议数据库用 `DATETIME`，PHP 侧格式化后写入 |
| 中文乱码 | 确认 `charset=utf8mb4` |

---

## 十、工具类（Utils）

### 10.1 目录与命名空间

- 目录：`utils/`
- 命名空间：`Utils`
- 由 Composer 的 PSR-4 加载

### 10.2 现有方法

`utils/Helper.php`：

```php
namespace Utils;

class Helper
{
    public static function upper(string $str): string
    {
        return strtoupper($str);
    }

    public static function dump($var): void
    {
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
    }
}
```

使用：

```php
use Utils\Helper;

echo Helper::upper('hello');   // HELLO
Helper::dump($data);           // 调试输出
```

### 10.3 扩展自定义工具

新增文件如 `utils/Str.php`：

```php
<?php

namespace Utils;

class Str
{
    public static function limit(string $str, int $len = 50): string
    {
        return mb_strlen($str) > $len ? mb_substr($str, 0, $len) . '...' : $str;
    }
}
```

直接 `Utils\Str::limit($text, 30)` 使用。

---

## 十一、请求生命周期

### 11.1 完整流程图

```
浏览器请求
    ↓
public/index.php
    ↓
Composer Autoload
    ↓
new App()
    ├── 读 config/config.php
    ├── Cache::init()
    ├── View::init()
    └── Database::init()
    ↓
App::run()
    ├── new Router()
    └── require routes/web.php（注册路由）
    ↓
Router::dispatch($method, $uri)
    ├── 遍历路由，正则匹配
    ├── 提取参数
    └── 调用控制器方法 / 闭包
    ↓
Controller
    ├── 可选：Cache 读取
    ├── 可选：Database 查询
    ├── 可选：Cache 写入
    └── View::render()
    ↓
返回字符串 → echo → 响应输出
```

### 11.2 各阶段职责

| 阶段 | 文件 | 职责 |
|------|------|------|
| 入口 | `public/index.php` | 引入 vendor、启动 App |
| 初始化 | `core/App.php` | 加载配置、初始化各模块 |
| 路由注册 | `routes/web.php` | 定义 URL → 处理器 |
| 分发 | `core/Router.php` | 匹配 URL、提取参数、调用处理器 |
| 业务 | `app/Controllers/*` | 处理逻辑，调用 Cache / Database / View |
| 输出 | `App::run()` | 把返回值 echo 出去 |

---

## 十二、常见任务示例

### 12.1 做一个列表页

路由：

```php
$router->get('/users', [UserController::class, 'index']);
```

控制器：

```php
public function index(): string
{
    $users = Database::table('users')->orderBy('id', 'DESC')->get();
    return View::render('users/index', ['users' => $users]);
}
```

模板 `views/users/index.twig`：

```twig
{% extends "layouts/base.twig" %}
{% block content %}
<ul>
    {% for u in users %}
        <li>{{ u.id }} - {{ u.name }}</li>
    {% else %}
        <li>暂无用户</li>
    {% endfor %}
</ul>
{% endblock %}
```

### 12.2 做一个详情页（带 404）

```php
$router->get('/users/:id(\d+)', [UserController::class, 'show']);
```

```php
public function show($id): string
{
    $user = Database::table('users')->where('id', $id)->first();
    if (!$user) {
        http_response_code(404);
        return 'User not found';
    }
    return View::render('users/show', ['user' => $user]);
}
```

### 12.3 表单提交（POST）

```php
$router->post('/users', [UserController::class, 'store']);
```

```php
public function store(): string
{
    $id = Database::table('users')->insert([
        'name'  => $_POST['name']  ?? '',
        'email' => $_POST['email'] ?? '',
    ]);
    return "Created: {$id}";
}
```

### 12.4 更新与删除（PUT / DELETE）

```php
$router->put('/users/:id',    [UserController::class, 'update']);
$router->delete('/users/:id', [UserController::class, 'destroy']);
```

```php
public function update($id): string
{
    parse_str(file_get_contents('php://input'), $data);
    Database::table('users')->where('id', $id)->update($data);
    return "Updated: {$id}";
}

public function destroy($id): string
{
    Database::table('users')->where('id', $id)->delete();
    return "Deleted: {$id}";
}
```

### 12.5 带缓存的数据查询

```php
public function index(): string
{
    $users = Cache::get('user_list');
    if ($users === null) {
        $users = Database::table('users')->get();
        Cache::set('user_list', $users, 60);
    }
    return View::render('users/index', ['users' => $users]);
}
```

### 12.6 JSON API 返回

```php
public function apiList(): string
{
    header('Content-Type: application/json; charset=utf-8');
    $users = Database::table('users')->get();
    return json_encode(['code' => 0, 'data' => $users], JSON_UNESCAPED_UNICODE);
}
```

---

## 十三、最佳实践

### 13.1 目录与命名规范

- 控制器放 `app/Controllers/`，一个资源一个控制器
- 命名空间与目录一致
- 类名与文件名一致，首字母大写
- 工具方法放 `utils/`，不要塞进控制器

### 13.2 控制器瘦身

控制器只做三件事：

1. 取请求参数
2. 调用数据 / 缓存 / 业务
3. 渲染或返回

复杂逻辑抽到 `app/Services/` 或模型方法里，避免控制器膨胀。

### 13.3 缓存策略

- 读多写少的列表、统计结果适合缓存
- 写操作后**主动 delete** 相关 key
- TTL 不要过长；调试期可临时关掉缓存
- key 命名建议带业务前缀，如 `user_list`、`post_5`

### 13.4 数据库使用规范

- 始终用 `?` 参数绑定
- 禁止无 where 的 update/delete
- 事务包裹多表写操作
- 大数据量查询用 `limit` 分页
- 表名、列名统一风格（下划线）

---

## 十四、已知限制

### 14.1 不支持的功能

- 中间件 / 过滤器
- 路由分组、命名路由
- 依赖注入容器
- 模型 ORM、关联关系
- 请求 / 响应对象封装
- 命令行工具
- 表单验证、CSRF
- 日志系统

### 14.2 手动扩展方向

1. **中间件**：在 `Router::call()` 前插入管道
2. **路由分组**：给 Router 加 `group()` 与前缀栈
3. **命名路由**：路由注册时记录 `name`，生成 URL 反查
4. **Model 基类**：封装表名、主键、find / save
5. **Request / Response**：把 `$_GET`、`$_POST` 等封装成对象
6. **日志**：封装 `storage/logs/` 下的写入方法

每一项都可以在现有模块基础上迭代，不需要重写。

---

## 十五、附录

### 15.1 API 速查表

**View**

```php
View::render(string $template, array $data = []): string
```

**Cache**

```php
Cache::set(string $key, $value, ?int $ttl = null): bool
Cache::get(string $key, $default = null)
Cache::has(string $key): bool
Cache::delete(string $key): bool
Cache::clear(): void
```

**Router**

```php
$router->get(string $path, $handler): void
$router->post(string $path, $handler): void
$router->put(string $path, $handler): void
$router->delete(string $path, $handler): void
```

**Database**

```php
Database::table(string $table): self
Database::query(string $sql, array $bindings = []): array
Database::execute(string $sql, array $bindings = []): int
Database::begin() / commit() / rollback(): void

// 链式
->select(array $columns)
->where(string $col, string $op, $value = null)
->whereIn(string $col, array $values)
->orderBy(string $col, string $dir = 'ASC')
->limit(int $offset, ?int $count = null)
->get() / first() / count()
->insert(array $data): int
->update(array $data): int
->delete(): int
```

### 15.2 Twig 语法速查

**输出**

```twig
{{ var }}
{{ obj.prop }}
{{ arr[0] }}
{{ var|upper }}
{{ var|lower }}
{{ var|length }}
{{ var|raw }}
{{ num|number_format(2) }}
```

**条件**

```twig
{% if cond %} ... {% elseif cond2 %} ... {% else %} ... {% endif %}
```

**循环**

```twig
{% for item in items %}
    {{ loop.index }} {{ item }}
{% else %}
    空
{% endfor %}
```

**继承 / 块**

```twig
{% extends "layouts/base.twig" %}
{% block content %} ... {% endblock %}
```

**包含**

```twig
{% include "partials/header.twig" %}
{% include "partials/header.twig" with {'title': '首页'} %}
```

**赋值 / 注释**

```twig
{% set name = 'Tom' %}
{# 注释 #}
```

### 15.3 常见问题 FAQ

**Q1：访问 `/users/5` 报 404，怎么办？**
检查路由是否注册、`:id` 是否被前面的路由吃掉、HTTP 方法是否一致。

**Q2：Twig 报模板找不到？**
确认 `view_path` 配置正确、模板路径大小写一致、扩展名被自动补全。

**Q3：更新数据库后页面没变？**
可能命中了缓存。检查是否 `Cache::delete()` 了相关 key。

**Q4：PUT / DELETE 拿不到参数？**
PHP 不会自动填充，需要 `parse_str(file_get_contents('php://input'), $data)`。

**Q5：中文乱码？**
数据库连接字符集设为 `utf8mb4`，响应头加 `charset=utf-8`。

**Q6：生产环境模板改动不生效？**
`debug=false` 时 Twig 使用编译缓存，清一次 `storage/cache/`。

### 15.4 术语表

| 术语 | 含义 |
|------|------|
| 路由 | URL + HTTP 方法到处理器的映射 |
| 动态参数 | 路由路径中以 `:name` 表示的变量段 |
| 闭包处理器 | 直接以函数作为路由处理逻辑 |
| 控制器 | 承载业务逻辑的类 |
| 模板 | Twig 文件，渲染为 HTML |
| 编译缓存 | Twig 把模板转成 PHP 后的缓存产物 |
| 文件缓存 | 框架 Cache 模块写盘的业务数据 |
| 查询构造器 | 用方法链拼 SQL 的工具 |
| 参数绑定 | 用 `?` 占位防止 SQL 注入 |
| 生命周期 | 一次请求从入口到输出的全过程 |
