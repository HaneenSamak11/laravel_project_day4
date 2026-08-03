# Laravel Project Day 4

مشروع Laravel 12 كامل للتدريب على:

- Web Authentication: Register, Login, Logout.
- API Authentication باستخدام Laravel Sanctum.
- Middleware للويب والـ API.
- Admin وUser authorization.
- Products CRUD في الويب والـ API.
- إدارة المستخدمين والأدوار عبر API للأدمن.
- OpenAI data chatbot يجيب عن أسئلة بيانات المنتجات والمستخدمين من خلال أدوات Eloquent آمنة.
- Postman Collection واختبارات Feature.

## أسرع طريقة للرفع على GitHub

على Windows اضغطي مرتين على:

```text
UPLOAD_TO_GITHUB.bat
```

الرابط المستهدف:

```text
https://github.com/HaneenSamak11/laravel_project_day4
```

## تشغيل المشروع أول مرة

يمكنك الضغط مرتين على:

```text
SETUP_PROJECT.bat
```

أو تنفيذ الأوامر يدويًا:

```bash
composer install
copy .env.example .env
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
php artisan serve
```

على Windows PowerShell، لإنشاء SQLite يدويًا:

```powershell
New-Item database/database.sqlite -ItemType File -Force
```

ثم افتحي:

```text
http://127.0.0.1:8000
```

## حسابات التجربة

```text
Admin: admin@example.com / password
User:  user@example.com / password
```

غيّري كلمات المرور في أي استخدام حقيقي.

## إعداد OpenAI

بعد إنشاء ملف `.env` ضعي المفتاح محليًا فقط:

```env
OPENAI_API_KEY=your_real_key
OPENAI_MODEL=gpt-5.6-luna
```

لا ترفعي `.env` أو مفتاح API إلى GitHub. ملف `.gitignore` يمنع ذلك تلقائيًا.

## أهم روابط الويب

| Method | URL | Access |
|---|---|---|
| GET/POST | `/register` | Guest |
| GET/POST | `/login` | Guest |
| POST | `/logout` | Authenticated |
| GET | `/dashboard` | Authenticated |
| GET | `/products` | Authenticated |
| Create/Update/Delete | `/products/...` | Admin |
| GET | `/chatbot` | Authenticated |
| POST | `/chatbot/ask` | Authenticated |

## أهم API Endpoints

| Method | URL | Access |
|---|---|---|
| POST | `/api/register` | Public |
| POST | `/api/login` | Public |
| GET | `/api/me` | Bearer Token |
| POST | `/api/logout` | Bearer Token |
| GET | `/api/products` | Bearer Token |
| POST/PATCH/DELETE | `/api/products...` | Admin Token |
| GET/PATCH/DELETE | `/api/users...` | Admin Token |
| POST | `/api/chatbot/ask` | Bearer Token |
| GET/DELETE | `/api/chatbot/history` | Bearer Token |

استوردي ملف Postman الموجود داخل:

```text
postman/Laravel_Web_API_Chatbot.postman_collection.json
```

## ملاحظة GitHub

المجلدان `vendor` و`node_modules`، وملف `.env`، وقاعدة SQLite المحلية لا تُرفع إلى GitHub. هذا طبيعي وصحيح. بعد تنزيل المشروع على جهاز جديد، نفّذي `composer install` ثم خطوات الإعداد السابقة.
